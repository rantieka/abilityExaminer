<?php

namespace App\Jobs;

use App\Models\Application;
use App\Services\GroqService;
// use App\Services\OllamaService; // Fallback option
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Smalot\PdfParser\Parser;

class ProcessCvScreening implements ShouldQueue
{
  use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;
  
  const HIGH_CONFIDENCE_THRESHOLD = 0.8;
  const LOW_CONFIDENCE_THRESHOLD = 0.4;

  public $tries = 5; 
  public $backoff = [30, 60, 120, 300];
  public $application;
  public function __construct(Application $application)
  {
    $this->application = $application;
    $this->onConnection('database'); // Force this heavy job to use Database Queue
  }

  public function handle(GroqService $groq, Parser $parser): void
  // public function handle(OllamaService $ai, Parser $parser): void // Fallback
  {
    if (!$this->application) {
      Log::error("ProcessCvScreening: Application model is null. It may have been deleted.");
      return;
    }

    Log::info("Starting CV Screening for Application ID: " . $this->application->id);

    try {
      // Read & parse CV file (LogicException = fail immediately)
      $path = Storage::disk('public')->path($this->application->cv_path);

      if (!file_exists($path)) {
        throw new \LogicException("CV file not found in storage.");
      }

      $cvText = $this->parsePdf($parser, $path);
      $cvText = preg_replace('/\s+/', ' ', $cvText);
      
      $originalLength = strlen($cvText);
      $cvText = mb_substr($cvText, 0, 12000); 
      Log::info("CV Processing [App ID: {$this->application->id}]: Original Length: {$originalLength}, Sent Length: " . strlen($cvText));

      // Data Guard: Check if text is suspiciously short AND lacks common structure
      $trimmedText = trim($cvText);
      if (strlen($trimmedText) < 200 && str_word_count($trimmedText) < 25) {
        throw new \LogicException("CV content is too sparse. This might be a scanned image or an invalid PDF. Please review manually.");
      }

      // Anonymize CV before sending to AI
      $anonymizedCvText = $this->maskPII($cvText, $this->application);

      // Validate job vacancy data (LogicException = fail immediately)
      $job = $this->application->jobVacancy;

      if (!$job) {
        throw new \LogicException("Job vacancy data not found for this application.");
      }

      if (empty(trim(strip_tags($job->qualifications ?? '')))) {
        throw new \LogicException("Job vacancy '{$job->title}' has no defined qualifications. Cannot perform screening.");
      }

      // Send to AI for structured data extraction
      $messages = $this->buildPrompt($job, $anonymizedCvText);

      // Add a 30-second delay to prevent Groq TPM (Tokens Per Minute) Rate Limit
      sleep(30);

      // Low temperature for deterministic, structured extraction
      $rawResult = $groq->chat($messages, 0.1);
      Log::info("Groq Raw Result [App ID: {$this->application->id}]:\n" . json_encode($rawResult, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));

      if (!$rawResult) {
        throw new \RuntimeException("Groq AI returned no valid response. Possible network or API issue.");
      }

      // Validate & sanitize AI JSON structure before processing
      $result = $this->validateAiResponse($rawResult);

      $calculatedScore = $this->calculateScore($result, $job);
      Log::info("Skills Found [App ID: {$this->application->id}]:\n" . json_encode($result['skills_found'] ?? [], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
      Log::info("Calculated PHP Score: {$calculatedScore}");

      // Build human-readable summary from the requirements analysis
      $skillsFound = $result['skills_found'];
      $otherSkills = $result['other_technical_skills'];
      $generalReqs = $result['general_requirements_analysis'];
      $expYearsRaw = $result['experience_years'];
      $expYears    = (float) $expYearsRaw;
      $pros = [];
      $cons = [];

      // Required Skills
      $allRequired = $job->required_skills ?? [];
      $foundRequiredLower = array_map('strtolower', $skillsFound['required']);
      $missingRequired = [];
      foreach ($allRequired as $req) {
        if (!in_array(strtolower($req), $foundRequiredLower)) {
          $missingRequired[] = $req;
        }
      }

      if (count($missingRequired) === 0 && count($allRequired) > 0) {
        $pros[] = "Matches core skill requirements.";
      } elseif (count($missingRequired) > 0) {
        $cons[] = "Mandatory skills not explicitly found: " . implode(', ', $missingRequired) . ".";
      }

      // Preferred/Bonus
      $filteredPreferred = array_filter($skillsFound['preferred'], fn($s) => true);
      if (count($filteredPreferred) > 0) {
        $pros[] = "Demonstrates proficiency in: " . implode(', ', $filteredPreferred) . ".";
      }

      if (count($skillsFound['bonus']) > 0) {
        $pros[] = "Brings additional value with expertise in: " . implode(', ', $skillsFound['bonus']) . ".";
      }

      // Experience & Education
      if ($expYears >= 5) {
        $pros[] = "Extensive professional background with {$expYears} years of experience.";
      } elseif ($expYears >= 2) {
        $pros[] = "Solid career foundation with {$expYears} years in the industry.";
      } elseif ($expYears > 0) {
        $pros[] = "Possesses {$expYears} years of practical work experience.";
      }
      
      if (!empty($result['education_level'])) {
        $pros[] = "Academic background: {$result['education_level']} " . ($result['education_major'] ?? '') . ".";
      }

      // General Requirements
      foreach ($generalReqs as $item) {
        $reqTextLower = strtolower($item['requirement'] ?? '');
        $isMet        = $item['is_met'] ?? true;

        // If fresh graduate but candidate has experience, handle based on years
        if (str_contains($reqTextLower, 'fresh')) {
          if ($expYears > 2) {
            $cons[] = "Candidate may be overqualified (More than 2 years of experience for a Fresh Graduate role).";
          }
          if ($expYears >= 1) {
            continue; // Do not show as "No explicit evidence" if they have experience
          }
        }

        if (!$isMet) {
          $cons[] = "No explicit evidence found for: " . ($item['requirement'] ?? 'Unknown');
        }
      }

      $expFormatted = (float) $expYearsRaw;
      $summary = "Identified " . count($skillsFound['required']) . " required skills and " . count($skillsFound['preferred']) . " preferred skills with " . $expFormatted . " years of experience.";

      $this->application->update([
        'ai_score'    => $calculatedScore,
        'ai_analysis' => [
          'summary'        => $summary,
          'pros'           => $pros,
          'cons'           => array_slice(array_filter($cons), 0, 5),
          'extracted_data' => $result,
        ],
        'status' => 'pending',
      ]);

      Log::info("CV Screening Success. Final Score: {$calculatedScore} for Application ID: " . $this->application->id);

    } catch (\RuntimeException $e) {
      // Infrastructure errors
      Log::error("CV Screening Infrastructure Error [Application ID: {$this->application->id}]: " . $e->getMessage());
      $this->failWithMessage("Service temporarily unavailable: " . $e->getMessage());
      throw $e;

    } catch (\LogicException $e) {
      // Logic/data errors
      Log::error("CV Screening Logic Error [Application ID: {$this->application->id}]: " . $e->getMessage());
      $this->failWithMessage($e->getMessage());

    } catch (\Throwable $e) {
      // Unexpected errors
      Log::error("CV Screening Unexpected Error [Application ID: {$this->application->id}]: " . $e->getMessage());
      $this->failWithMessage("An unexpected error occurred: " . $e->getMessage());
    }
  }

  // Fail gracefully
  protected function failWithMessage(string $message): void {
    if ($this->application) {
      $this->application->update([
        'ai_score'    => 0,
        'ai_analysis' => [
          'summary' => 'Automated analysis failed: ' . $message,
          'pros'    => [],
          'cons'    => ['A system or file error occurred. Please review this CV manually.'],
        ],
        'status' => 'pending',
      ]);
    }
  }

  /**
   * Parse a PDF file and return its plain text content.
     *
     * @throws \LogicException if the file cannot be parsed (non-retryable)
     */
  protected function parsePdf(Parser $parser, string $path): string {
    try {
      $pdf = $parser->parseFile($path);
      return $pdf->getText();
    } catch (\Exception $e) {
      Log::error("PDF Parsing failed for path [{$path}]: " . $e->getMessage());
      throw new \LogicException("Failed to read PDF file (file may be corrupt or not a valid PDF).");
    }
  }

  protected function buildPrompt(\App\Models\JobVacancy $job, string $cvText): array {
    $systemMessage = 'You are an HR Evaluation AI. You extract structured data. Output valid JSON ONLY without markdown formatting blocks. Output all text in English.';

    $reqSkills = is_array($job->required_skills) ? implode(', ', $job->required_skills) : '';
    $qualifications = strip_tags($job->qualifications ?? '');

    $userPrompt = "
      ## CV Content (Anonymized)
      {$cvText}

      ## Rules:
      Extract structured data from the CV above with EXTREME precision. 
      Follow these rules:
      1. SKILLS: Extract ALL technical skills, tools, and methodologies explicitly mentioned in the text (ensure you check project descriptions and work experience).
      2. EXPERIENCE: Calculate total years of experience ONLY where the role OR project involves Software Development or Coding. Ignore System Administration, Technician, or Support roles. Return 0 if none found.
      3. EDUCATION: Extract highest education level (SMK/D3/D4/S1/etc) and major.
      4. REQUIREMENTS: Check these specific labels from the CV:
         - {$qualifications}
         Determine if they are met based ONLY on the CV text.

      ## Format JSON strictly:
      {
        \"all_extracted_skills\": [],
        \"general_requirements_analysis\": [{\"requirement\": \"label\", \"is_met\": false}],
        \"experience_years\": 0.0,
        \"confidence\": 0.0,
        \"education_level\": \"\",
        \"education_major\": \"\"
      }
    ";

    Log::info("AI SCREENING PROMPT (App ID: {$this->application->id})\n" . $userPrompt . "\n");

    return [
      ['role' => 'system', 'content' => $systemMessage],
      ['role' => 'user',   'content' => $userPrompt],
    ];
  }

  /**
   * Safe skill normalization. Focus on trimming and common variants only.
   */
  protected function normalizeSkill(string $skill): string {
    $skill = strtolower(trim($skill));

    // Common aliases normalization
    $aliases = [
      // Version Control
      'github'        => 'git',
      'gitlab'        => 'git',
      'bitbucket'     => 'git',

      // JavaScript ecosystem
      'javascript'    => 'js',
      'ecmascript'    => 'js',
      'es6'           => 'js',
      'reactjs'       => 'react',
      'react.js'      => 'react',
      'vuejs'         => 'vue',
      'vue.js'        => 'vue',
      'angularjs'     => 'angular',
      'nextjs'        => 'next',
      'next.js'       => 'next',
      'nuxtjs'        => 'nuxt',
      'nuxt.js'       => 'nuxt',
      'node.js'       => 'node',

      // CSS
      'css3'          => 'css',
      'html5'         => 'html',
      'scss'          => 'sass',

      // Database
      'postgresql'    => 'postgres',
      'mariadb'       => 'mysql',
      'mongo'         => 'mongodb',
      'mssql'         => 'sqlserver',
      'ms sql'        => 'sqlserver',

      // Cloud
      'amazon web services' => 'aws',
      'google cloud'        => 'gcp',
      'google cloud platform' => 'gcp',
      'azure'               => 'azure',
      'microsoft azure'     => 'azure',

      // Mobile
      'react native'  => 'reactnative',
      'flutter'       => 'flutter',
      'ios'           => 'ios',

      // Python
      'python3'       => 'python',
      'py'            => 'python',

      // DevOps
      'k8s'           => 'kubernetes',
      'docker compose' => 'docker',

      // Others
      'golang'        => 'go',
      'c sharp'       => 'csharp',
      'c#'            => 'csharp',
      'dot net'       => 'dotnet',
      '.net'          => 'dotnet',
      'springboot'    => 'spring',
      'spring boot'   => 'spring',
      'ruby on rails' => 'rails',
      'ror'           => 'rails',
      'rest api'      => 'rest-api',
      'restapi'       => 'rest-api',
      'restful'       => 'rest-api',
      'restfull'      => 'rest-api',
      'restful api'   => 'rest-api',
      'restfull api'  => 'rest-api',
      'rest api'      => 'rest-api',
    ];

    if (isset($aliases[$skill])) {
        $skill = $aliases[$skill];
    }

    $replacements = [
      '.js' => '',
      ' framework' => '',
      ' library' => '',
      ' language' => '',
      'postgresql' => 'postgres',
      'nodejs' => 'node',
      // Strip spaces, dashes, underscores — catches AI extraction typos
      // e.g. "mysq l" → "mysql", "postgre sql" → "postgresql", "react .js" → "react"
      ' ' => '',
      '-' => '',
      '_' => '',
    ];
    return str_replace(array_keys($replacements), array_values($replacements), $skill);
  }

  /**
   * Fuzzy match two skills — case-insensitive substring match.
   * Returns true if one skill is contained within the other (or they're identical).
   */
  protected function skillMatches(string $normalizedJob, string $normalizedFound): bool {
    return $normalizedJob === $normalizedFound
      || str_contains($normalizedJob, $normalizedFound)
      || str_contains($normalizedFound, $normalizedJob);
  }

  protected function validateAiResponse(array $result): array
  {
    $skills = $result['skills_found'] ?? [];
    
    $reqAnalysis = $result['general_requirements_analysis'] ?? [];
    $sanitizedReqs = [];
    if (is_array($reqAnalysis)) {
      foreach ($reqAnalysis as $item) {
        if (is_array($item)) {
          $sanitizedReqs[] = [
            'requirement' => trim((string)($item['requirement'] ?? 'Unknown')),
            'is_met'      => (bool)($item['is_met'] ?? false),
          ];
        }
      }
    }

    $sanitized = [
      'all_extracted_skills'          => is_array($result['all_extracted_skills'] ?? null) ? array_unique(array_map(fn($val) => is_array($val) ? json_encode($val) : (string)$val, $result['all_extracted_skills'])) : [],
      'general_requirements_analysis' => $sanitizedReqs,
      'experience_years'              => is_numeric($result['experience_years'] ?? null) ? round(max(0, min(15, (float)$result['experience_years'])), 1) : 0.0,
      'confidence'                    => is_numeric($result['confidence'] ?? null) ? max(0, min(1, (float)$result['confidence'])) : 0.5,
      'education_level'               => is_string($result['education_level'] ?? null) ? trim($result['education_level']) : '',
      'education_major'               => is_string($result['education_major'] ?? null) ? trim($result['education_major']) : '',
    ];

    // Final Guard: If no skills found at all, it's likely a bad extraction or invalid CV
    if (empty($sanitized['all_extracted_skills'])) {
      Log::warning("Validation Guard: No skills extracted for Application ID: " . $this->application->id);
    }

    return $sanitized;
  }

  protected function calculateScore(array &$extractedData, \App\Models\JobVacancy $job): int {
    $rawScore = 0;
    $expYears   = min(15, (float)($extractedData['experience_years'] ?? 0)); // Cap at 15
    $confidence = (float)($extractedData['confidence'] ?? 0.5);
    $genReqs    = $extractedData['general_requirements_analysis'] ?? [];

    // Hallucination & Logic Guard
    // If experience > 20 years (unlikely for most roles) or confidence is critical
    if ($expYears > 20 || $confidence < 0.3) {
      $confidence = 0.1; // Mark as unreliable
    }

    $reqSkillsJob   = is_array($job->required_skills) ? $job->required_skills : [];
    $prefSkillsJob  = is_array($job->preferred_skills) ? $job->preferred_skills : [];
    $bonusSkillsJob = is_array($job->bonus_skills) ? $job->bonus_skills : [];

    $reqSkillsNorm   = array_map([$this, 'normalizeSkill'], $reqSkillsJob);
    $prefSkillsNorm  = array_map([$this, 'normalizeSkill'], $prefSkillsJob);
    $bonusSkillsNorm = array_map([$this, 'normalizeSkill'], $bonusSkillsJob);

    $reqCount   = count($reqSkillsNorm);
    $prefCount  = count($prefSkillsNorm);
    $bonusCount = count($bonusSkillsNorm);

    // Normalize found skills
    $allFoundNorm = array_map([$this, 'normalizeSkill'], ($extractedData['all_extracted_skills'] ?? []));

    // Fuzzy intersection using substring match (case-insensitive)
    $matchedReq   = [];
    $matchedPref  = [];
    $matchedBonus = [];
    foreach ($reqSkillsNorm as $req) {
      foreach ($allFoundNorm as $found) {
        if ($this->skillMatches($req, $found)) {
          $matchedReq[] = $req;
          break;
        }
      }
    }
    foreach ($prefSkillsNorm as $pref) {
      foreach ($allFoundNorm as $found) {
        if ($this->skillMatches($pref, $found)) {
          $matchedPref[] = $pref;
          break;
        }
      }
    }
    foreach ($bonusSkillsNorm as $bonus) {
      foreach ($allFoundNorm as $found) {
        if ($this->skillMatches($bonus, $found)) {
          $matchedBonus[] = $bonus;
          break;
        }
      }
    }

    $matchedReqCount   = count($matchedReq);
    $matchedPrefCount  = count($matchedPref);
    $matchedBonusCount = count($matchedBonus);

    // Store categorized skills back into extractedData for the summary generator
    $extractedData['skills_found'] = [
      'required'  => array_values($matchedReq),
      'preferred' => array_values($matchedPref),
      'bonus'     => array_values($matchedBonus),
    ];
      // Other technical skills: found skills NOT in any job-skill bucket (using fuzzy match)
    $jobSkillNorms = array_merge($reqSkillsNorm, $prefSkillsNorm, $bonusSkillsNorm);
    $otherSkills = [];
    foreach ($allFoundNorm as $found) {
      $isJobSkill = false;
      foreach ($jobSkillNorms as $jobNorm) {
        if ($this->skillMatches($jobNorm, $found)) {
          $isJobSkill = true;
          break;
        }
      }
      if (!$isJobSkill) {
        $otherSkills[] = $found;
      }
    }
    $extractedData['other_technical_skills'] = array_values(array_unique($otherSkills));

    // Core Required Skills (Max 60 points)
    $corePoints = 0;
    if ($reqCount > 0) {
        $matchRatio = $matchedReqCount / $reqCount;
        $corePoints = (int) round($matchRatio * 60);
    }
    $rawScore += $corePoints;

    // Preferred & Bonus Skills (Max 10 points)
    $prefWeight  = 7;
    $bonusWeight = 3;

    $prefRatio   = $prefCount   > 0 ? $matchedPrefCount  / $prefCount  : 0;
    $bonusRatio  = $bonusCount > 0 ? $matchedBonusCount / $bonusCount : 0;
    
    $optPoints  = ($prefRatio * $prefWeight) + ($bonusRatio * $bonusWeight);
    $optPointsFinal = (int) round($optPoints);
    $rawScore += $optPointsFinal;

    // Experience (Max 20 points)
    // Check if this is specifically a Fresh Graduate targeted job
    $isFreshGradJob = false;
    foreach ($genReqs as $item) {
      $reqLabel = strtolower($item['requirement'] ?? '');
      if (preg_match('/fresh/i', $reqLabel)) {
        $isFreshGradJob = true;
        break;
      }
    }
      
    Log::info("Fresh Grad Job Detection [App ID: {$this->application->id}]: " . ($isFreshGradJob ? 'YES' : 'NO'));

    $expScore = match (true) {
      $expYears >= 5 => 20,
      $expYears >= 3 => 18,
      $expYears >= 2 => 15,
      $expYears >= 1 => 10,
      $expYears >= 0.5 => 5,
      $expYears > 0  => 2,
      default        => 0,
    };

    // OVERQUALIFIED LOGIC 
    // Based on user provided table for Fresh Grad vacancies (scaled x2 to maintain 20pts max)
    if ($isFreshGradJob) {
      $expScore = match (true) {
        $expYears >= 6 => 6,
        $expYears >= 4 => 10,
        $expYears > 2  => 14,
        default        => 20, // 0-2 years get full score
      };
    }

    $rawScore += $expScore;

    // General Requirements (Max 10 points)
    $genPoints = 10;
    
    // Soft skill keywords to avoid punishing lack of "vague" traits in a technical CV
    $softSkillKeywords = ['teamwork', 'collaboration', 'communication', 'learning', 'growth', 'interpersonal', 'leadership', 'eagerness', 'attitude', 'bersedia', 'jujur'];

    foreach ($genReqs as $item) {
      $reqTextLower = strtolower($item['requirement'] ?? '');
      $isMet        = $item['is_met'] ?? true;

      // Special Case: If requirement is "fresh graduate" and candidate has experience, they pass.
      if (str_contains($reqTextLower, 'fresh') && $expYears >= 1) {
        $isMet = true;
      }

      if (!$isMet) {
        $isSoftSkill = false;
        foreach ($softSkillKeywords as $keyword) {
          if (str_contains($reqTextLower, $keyword)) {
            $isSoftSkill = true;
            break;
          }
        }
        
        // Only penalize Hard Requirements (Education, Experience levels, Certification, etc.)
        // Soft skills (Teamwork, etc.) will NOT reduce the score but will still show in 'cons' for interviewers.
        if (!$isSoftSkill) {
          $genPoints -= 5; 
        }
      }
    }
    $genPointsFinal = max(0, $genPoints);
    $rawScore += $genPointsFinal;

    // FIRST-CLASS CONFIDENCE (The Multiplier)
    // If AI is very confident (>= 0.8), don't penalize the score.
    // Otherwise, multiply by confidence to account for uncertainty.
    $appliedConfidence = ($confidence >= self::HIGH_CONFIDENCE_THRESHOLD) ? 1.0 : $confidence;
    $finalScore = (int) round($rawScore * $appliedConfidence);

    // Hard Fail Threshold
    // If confidence is extremely low (< 0.4), force score to 0 to trigger manual review flag
    if ($confidence < self::LOW_CONFIDENCE_THRESHOLD) {
      $finalScore = 0;
    }

    Log::info("Scoring Detail [App ID: {$this->application->id}]:");
    Log::info("Core Skills (Max 60): {$corePoints}");
    Log::info("Optional Skills (Max 10): {$optPointsFinal}");
    Log::info("Experience (Max 20): {$expScore}");
    Log::info("General Reqs (Max 10): {$genPointsFinal}");
    Log::info("Raw Total (Max 100): {$rawScore}");
    Log::info("Confidence: {$confidence} (Applied: {$appliedConfidence})");
    Log::info("FINAL SCORE: {$finalScore}");

    // Safeguard bounds
    return max(0, min(100, $finalScore));
  }

  // Mask personally identifiable information (PII) from CV text before sending to AI.
  protected function maskPII(string $text, Application $app): string {
    $masked = $text;

    // Email: regex catches all email variants in the CV
    $masked = preg_replace('/[\w._%+\-]+@[\w.\-]+\.[a-zA-Z]{2,}/', '[EMAIL_REDACTED]', $masked);
    if ($app->email) {
      $masked = str_ireplace($app->email, '[EMAIL_REDACTED]', $masked);
    }

    // URL: Mask URLs to prevent AI distraction (portfolio links, etc)
    $masked = preg_replace('/(https?:\/\/[^\s]+|www\.[^\s]+)/i', '[URL_REDACTED]', $masked);

    // Phone: regex
    $masked = preg_replace(
      '/(\+62|62|0)[\s.\-]?\d{3,4}[\s.\-]?\d{3,4}[\s.\-]?\d{3,4}/',
      '[PHONE_REDACTED]',
      $masked
    );
    if ($app->phone) {
      $masked = str_ireplace($app->phone, '[PHONE_REDACTED]', $masked);
    }

    if ($app->full_name) {
      $masked = str_ireplace($app->full_name, '[CANDIDATE_NAME]', $masked);

      $nameParts = explode(' ', $app->full_name);
      if (!empty($nameParts[0]) && strlen($nameParts[0]) > 2) {
        $masked = str_ireplace($nameParts[0], '[CANDIDATE]', $masked);
      }
    }

    return $masked;
  }
}