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

  public $tries = 1; // Set to 1 to avoid repeated failed requests
  public $backoff = [10];
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
      $cvText = mb_substr($cvText, 0, 8000); 

      if (trim($cvText) === '') {
        Log::warning("Empty CV text for Application ID: " . $this->application->id . " (possibly image-based PDF).");
        throw new \LogicException("Failed to extract text from CV (possibly a scanned/image-based PDF). Please review manually.");
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

      // Low temperature for deterministic, structured extraction
      $rawResult = $groq->chat($messages, 0.1);
      Log::info("Groq Raw Result: " . json_encode($rawResult));

      if (!$rawResult) {
        throw new \RuntimeException("Groq AI returned no valid response. Possible network or API issue.");
      }

      // Validate & sanitize AI JSON structure before processing
      $result = $this->validateAiResponse($rawResult);

      // Score & persist result based on Job constraints
      $calculatedScore = $this->calculateScore($result, $job);
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

      // Preferred/Bonus
      if (count($skillsFound['preferred']) > 0) {
          $pros[] = "Possesses preferred skills (" . implode(', ', $skillsFound['preferred']) . ").";
      }
      if (count($skillsFound['bonus']) > 0) {
          $pros[] = "Has bonus qualifications in " . implode(', ', $skillsFound['bonus']) . ".";
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
    $prefSkills = is_array($job->preferred_skills) ? implode(', ', $job->preferred_skills) : '';
    $bonusSkills = is_array($job->bonus_skills) ? implode(', ', $job->bonus_skills) : '';
    $qualifications = strip_tags($job->qualifications ?? '');

    $userPrompt = "
        Role: {$job->title}

        We are strictly looking for the following categorized skills:
        REQUIRED SKILLS: {$reqSkills}
        PREFERRED SKILLS: {$prefSkills}
        BONUS SKILLS: {$bonusSkills}
        
        General Qualifications:
        {$qualifications}

        CV Content:
        {$cvText}

        Evaluation Rules:
        1. Parse the CV and identify which of the EXACT REQUIRED, PREFERRED, and BONUS skills are explicitly or implicitly present. Use synonyms intelligently (e.g. 'React.js' counts for 'React').
        2. Extract any other relevant technical skills into 'other_technical_skills'.
        3. Evaluate if the candidate meets the 'General Qualifications'. Provide a short label for each rule (e.g. 'Willing to work on-site') and state 'is_met': true/false.
        4. Accurately extract the candidate's total years of professional experience (decimals allowed).
        5. Extract their latest/highest education degree and major.
        6. Provide 2-3 qualitative 'key_strengths'. Focus on specific expertise, software mastery, or soft skills (e.g., 'Expertise in building scalable microservices' or 'Strong background in Financial Systems') instead of just job titles.

        Format JSON strictly like this:
        {
          \"skills_found\": {
            \"required\": [\"list of REQUIRED skills found in CV\"],
            \"preferred\": [\"list of PREFERRED skills found in CV\"],
            \"bonus\": [\"list of BONUS skills found in CV\"]
          },
          \"other_technical_skills\": [\"list of other tools/frameworks\"],
          \"general_requirements_analysis\": [
            {
              \"requirement\": \"Short English/Indo Label\",
              \"is_met\": true
            }
          ],
          \"experience_years\": 0.0,
          \"education\": \"Degree and major\",
          \"key_strengths\": [\"Descriptive strength 1\", \"Descriptive strength 2\"]
        }
    ";

    return [
      ['role' => 'system', 'content' => $systemMessage],
      ['role' => 'user',   'content' => $userPrompt],
    ];
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

    return [
      'skills_found' => [
          'required'  => is_array($skills['required'] ?? null) ? array_map(fn($val) => is_array($val) ? json_encode($val) : (string)$val, $skills['required']) : [],
          'preferred' => is_array($skills['preferred'] ?? null) ? array_map(fn($val) => is_array($val) ? json_encode($val) : (string)$val, $skills['preferred']) : [],
          'bonus'     => is_array($skills['bonus'] ?? null) ? array_map(fn($val) => is_array($val) ? json_encode($val) : (string)$val, $skills['bonus']) : [],
      ],
      'other_technical_skills'        => is_array($result['other_technical_skills'] ?? null) ? array_map(fn($val) => is_array($val) ? json_encode($val) : (string)$val, $result['other_technical_skills']) : [],
      'general_requirements_analysis' => $sanitizedReqs,
      'experience_years'              => is_numeric($result['experience_years'] ?? null) ? (float) $result['experience_years'] : 0.0,
      'education'                     => is_string($result['education'] ?? null) ? trim($result['education']) : '',
      'key_strengths'                 => is_array($result['key_strengths'] ?? null) ? array_map(fn($val) => is_array($val) ? json_encode($val) : (string)$val, $result['key_strengths']) : [],
    ];
  }

  protected function calculateScore(array $extractedData, \App\Models\JobVacancy $job): int {
    $score = 0;
    $expYears = $extractedData['experience_years'] ?? 0;

    $reqSkills  = is_array($job->required_skills)  ? $job->required_skills  : [];
    $prefSkills = is_array($job->preferred_skills) ? $job->preferred_skills : [];
    $bonusSkills = is_array($job->bonus_skills)    ? $job->bonus_skills     : [];

    $reqCount   = count($reqSkills);
    $prefCount  = count($prefSkills);
    $bonusCount = count($bonusSkills);

    $foundReq   = count($extractedData['skills_found']['required']  ?? []);
    $foundPref  = count($extractedData['skills_found']['preferred'] ?? []);
    $foundBonus = count($extractedData['skills_found']['bonus']     ?? []);

    // 1. Core Required Skills (Max 60 points)
    if ($reqCount > 0) {
        $matchRatio = $foundReq / $reqCount;
        $score += (int) round($matchRatio * 60);
    }

    // 2. Preferred & Bonus Skills (Max 10 points)
    $prefRatio  = $prefCount  > 0 ? $foundPref  / $prefCount  : 0;
    $bonusRatio = $bonusCount > 0 ? $foundBonus / $bonusCount : 0;
    $optPoints  = ($prefRatio * 6) + ($bonusRatio * 4);
    $score += (int) min(10, round($optPoints));

    // 3. Experience (Max 20 points)
    $expScore = match (true) {
        $expYears >= 5 => 20,
        $expYears >= 4 => 18,
        $expYears >= 3 => 16,
        $expYears >= 2 => 13,
        $expYears >= 1 => 8,
        $expYears > 0  => 4,
        default        => 0,
    };
    $score += $expScore;

    // 4. General Requirements & Education (Max 10 points)
    $genReqs   = $extractedData['general_requirements_analysis'] ?? [];
    $genPoints = 10;
    foreach ($genReqs as $item) {
      $reqTextLower = strtolower($item['requirement'] ?? '');
      $isMet        = $item['is_met'] ?? true;

      // Skip fresh graduate requirement if candidate has experience
      if (str_contains($reqTextLower, 'fresh') && $expYears >= 1) {
        $isMet = true;
      }

      if (!$isMet) {
        $genPoints -= 3;
      }
    }
    $score += max(0, $genPoints);

    // Safeguard bounds
    return max(0, min(100, $score));
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