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
      $cvText = mb_substr($cvText, 0, 4000); 

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
      sleep(15);

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
          $pros[] = "Matches all mandatory core requirements.";
      } elseif (count($missingRequired) > 0) {
          $cons[] = "Missing required skills: " . implode(', ', $missingRequired) . ".";
      }

      // Preferred/Bonus (Deduplicate against what AI already highlighted in strengths)
      $strengthsRaw = implode(' ', $result['key_strengths']);
      
      $filteredPreferred = array_filter($skillsFound['preferred'], fn($s) => !str_contains(strtolower($strengthsRaw), strtolower($s)));
      if (count($filteredPreferred) > 0) {
          $pros[] = "Possesses preferred skills: " . implode(', ', $filteredPreferred) . ".";
      }

      $filteredBonus = array_filter($skillsFound['bonus'], fn($s) => !str_contains(strtolower($strengthsRaw), strtolower($s)));
      if (count($filteredBonus) > 0) {
          $pros[] = "Has bonus qualifications in: " . implode(', ', $filteredBonus) . ".";
      }

      // Experience & Education
      if ($expYears >= 2) {
          $pros[] = "Solid professional experience ({$expYears} years).";
      }
      
      if (!empty($result['education_level'])) {
          $pros[] = "Educational background: {$result['education_level']} " . ($result['education_major'] ?? '');
      }

      // General Requirements
      foreach ($generalReqs as $item) {
          $reqTextLower = strtolower($item['requirement'] ?? '');
          $isMet        = $item['is_met'] ?? true;

          // If fresh graduate but candidate has experience, skip (do not display in pros/cons)
          if (str_contains($reqTextLower, 'fresh') && $expYears >= 1) {
              continue;
          }

          if (!$isMet) {
              $cons[] = "Failed general qualification: " . ($item['requirement'] ?? 'Unknown');
          }
      }

      $expFormatted = (float) $expYearsRaw;
      $summary = "Identified " . count($skillsFound['required']) . " required skills and " . count($skillsFound['preferred']) . " preferred skills with " . $expFormatted . " years of experience.";

      $this->application->update([
        'ai_score'    => $calculatedScore,
        'ai_analysis' => [
          'summary'        => $summary,
          'pros'           => array_merge(
              $result['key_strengths'] ?? [],
              $pros
          ),
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
    $systemMessage = 'You are an HR Evaluation AI. You extract structured data. Output valid JSON ONLY without markdown formatting blocks.';

    $reqSkills = is_array($job->required_skills) ? implode(', ', $job->required_skills) : '';
    $qualifications = strip_tags($job->qualifications ?? '');

    $userPrompt = "
      ## CV Content (Anonymized)
      {$cvText}

      ## TASK:
      Extract structured data from the CV above with EXTREME precision. 
      Follow these rules:
      1. SKILLS: Extract ALL technical skills, programming languages, and tools explicitly mentioned (limit to max 50 items).
      2. EXPERIENCE: Calculate total years of professional work experience based on dates.
      3. EDUCATION: Extract highest education level (SMK/D3/S1/etc) and major.
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
        \"education_major\": \"\",
        \"key_strengths\": []
      }
    ";

    Log::info("=== AI SCREENING PROMPT (App ID: {$this->application->id}) ===\n" . $userPrompt . "\n==================================================");

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
    $replacements = [
        '.js' => '',
        ' framework' => '',
        ' library' => '',
        ' language' => '',
        'postgresql' => 'postgres',
        'nodejs' => 'node',
    ];
    return str_replace(array_keys($replacements), array_values($replacements), $skill);
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
      'key_strengths'                 => is_array($result['key_strengths'] ?? null) ? array_slice(array_unique(array_map(fn($val) => is_array($val) ? json_encode($val) : (string)$val, $result['key_strengths'])), 0, 3) : [],
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

    // 0. Hallucination & Logic Guard
    // If experience > 20 years (unlikely for most roles) or confidence is critical
    if ($expYears > 20 || $confidence < 0.3) {
        $confidence = 0.1; // Mark as unreliable
    }

    $reqSkills   = array_map([$this, 'normalizeSkill'], (is_array($job->required_skills) ? $job->required_skills : []));
    $prefSkills  = array_map([$this, 'normalizeSkill'], (is_array($job->preferred_skills) ? $job->preferred_skills : []));
    $bonusSkills = array_map([$this, 'normalizeSkill'], (is_array($job->bonus_skills) ? $job->bonus_skills : []));

    $reqCount   = count($reqSkills);
    $prefCount  = count($prefSkills);
    $bonusCount = count($bonusSkills);

    // Normalize found skills
    $allFound = array_map([$this, 'normalizeSkill'], ($extractedData['all_extracted_skills'] ?? []));

    // Intersection (PURE PHP MATCHING)
    $matchedReq   = count(array_intersect($reqSkills, $allFound));
    $matchedPref  = count(array_intersect($prefSkills, $allFound));
    $matchedBonus = count(array_intersect($bonusSkills, $allFound));

    // Store categorized skills back into extractedData for the summary generator
    $extractedData['skills_found'] = [
        'required'  => array_values(array_intersect($reqSkills, $allFound)),
        'preferred' => array_values(array_intersect($prefSkills, $allFound)),
        'bonus'     => array_values(array_intersect($bonusSkills, $allFound)),
    ];
    $extractedData['other_technical_skills'] = array_values(array_diff($allFound, $reqSkills, $prefSkills, $bonusSkills));

    // 1. Core Required Skills (Max 60 points)
    $corePoints = 0;
    if ($reqCount > 0) {
        $matchRatio = $matchedReq / $reqCount;
        $corePoints = (int) round($matchRatio * 60);
    }
    $rawScore += $corePoints;

    // 2. Preferred & Bonus Skills (Max 10 points)
    $prefWeight  = 7;
    $bonusWeight = 3;
    
    $prefRatio  = $prefCount  > 0 ? $matchedPref  / $prefCount  : 0;
    $bonusRatio = $bonusCount > 0 ? $matchedBonus / $bonusCount : 0;
    
    $optPoints  = ($prefRatio * $prefWeight) + ($bonusRatio * $bonusWeight);
    $optPointsFinal = (int) round($optPoints);
    $rawScore += $optPointsFinal;

    // 3. Experience (Max 20 points)
    $expScore = match (true) {
        $expYears >= 5 => 20,
        $expYears >= 3 => 18,
        $expYears >= 2 => 15,
        $expYears >= 1 => 10,
        $expYears >= 0.5 => 5,
        $expYears > 0  => 2,
        default        => 0,
    };
    $rawScore += $expScore;

    // 4. General Requirements (Max 10 points)
    $genReqs   = $extractedData['general_requirements_analysis'] ?? [];
    $genPoints = 10;
    foreach ($genReqs as $item) {
      $reqTextLower = strtolower($item['requirement'] ?? '');
      $isMet        = $item['is_met'] ?? true;

      if (str_contains($reqTextLower, 'fresh') && $expYears >= 1) {
        $isMet = true;
      }

      if (!$isMet) {
        $genPoints -= 3;
      }
    }
    $genPointsFinal = max(0, $genPoints);
    $rawScore += $genPointsFinal;

    // 5. FIRST-CLASS CONFIDENCE (The Multiplier)
    // If AI is very confident (>= 0.8), don't penalize the score.
    // Otherwise, multiply by confidence to account for uncertainty.
    $appliedConfidence = ($confidence >= self::HIGH_CONFIDENCE_THRESHOLD) ? 1.0 : $confidence;
    $finalScore = (int) round($rawScore * $appliedConfidence);

    // 6. Hard Fail Threshold
    // If confidence is extremely low (< 0.4), force score to 0 to trigger manual review flag
    if ($confidence < self::LOW_CONFIDENCE_THRESHOLD) {
        $finalScore = 0;
    }

    Log::info("Scoring Detail [App ID: {$this->application->id}]:");
    Log::info("- Core Skills (Max 60): {$corePoints}");
    Log::info("- Optional Skills (Max 10): {$optPointsFinal}");
    Log::info("- Experience (Max 20): {$expScore}");
    Log::info("- General Reqs (Max 10): {$genPointsFinal}");
    Log::info("- Raw Total (Max 100): {$rawScore}");
    Log::info("- Confidence: {$confidence} (Applied: {$appliedConfidence})");
    Log::info("- FINAL SCORE: {$finalScore}");

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