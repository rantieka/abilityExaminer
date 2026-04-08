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

  const max_req_score = 40;
  const max_exp_score = 35;
  const max_edu_score = 5;
  const max_skill_score = 15;
  const advanced_skill_keywords = [
    'aws', 'gcp', 'azure', 'docker', 'kubernetes', 'k8s', 'ci/cd', 'tdd',
    'redis', 'elasticsearch', 'kafka', 'rabbitmq', 'microservices',
    'system design', 'architecture', 'jenkins', 'terraform', 'ansible',
    'graphql', 'web rtc', 'grpc', 'sidekiq', 'devops', 'machine learning'
  ];

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

      $qualifications = strip_tags($job->qualifications);

      // Send to AI for structured data extraction
      $messages = $this->buildPrompt($job->title, $qualifications, $anonymizedCvText);

      // Low temperature for deterministic, structured extraction
      $rawResult = $groq->chat($messages, 0.1);
      Log::info("Groq Raw Result: " . json_encode($rawResult));

      if (!$rawResult) {
        // No response = infrastructure/network/API issue → rethrow for potential retry
        throw new \RuntimeException("Groq AI returned no valid response. Possible network or API issue.");
      }

      // Validate & sanitize AI JSON structure before processing
      $result = $this->validateAiResponse($rawResult);

      // Score & persist result
      $calculatedScore = $this->calculateScore($result);
      Log::info("Calculated PHP Score: {$calculatedScore}");

      // Build human-readable summary from the requirements analysis
      $reqAnalysis = $result['requirements_analysis'];
      $pros        = [];
      $cons        = [];

      foreach ($reqAnalysis as $item) {
        if ($item['is_met'] ?? false) {
          $pros[] = $item['requirement'];
        } else {
          $cons[] = $item['requirement'];
        }
      }

      $summary = "Meets " . count($pros) . " of " . count($reqAnalysis) . " required qualifications. "
                     . "Actual experience: " . $result['experience_years'] . " year(s).";

      $this->application->update([
        'ai_score'    => $calculatedScore,
        'ai_analysis' => [
          'summary'        => $summary,
          'pros'           => $pros,
          'cons'           => $cons,
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

  /**
   * Build the structured chat messages array for the AI extraction request.
   * Technical instructions are in English for better LLM compliance.
   * Job qualifications and CV text remain in their original language.
   *
   * @return array<int, array{role: string, content: string}>
   */
  protected function buildPrompt(string $jobTitle, string $qualifications, string $cvText): array {
    $systemMessage = 'You are a strict CV Data Extractor. '
      . 'Your only task is to extract structured facts from a candidate CV against a list of job qualifications. '
      . 'You must output valid JSON only. Never add scores, opinions, or explanations.';

    $userPrompt = "
        ## Job Position
        Title: {$jobTitle}

        ## Required Qualifications (from job posting)
        {$qualifications}

        ## Candidate CV Text (Anonymized)
        {$cvText}

        ## Extraction Rules
        1. DO NOT assign any score. Extract boolean facts only.
        2. Break down job qualifications into individual requirements.
          - Keep compound requirements as one (e.g. \"PHP or Ruby\" = 1 requirement).
        3. Evaluate each requirement strictly based on explicit evidence in the CV.
          - Do NOT assume or infer.
        4. Extract:
          - All technical skills mentioned
          - Total years of relevant experience (0 if none)
          - Highest education level and field
        5. Match technologies by meaning, not exact wording
          (e.g. CI = CodeIgniter, RoR = Ruby on Rails, JS = JavaScript).
        6. Classify each requirement:
          - Soft skill → set \"is_soft_skill\": true and assume \"is_met\": true unless contradicted
          - Technical skill → set \"is_soft_skill\": false and evaluate strictly
        7. For \"Fresh Graduate\":
          - Set \"is_met\": false if experience_years > 1
        8. ALWAYS include all job requirements in the output.
          - Never skip any requirement

        ## Output Format
        Respond with valid JSON only. No explanation, no markdown, no extra text.
        {
          \"candidate_skills\": [\"list of all technical skills found in CV\"],
          \"requirements_analysis\": [
            {
              \"requirement\": \"exact qualification text from the job posting\",
              \"is_met\": true,
              \"is_soft_skill\": false
            },
            {
              \"requirement\": \"soft skill requirement example\",
              \"is_met\": true,
              \"is_soft_skill\": true
            }
          ],
          \"experience_years\": 2.5,
          \"education\": \"Degree name and field of study\"
        }
    ";

    return [
      ['role' => 'system', 'content' => $systemMessage],
      ['role' => 'user',   'content' => $userPrompt],
    ];
  }

  /**
   * Validate the AI response structure and sanitize all fields.
   * Returns a guaranteed-safe array with all expected keys.
   * Logs warnings for any structural anomalies without throwing.
   *
   * @throws \LogicException if the response is critically malformed (e.g. wrong root type)
   */
  protected function validateAiResponse(array $result): array
  {
    // Requirements analysis
    $reqAnalysis = $result['requirements_analysis'] ?? [];

    if (!is_array($reqAnalysis)) {
      Log::warning("AI response: 'requirements_analysis' is not an array. Defaulting to empty.");
      $reqAnalysis = [];
    }

    $sanitizedReqs = [];
    foreach ($reqAnalysis as $index => $item) {
      if (!is_array($item)) {
        Log::warning("AI response: requirements_analysis[{$index}] is not an object. Skipping.");
        continue;
      }
      $isSoftSkill = (bool) ($item['is_soft_skill'] ?? false);
      $sanitizedReqs[] = [
        'requirement'  => is_string($item['requirement'] ?? null)
          ? trim($item['requirement'])
          : 'Unknown requirement',
        'is_met'       => (bool) ($item['is_met'] ?? false),
        'is_soft_skill' => $isSoftSkill,
      ];
    }

    // Candidate skills
    $skills = $result['candidate_skills'] ?? [];

    if (!is_array($skills)) {
      Log::warning("AI response: 'candidate_skills' is not an array. Defaulting to empty.");
      $skills = [];
    }

    $skills = array_values(array_filter($skills, fn($s) => is_string($s) && !empty(trim($s))));

    // Experience years
    $expYears = $result['experience_years'] ?? 0;

    if (!is_numeric($expYears)) {
      Log::warning("AI response: 'experience_years' is not numeric (got: " . json_encode($expYears) . "). Defaulting to 0.");
      $expYears = 0;
    }

    // Education
    $education = $result['education'] ?? '';

    if (!is_string($education)) {
      Log::warning("AI response: 'education' is not a string. Defaulting to empty.");
      $education = '';
    }

    return [
      'requirements_analysis' => $sanitizedReqs,
      'candidate_skills'      => $skills,
      'experience_years'      => (float) $expYears,
      'education'             => trim($education),
    ];
  }

  /**
   * Calculate CV score strictly inside PHP based on AI-extracted data.
   *
   * Score Breakdown:
   *  - Requirements Met : max 40 points (hard/technical reqs only)
   *  - Experience Years : max 35 points (tiered, proportional)
   *  - Education Level  : max 5 points
   *  - Skill Depth      : max 15 points (breadth of technical skills)
   *  - Penalty          :  -5 points if CV has no detectable data at all
   */
  protected function calculateScore(array $extractedData): int {
    $score = 0;

    // Extract experience early so we can use it to override requirements (e.g. Fresh Graduate)
    $expYears = floatval($extractedData['experience_years'] ?? 0);

    // Requirements Met Score (Max 40 points)
    $reqAnalysis     = $extractedData['requirements_analysis'] ?? [];
    $candidateSkills = $extractedData['candidate_skills'] ?? [];

    $hardReqs = array_filter($reqAnalysis, fn($item) => !($item['is_soft_skill'] ?? false));
    $reqCount = count($hardReqs);

    if ($reqCount > 0) {
      $metCount = 0;
      foreach ($hardReqs as $item) {
        $reqTextLower = strtolower($item['requirement'] ?? '');
        
        // Ignore "Fresh Graduate" failing if candidate has more than 1 year of experience.
        $freshKeywords = ['fresh grad', 'fresh graduate', 'fresh graduated'];
        $isFreshReq = collect($freshKeywords)->contains(fn($kw) => str_contains($reqTextLower, $kw));
        if ($isFreshReq && $expYears > 1) {
          $metCount++;
          continue;
        }

        if (($item['is_met'] ?? false) === true) {
          $metCount++;
        }
      }
      $matchRatio = $metCount / $reqCount;
      $score += round($matchRatio * self::max_req_score);
      Log::info("Requirements scoring: {$metCount}/{$reqCount} hard requirements met (soft skills excluded).");
    } else {
      // use skill count as a rough estimate (capped at max req points)
      $score += min(self::max_req_score, count($candidateSkills) * 5);
    }

    // Experience Score (Max 35 points) — Proportional & Tiered
    $expScore = match (true) {
      $expYears >= 3 => self::max_exp_score,
      $expYears >= 1 => 25,
      $expYears > 0  => 15,
      default        => 5, 
    };
    $score += $expScore;

    // Education Score (Max 5 points)
    $education = strtolower($extractedData['education'] ?? '');
    $eduScore = 0;

    if (str_contains($education, 's3') || str_contains($education, 'doktor') || str_contains($education, 'phd')) {
      $eduScore = self::max_edu_score;
    } elseif (str_contains($education, 's2') || str_contains($education, 'magister') || str_contains($education, 'master')) {
      $eduScore = 4;
    } elseif (str_contains($education, 's1') || str_contains($education, 'sarjana') || str_contains($education, 'bachelor') || (str_contains($education, 'd4'))) {
      $eduScore = 3;
    } elseif (str_contains($education, 'd3')) {
      $eduScore = 2;
    } elseif (str_contains($education, 'd2') || str_contains($education, 'd1')) {
      $eduScore = 1;
    } elseif (!empty($education)) {
      $eduScore = 1;
    }
    $score += $eduScore;

    // Skill Depth Score (Max 15 points) -> Weighted Evaluation
    $weightedSkillCount = 0;
    
    // Normalize and remove duplicates to prevent double-counting the same skill
    $normalizedSkills = array_map(fn($s) => strtolower(trim($s)), $candidateSkills);
    $uniqueSkills = array_unique($normalizedSkills);

    foreach ($uniqueSkills as $skillLower) {
      $isAdvanced = false;
      foreach (self::advanced_skill_keywords as $advIdx) {
        if (str_contains($skillLower, $advIdx)) {
          $isAdvanced = true;
          break;
        }
      }
      $weightedSkillCount += $isAdvanced ? 2 : 1;
    }

    $skillDepthScore = match (true) {
      $weightedSkillCount >= 25 => self::max_skill_score, // Expert profile (many advanced skills)
      $weightedSkillCount >= 15 => 12, // Advanced technical profile
      $weightedSkillCount >= 8 => 9,  // Strong technical profile
      $weightedSkillCount >= 4  => 5,  // Moderate skill breadth
      $weightedSkillCount >= 2  => 2,  // Basic skill set detected
      default                   => 0,
    };

    $score += $skillDepthScore;
    Log::info("Skill depth score: {$skillDepthScore} pts (Weighted Count Score: {$weightedSkillCount} from " . count($candidateSkills) . " raw skills).");

    // Penalty — only if the CV yields absolutely no extractable data
    $hasNoData = empty($reqAnalysis) && empty($candidateSkills) && $expYears == 0 && empty($education);
    if ($hasNoData) {
      $score -= 5; // Reduced penalty: CV provided no evaluable information
    }

    // Clamp score between 0 and 100
    return max(0, min(100, (int) round($score)));
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