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

      // Add a 60-second delay to prevent Groq TPM (Tokens Per Minute) Rate Limit
      sleep(60);

      // Low temperature for deterministic, structured extraction
      $rawResult = $groq->chat($messages, 0.1);
      Log::info("Groq Raw Result [App ID: {$this->application->id}]:\n" . json_encode($rawResult, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));

      if (!$rawResult) {
        throw new \RuntimeException("Groq AI returned no valid response. Possible network or API issue.");
      }

      // Validate & sanitize AI JSON structure before processing
      $result = $this->validateAiResponse($rawResult);

      // FALLBACK: Scan CV text for critical skills Groq likely missed
      $result = $this->extractCriticalSkillsFromText($result, $anonymizedCvText);

      // NEW HYBRID CALCULATION
      // 1. Calculate refined experience years from structured history
      $calculatedExp = $this->calculateRefinedExperience($result['work_experiences'] ?? []);
      $result['experience_years'] = $calculatedExp;
      Log::info("Refined Experience [App ID: {$this->application->id}]: {$calculatedExp} years");

      // 2. Calculate score AND populate skills_found & other_technical_skills
      // NOTE: calculateScore() modifies $result by reference
      $calculatedScore = $this->calculateScore($result, $job);
      Log::info("Skills Found [App ID: {$this->application->id}]:\n" . json_encode($result['skills_found'] ?? [], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
      Log::info("Calculated PHP Score: {$calculatedScore}");

      // Build human-readable summary from the requirements analysis
      // NOTE: skills_found & other_technical_skills are populated by calculateScore() above
      $skillsFound = $result['skills_found'] ?? ['required' => [], 'preferred' => [], 'bonus' => []];
      $otherSkills = $result['other_technical_skills'] ?? [];
      $generalReqs = $result['general_requirements_analysis'];
      $expYearsRaw = $result['experience_years'];
      $expYears    = (float) $expYearsRaw;
      $expLevel    = $this->getExperienceLevel($expYears);
      $pros = [];
      $cons = [];

      // Required Skills
      $allRequired = $job->required_skills ?? [];
      $foundRequiredLower = array_map('strtolower', $skillsFound['required']);
      $allRequiredNorm = array_map(fn($s) => $this->normalizeSkill($s), $allRequired);
      $missingRequired = [];
      foreach ($allRequiredNorm as $index => $req) {
        if (!in_array($req, $foundRequiredLower)) {
          $missingRequired[] = $allRequired[$index]; // keep original name for display
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

      // Experience & Education - use text-based description without decimal-to-months conversion
      Log::info("DEBUG displayExpPros [App ID: {$this->application->id}]: expYearsRaw={$expYearsRaw}, expYears={$expYears}");
      $displayExpPros = ($expYears == 0)
          ? "no relevant experience"
          : ($expYears < 1 ? "less than 1 year" : (($expYears == 1) ? "1 year" : round($expYears, 1) . " years"));

      if ($expYears >= 5) {
        $pros[] = "Extensive professional background with {$displayExpPros} of experience.";
      } elseif ($expYears >= 2) {
        $pros[] = "Solid career foundation with {$displayExpPros} in the industry.";
      } elseif ($expYears > 0) {
        $pros[] = "Possesses practical work experience ({$displayExpPros}).";
      }
      
      if (!empty($result['education_level'])) {
        $pros[] = "Academic background: {$result['education_level']} ". ($result['education_major'] ?? '') . ".";
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
      $displayExp = ($expFormatted == 0)
        ? "no relevant experience"
        : ($expFormatted < 1 ? "less than 1 year" : (($expFormatted == 1) ? "1 year" : round($expFormatted, 1) . " years"));

      $summary = "Identified " . count($skillsFound['required']) . " required skills and " . count($skillsFound['preferred']) . " preferred skills with " . $displayExp . ".";

      // Determine screening label based on score (2-class for C4.5)
      $screeningLabel = $calculatedScore >= 60 ? 'suitable' : 'not_suitable';

      $this->application->update([
        'ai_score'          => $calculatedScore,
        'experience_level'  => $expLevel,
        'screening_label'   => $screeningLabel,
        'ai_analysis'       => [
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
      2. EXPERIENCE: Extract work history as a structured list. Include: company, role, start_date, end_date (Format: YYYY-MM or YYYY, use 'Present' if current).
         Identify if the role is 'relevant' to software development/IT based on the job description.
         CRITICAL: Do NOT include education periods, university studies, or school projects in this list.
         CRITICAL: Only include actual paid employment or formal internship with a company. Sertifikat, Certificate, Incubator, Bootcamp, Award, Scholarship, Workshop are NOT work experience.
      3. EDUCATION: Extract highest education level (SMK/D3/D4/S1/etc) and major.
      4. REQUIREMENTS: Check these specific labels from the CV:
         - {$qualifications}
         Determine if they are met based ONLY on the CV text.

      ## Format JSON strictly:
      {
        \"all_extracted_skills\": [],
        \"general_requirements_analysis\": [{\"requirement\": \"label\", \"is_met\": false}],
        \"work_experiences\": [
          {
            \"company\": \"\",
            \"role\": \"\",
            \"start_date\": \"\",
            \"end_date\": \"\",
            \"is_relevant\": true
          }
        ],
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
    $skill = trim($skill);

    // Handle CI/CD variations (ci/cd, ci-cd, ci_cd, cicd, etc.) early to normalize to 'cicd'
    if (preg_match('/ci[\/\-\s_]?cd/i', $skill)) {
      $skill = preg_replace('/ci[\/\-\s_]?cd/i', 'cicd', $skill);
    }

    $skill = strtolower($skill);
    $skill = preg_replace('/(\s*[\d\.]+\.\*|\s*[\d\.]+|[\d\.]+(\.\*)?)$/', '', $skill);

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

      // PHP Frameworks — 'ci' now safe to map because CI/CD variants are handled early
      'codeigniter'  => 'codeigniter',
      'ci'           => 'codeigniter',
      'ci4'          => 'codeigniter',
      'codeigniter4' => 'codeigniter',
      'codeigniter3' => 'codeigniter',
      'ci3'          => 'codeigniter',
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

      ' ' => '',
      '-' => '',
      '_' => '',
      '(' => '',
      ')' => '',
      '.' => '',
      ',' => '',
      '/' => '',
    ];
    $skill = str_replace(array_keys($replacements), array_values($replacements), $skill);

    // Normalize cloud provider shorthand (EC2, S3, ES6, ES5, etc.)
    if (preg_match('/^(ec2|s3|es6|es5|es7)$/', $skill)) {
      return $skill; // keep as-is, length > 1 so skillMatches won't reject
    }

    // Drop any remaining single-char results (noisy — e.g. "S" from malformed "S3" extraction)
    if (strlen($skill) <= 1) {
      return '';
    }

    return $skill;
  }

  /**
   * Fallback: Scan raw CV text for technical skills that Groq likely missed.
   */
  protected function extractCriticalSkillsFromText(array $result, string $cvText): array {
    $existingLower = array_map(fn($s) => strtolower(trim($s)), $result['all_extracted_skills'] ?? []);

    // list skills that often missed by AI
    // Sort by length DESC to ensure multi-word keywords (e.g. "machine learning") match first
    $keywords = [
      'grape api' => 'rest-api', 'grape' => 'rest-api', 'api framework' => 'rest-api',
      'restful api' => 'rest-api', 'rest api' => 'rest-api', 'rest' => 'rest-api', 'restful' => 'rest-api',
      'postgresql' => 'postgres', 'postgre sql' => 'postgres',
      'mysql' => 'mysql', 'mariadb' => 'mysql', 'mongodb' => 'mongodb',
      'redis' => 'redis', 'elasticsearch' => 'elasticsearch',
      'docker' => 'docker', 'kubernetes' => 'kubernetes', 'k8s' => 'kubernetes',
      'aws' => 'aws', 'gcp' => 'gcp', 'azure' => 'azure',
      'python' => 'python', 'flutter' => 'flutter', 'react native' => 'reactnative',
      'git' => 'git', 'cicd' => 'cicd', 'ci/cd' => 'cicd', 'postman' => 'postman', 'machine learning' => 'ml', 'deep learning' => 'deep-learning'
    ];
    uksort($keywords, fn($a, $b) => strlen($b) - strlen($a));

    $found = [];
    // fixed-length lookbehind chains (PCRE2 requirement: each branch must be same length).
    $negLookbehinds = [
      '(?<!no\s)',    // preceded by "no "
      '(?<!not\s)',
      '(?<!tanpa\s)',
      '(?<!bukan\s)',
      '(?<!tidak\s)',
      '(?<!belum\s)'
    ];
    $negLookbehind = implode('', $negLookbehinds);

    foreach ($keywords as $key => $normalized) {
      $escapedKey = preg_quote($key, '/');
      $pattern = '/' . $negLookbehind . '\b' . $escapedKey . '\b/i';

      if (@preg_match($pattern, $cvText)) {
        // Use fuzzy matching to avoid semantic duplicates (e.g. "rest" exists → don't add "rest-api")
        $alreadyExists = false;
        foreach ($existingLower as $existingSkill) {
          if ($this->skillMatches($normalized, $existingSkill)) {
            $alreadyExists = true;
            break;
          }
        }
        if (!$alreadyExists) {
          $found[] = $normalized;
        }
      }
    }

    if (!empty($found)) {
      $result['all_extracted_skills'] = array_unique(array_merge($result['all_extracted_skills'] ?? [], $found));
    }

    return $result;
  }

  /**
   * Fuzzy match two skills — case-insensitive substring match.
   * Returns true if one skill is contained within the other (or they're identical).
   * Includes special-case guards to prevent false positives (e.g. "s" matching "mysql").
   */
  protected function skillMatches(string $normalizedJob, string $normalizedFound): bool {
    if ($normalizedJob === $normalizedFound) return true;

    // Length guard: Reject single-char or empty strings (e.g. "s" from "S3")
    if (strlen($normalizedFound) <= 1 || strlen($normalizedJob) <= 1) return false;

    // Special cases: Prevent "Java" from matching "JavaScript" or "SQL" from matching "MySQL"
    if ($normalizedJob === 'java' && $normalizedFound === 'js') return false;
    if ($normalizedJob === 'sql' && in_array($normalizedFound, ['mysql', 'postgres', 'sqlserver'])) return false;
    // Reverse check: Specific job tool should not match generic 'sql'
    if (in_array($normalizedJob, ['mysql', 'postgres']) && $normalizedFound === 'sql') return false;

    // Semantic equivalence: responsive design / web design
    $designEquivalents = ['bootstrap', 'tailwindcss', 'tailwind', 'css', 'flexbox', 'grid', 'mediaquery', 'mediaqueries'];
    $isJobDesign = $normalizedJob === 'responsivedesign' || $normalizedJob === 'webdesign';
    if ($isJobDesign && in_array($normalizedFound, $designEquivalents)) {
      return true;
    }

    // Otherwise, allow fuzzy matching (e.g., "Git" matches "Git (Expert)")
    return str_contains($normalizedJob, $normalizedFound)
      || str_contains($normalizedFound, $normalizedJob);
  }

  /**
   * Convert numeric experience years to categorical level.
   * Used for C4.5 training features.
   */
  protected function getExperienceLevel(float $expYears): string {
    $normalizedYear = floor($expYears);
    return match (true) {
      $normalizedYear == 0   => 'fresher',
      $normalizedYear <= 1  => 'newcomer',
      $normalizedYear <= 2  => 'junior',
      $normalizedYear <= 5  => 'early_career',
      $normalizedYear <= 10 => 'mid_level',
      default                => 'senior',
    };
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
      'work_experiences'              => is_array($result['work_experiences'] ?? null) ? $result['work_experiences'] : [],
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
    $expYears   = floor($expYears); // Normalize to kill decimal noise (2.0 vs 2.5 → both 2)
    $confidence = (float)($extractedData['confidence'] ?? 0.5);
    $genReqs    = $extractedData['general_requirements_analysis'] ?? [];

    // Guard
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
    $allFoundNorm = array_values(array_filter($allFoundNorm)); // re-index and remove empty

    // DEBUG: Log normalization details for skill matching
    Log::info("allFoundNorm [App ID: {$this->application->id}]: " . json_encode($allFoundNorm));
    Log::info("reqSkillsNorm [App ID: {$this->application->id}]: " . json_encode($reqSkillsNorm));
    Log::info("prefSkillsNorm [App ID: {$this->application->id}]: " . json_encode($prefSkillsNorm));
    Log::info("bonusSkillsNorm [App ID: {$this->application->id}]: " . json_encode($bonusSkillsNorm));

    // Fuzzy intersection using substring match (case-insensitive)
    $matchedReq   = [];
    $matchedPref  = [];
    $matchedBonus = [];
    foreach ($reqSkillsNorm as $req) {
      $didMatch = false;
      $matchWhy = null;
      foreach ($allFoundNorm as $found) {
        if ($this->skillMatches($req, $found)) {
          $matchedReq[] = $req;
          $didMatch = true;
          $matchWhy = $found;
          break;
        }
      }
      Log::info("reqMatch [App ID: {$this->application->id}]: '{$req}' → " . ($didMatch ? "MATCHED (vs '{$matchWhy}')" : "NO MATCH"));
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

    // Experience (Max 20 points) - Check if this is specifically a Fresh Graduate targeted job
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

    Log::info("Scoring Detail [{$this->application->id}]:");
    Log::info("Core Skills (Max 60): {$corePoints}");
    Log::info("Optional Skills (Max 10): {$optPointsFinal}");
    Log::info("Experience (Max 20): {$expScore}");
    Log::info("General Reqs (Max 10): {$genPointsFinal}");
    Log::info("Raw Total (Max 100): {$rawScore}");
    Log::info("Confidence: {$confidence} (Applied: {$appliedConfidence})");
    Log::info("Final ScoreE: {$finalScore}");

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

  /**
   * REFINED EXPERIENCE CALCULATION
   * Steps: Validate -> Sort -> Merge Overlaps -> Weighting -> Final Years
   */
  protected function calculateRefinedExperience(array $experiences): float
  {
    if (empty($experiences)) return 0.0;

    Log::info("DEBUG ExpCalc Input [App ID: {$this->application->id}]: " . json_encode(array_map(fn($e) => [
      'role' => $e['role'] ?? '',
      'company' => $e['company'] ?? '',
      'start' => $e['start_date'] ?? '',
      'end' => $e['end_date'] ?? '',
      'is_relevant' => $e['is_relevant'] ?? false
    ], $experiences)));

    $intervals = [];
    $currentDate = now();

    foreach ($experiences as $exp) {
      try {
        $role = strtolower($exp['role'] ?? '');
        $company = strtolower($exp['company'] ?? '');
        
        // Skip if it looks like a degree or university status
        $academicKeywords = ['student', 'mahasiswa', 'university', 'universitas', 'school', 'sekolah', 'college', 'degree', 'undergraduate', 'smk', 'sma', 'smp', 'sd', 'smkn'];
        $isAcademic = false;
        foreach ($academicKeywords as $kw) {
          $pattern = "/\b" . preg_quote($kw, '/') . "\b/i";
          if (preg_match($pattern, $role) || preg_match($pattern, $company)) {
            $isAcademic = true;
            break;
          }
        }
        if ($isAcademic) {
          Log::info("DEBUG ExpCalc [App ID: {$this->application->id}]: SKIPPED (academic) -> {$role} @ {$company}");
          continue;
        }

        // Skip suspicious non-employment entries (AI might wrongly parse these as work experience)
        $suspiciousKeywords = ['incubator', 'bootcamp', 'sertifikat', 'certificate',
                               'award', 'penghargaan', 'training program', 'digital talent',
                               'scholarship', 'fellowship', 'workshop', 'seminar', 'pelatihan',
                               'admin online', 'staff administrasi', 'admin media sosial',
                               'admin sosial media', 'admin gudang', 'admin marketplace',
                               'admin toko', 'admin penjualan', 'admin sales', 'customer service'];
        $isSuspicious = false;
        $suspiciousReason = null;
        foreach ($suspiciousKeywords as $kw) {
          if (str_contains($role, $kw) || str_contains($company, $kw)) {
            $isSuspicious = true;
            $suspiciousReason = $kw;
            break;
          }
        }
        if ($isSuspicious) {
          Log::info("DEBUG ExpCalc [App ID: {$this->application->id}]: SKIPPED (suspicious: '{$suspiciousReason}') -> {$role} @ {$company}");
          continue;
        }

        $startStr = $exp['start_date'] ?? null;
        $endStr   = $exp['end_date'] ?? 'Present';

        $start = $this->parseFlexibleDate($startStr);
        $end   = $this->parseFlexibleDate($endStr);

        $isRelevant = (bool) ($exp['is_relevant'] ?? false);

        // If start can't be parsed — decide what to do based on relevance
        if (!$start) {
          if ($isRelevant) {
            // Dates unclear but role is relevant → estimate minimum 6 months
            $fallbackStart = $currentDate->copy()->subMonths(6);
            $fallbackEnd   = $currentDate;
            Log::info("DEBUG ExpCalc [App ID: {$this->application->id}]: ESTIMATED 0.5yr (dates unknown) -> {$role} @ {$company}");
            $intervals[] = [
              'start'   => $fallbackStart,
              'end'     => $fallbackEnd,
              'weight'  => 0.5, // lower than fully-known experiences (1.0)
              'role'    => $role,
              'company' => $company,
            ];
          }
          continue;
        }
        if (!$end) $end = $currentDate;

        // Ensure start is before end
        if ($start->gt($end)) continue;

        if (!$isRelevant) {
          Log::info("DEBUG ExpCalc [App ID: {$this->application->id}]: SKIPPED (is_relevant=false) -> {$role} @ {$company}");
          continue;
        }

        // Safety net: skip roles that are clearly NOT IT/Dev regardless of is_relevant
        $clearlyNonDev = ['sales', 'marketing', 'kasir', 'apotek', 'dokter', 'guru',
                          'driver', 'cleaning service', 'security', 'security guard'];
        foreach ($clearlyNonDev as $kw) {
          if (str_contains($role, $kw)) {
            Log::info("DEBUG ExpCalc [App ID: {$this->application->id}]: SKIPPED (clearly non-dev) -> {$role}");
            continue 2; // continue to next $exp in foreach
          }
        }

        // Passed both checks: is_relevant=true AND not clearly non-dev
        $weight = 1.0;
        Log::info("DEBUG ExpCalc Parse [App ID: {$this->application->id}]: {$role} @ {$company} | {$startStr} → {$endStr} | parsed: {$start->format('Y-m-d')} → {$end->format('Y-m-d')} | weight={$weight}");

        $intervals[] = [
          'start'   => $start,
          'end'     => $end,
          'weight'  => $weight,
          'role'    => $role,
          'company' => $company,
        ];
      } catch (\Exception $e) {
        continue;
      }
    }

    if (empty($intervals)) return 0.0;

    // Merge Overlap Logic
    // Sort intervals by start date
    usort($intervals, fn($a, $b) => $a['start']->timestamp <=> $b['start']->timestamp);

    $merged = [];
    if (!empty($intervals)) {
      $current = $intervals[0];
      
      for ($i = 1; $i < count($intervals); $i++) {
        $next = $intervals[$i];

        // Only merge if overlap AND weight is the same (0.01 epsilon for float compare)
        if ($next['start']->lte($current['end']) && abs($next['weight'] - $current['weight']) < 0.01) {
          Log::info("DEBUG ExpCalc Merge [App ID: {$this->application->id}]: MERGING {$next['role']} @ {$next['company']} INTO {$current['role']} @ {$current['company']} | overlap: {$next['start']->format('Y-m')} <= {$current['end']->format('Y-m')}");
          // Extend the current end if the next one is further
          if ($next['end']->gt($current['end'])) {
            $current['end'] = $next['end'];
          }
        } else {
          // Different weight = separate interval, don't merge
          $merged[] = $current;
          $current = $next;
        }
      }
      $merged[] = $current;
    }

    // Calculate Months & Final Years
    $totalMonths = 0;
    $debugDetails = [];
    foreach ($merged as $m) {
      $months = $m['start']->diffInMonths($m['end']) + 1; // +1 to include starting month
      $weighted = $months * $m['weight'];
      $totalMonths += $weighted;

      $startStr = $m['start']->format('Y-m');
      $endStr = $m['end']->format('Y-m');
      $roleStr = $m['role'];
      $weightVal = $m['weight'];

      $debugDetails[] = "{$roleStr}: {$startStr} -> {$endStr} (weight={$weightVal}, months={$months}, weighted={$weighted})";
    }

    $finalYears = round($totalMonths / 12, 1);
    $debugMsg = implode(' | ', $debugDetails) . " | totalMonths={$totalMonths}, finalYears={$finalYears}";
    Log::info("DEBUG ExpCalc [App ID: {$this->application->id}]: " . $debugMsg);

    // Cap at 15 years to avoid noise
    return min(15.0, (float) $finalYears);
  }

  /**
   * Helper to parse various date formats from AI
   */
  protected function parseFlexibleDate($dateStr): ?\Illuminate\Support\Carbon 
  {
    if (!$dateStr) return null;
    
    $dateStr = trim(strtolower($dateStr));
    if (in_array($dateStr, ['present', 'now', 'current', 'sekarang'])) {
      return now();
    }

    // Try YYYY-MM-DD
    if (preg_match('/^\d{4}-\d{1,2}-\d{1,2}$/', $dateStr)) {
      try { return \Illuminate\Support\Carbon::parse($dateStr)->startOfDay(); } catch (\Exception $e) {}
    }

    // Try YYYY-MM
    if (preg_match('/^\d{4}-\d{1,2}$/', $dateStr)) {
      try { return \Illuminate\Support\Carbon::createFromFormat('Y-m', $dateStr)->startOfMonth(); } catch (\Exception $e) {}
    }

    // Try YYYY
    if (preg_match('/^\d{4}$/', $dateStr)) {
      try { return \Illuminate\Support\Carbon::createFromFormat('Y', $dateStr)->startOfYear(); } catch (\Exception $e) {}
    }

    // Final fallback for any other readable format (e.g. "May 2023", "20-10-2022")
    try {
      return \Illuminate\Support\Carbon::parse($dateStr);
    } catch (\Exception $e) {
      return null;
    }

    return null;
  }
}
