<?php

namespace App\Jobs;

use App\Models\JobVacancy;
use App\Models\Question;
use App\Services\GroqService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class GenerateExamQuestions
{
  use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

  public $jobVacancy;
  public $timeout = 600;

  public function __construct(JobVacancy $jobVacancy)
  {
    $this->jobVacancy = $jobVacancy;
  }

  /**
   * Execute the job.
   */
  public function handle(GroqService $groq): void
  {
    try {
      // 1. Check existing master questions from database (seeded earlier)
      $existingKnowledgeCount = Question::where('job_vacancy_id', $this->jobVacancy->id)
        ->where('section', 'knowledge')
        ->count();

      $existingTechnicalCount = Question::where('job_vacancy_id', $this->jobVacancy->id)
        ->where('section', 'technical')
        ->count();

      // Target distribution for each section (Total 20)
      $target = 20;
      $distTarget = [
          'required'  => 14,
          'preferred' => 5,
          'bonus'     => 1
      ];

      // Part 1: Knowledge & Foundation
      while (true) {
        $counts = [
            'required'  => Question::where('job_vacancy_id', $this->jobVacancy->id)->where('section', 'knowledge')->where('skill_category', 'required')->count(),
            'preferred' => Question::where('job_vacancy_id', $this->jobVacancy->id)->where('section', 'knowledge')->where('skill_category', 'preferred')->count(),
            'bonus'     => Question::where('job_vacancy_id', $this->jobVacancy->id)->where('section', 'knowledge')->where('skill_category', 'bonus')->count(),
        ];
        
        $currentKnowledgeCount = array_sum($counts);
        $neededKnowledge = $target - $currentKnowledgeCount;
        if ($neededKnowledge <= 0) break;

        // Determine focus and how many more are needed for that specific category
        $focus = 'required';
        $neededForFocus = $distTarget['required'] - $counts['required'];

        if ($neededForFocus <= 0) {
          $focus = 'preferred';
          $neededForFocus = $distTarget['preferred'] - $counts['preferred'];
          
          if ($neededForFocus <= 0) {
            $focus = 'bonus';
            $neededForFocus = $distTarget['bonus'] - $counts['bonus'];
          }
        }

        // Safety break if somehow we have no focus needed but total not reached
        if ($neededForFocus <= 0) break;

        $existingTexts = Question::where('job_vacancy_id', $this->jobVacancy->id)
          ->where('section', 'knowledge')
          ->latest()
          ->limit(10)
          ->pluck('question_text')
          ->toArray();

        $batchSize = min(5, $neededForFocus, $neededKnowledge);
        $promptPart1 = $this->buildPart1Prompt($batchSize, $focus, $existingTexts);
        $this->generateBatch($groq, $promptPart1, 'knowledge', $batchSize, 8192, 'llama-3.3-70b-versatile');
        
        sleep(20);
      }

      // Part 2: Technical
      while (true) {
        $counts = [
            'required'  => Question::where('job_vacancy_id', $this->jobVacancy->id)->where('section', 'technical')->where('skill_category', 'required')->count(),
            'preferred' => Question::where('job_vacancy_id', $this->jobVacancy->id)->where('section', 'technical')->where('skill_category', 'preferred')->count(),
            'bonus'     => Question::where('job_vacancy_id', $this->jobVacancy->id)->where('section', 'technical')->where('skill_category', 'bonus')->count(),
        ];

        $currentTechnicalCount = array_sum($counts);
        $neededTechnical = $target - $currentTechnicalCount;
        if ($neededTechnical <= 0) break;

        // Determine focus and how many more are needed for that specific category
        $focus = 'required';
        $neededForFocus = $distTarget['required'] - $counts['required'];

        if ($neededForFocus <= 0) {
            $focus = 'preferred';
            $neededForFocus = $distTarget['preferred'] - $counts['preferred'];
            
            if ($neededForFocus <= 0) {
                $focus = 'bonus';
                $neededForFocus = $distTarget['bonus'] - $counts['bonus'];
            }
        }

        // Safety break
        if ($neededForFocus <= 0) break;

        $existingTexts = Question::where('job_vacancy_id', $this->jobVacancy->id)
          ->where('section', 'technical')
          ->latest()
          ->limit(10)
          ->pluck('question_text')
          ->toArray();

        $batchSize = min(5, $neededForFocus, $neededTechnical);
        $promptPart2 = $this->buildPart2TechPrompt($batchSize, $focus, $existingTexts);
        $this->generateBatch($groq, $promptPart2, 'technical', $batchSize, 8192, 'llama-3.3-70b-versatile');
        
        sleep(20);
      }

    } catch (\Exception $e) {
      Log::error("Failed to generate exam questions: " . $e->getMessage());
    }
  }

  /**
   * Determine question difficulty based on job title.
   */
  protected function determineDifficulty(string $level): array
  {
    $level = strtolower($level);

    if ($level === 'senior') {
      return [
        'distribution' => '2 Easy, 6 Medium, 12 Hard',
        'focus' => 'Advanced Knowledge, Architecture, System Scalability, Security, Advanced Optimization',
        'tech_focus' => 'Architecture, System Design, Advanced Debugging, Security'
      ];
    } elseif ($level === 'middle') {
      return [
        'distribution' => '6 Easy, 10 Medium, 4 Hard',
        'focus' => 'Industry standards, core concepts, common tools, working principles',
        'tech_focus' => 'Industry standard practices, framework usage, intermediate debugging'
      ];
    }

    // Default (Junior)
    return [
      'distribution' => '10 Easy, 8 Medium, 2 Hard',
      'focus' => 'Fundamental Understanding, Basic Syntax, Standard Tool Usage',
      'tech_focus' => 'Basic Syntax, Fundamental Logic, Simple Debugging'
    ];
  }

  /**
   * Build prompt for Knowledge & Foundation section.
   */
  protected function buildPart1Prompt(int $count, string $focusCategory, array $existingQuestions = []): string
  {
    $req = implode(', ', $this->jobVacancy->required_skills ?? []);
    $pref = implode(', ', $this->jobVacancy->preferred_skills ?? []);
    $bonus = implode(', ', $this->jobVacancy->bonus_skills ?? []);

    $diff = $this->determineDifficulty($this->jobVacancy->experience_level ?? 'junior');

    $context = "";
    if (!empty($existingQuestions)) {
      $context = "\nEXISTING QUESTIONS (DO NOT REPEAT THESE TOPICS):\n- " . implode("\n- ", $existingQuestions);
    }

    return "
      Generate EXACTLY {$count} multiple-choice questions for THEORETICAL KNOWLEDGE for the position: '{$this->jobVacancy->title}'.
      
      SKILL CONTEXT:
      - PRIMARY (Required): {$req} (Can be Easy, Medium, or Hard)
      - SECONDARY (Preferred): {$pref} (Should be Easy or Medium only)
      - BONUS SKILLS: {$bonus} (MUST be Easy only)
      
      FOCUS FOR THIS BATCH: Prioritize generating questions for '{$focusCategory}' skills.
      
      Qualification Description: " . strip_tags($this->jobVacancy->qualifications) . "
      {$context}

      RULES:
      1. LANGUAGE: All question text and options MUST be in Indonesian (Bahasa Indonesia).
      2. STRICTLY THEORY ONLY: ABSOLUTELY NO code snippets, NO variable assignment calculations (e.g., a=b), NO logic tracing, and NO technical symbols. Questions must focus on definitions, functions, concepts, and best practices.
      3. DIFFICULTY DISTRIBUTION: {$diff['distribution']}.
      4. MAPPING RULES: 
         - Required skills can be Easy, Medium, or Hard.
         - Preferred skills should be Easy or Medium.
         - Bonus skills MUST be Easy.
      5. CONTENT FOCUS: {$diff['focus']} related to the skills above.
      6. STRICT UNIQUENESS: EVERY question MUST cover a completely different topic or concept. DO NOT generate similar questions with slightly different wording. If a concept (e.g., Encapsulation, MVC, Middleware) is already in the EXISTING QUESTIONS list below, DO NOT generate another question about it. Focus on breadth of knowledge.
      7. RANDOMIZE CORRECT ANSWER: Distribute the correct answer letter (A, B, C, D) randomly across the set of questions. Do not bias towards 'A'.
      8. QUALITY OPTIONS: Ensure answer options are descriptive, professional, and clear. Avoid overly short or repetitive phrases. Each option should be a plausible but distinct explanation of the concept.
      
      JSON Format: {\"questions\": [{\"text\":\"Question text...\",\"options\":{\"A\":\"...\",\"B\":\"...\",\"C\":\"...\",\"D\":\"...\"},\"correct\":\"C\",\"difficulty\":\"easy\",\"category\":\"required/preferred/bonus\"}]}
      Output ONLY JSON. Exactly {$count} UNIQUE questions. No duplicates.";
  }

  /**
   * Build prompt for Technical & Analysis section.
   */
  protected function buildPart2TechPrompt(int $count, string $focusCategory, array $existingQuestions = []): string
  {
    $req = implode(', ', $this->jobVacancy->required_skills ?? []);
    $pref = implode(', ', $this->jobVacancy->preferred_skills ?? []);
    $bonus = implode(', ', $this->jobVacancy->bonus_skills ?? []);

    $diff = $this->determineDifficulty($this->jobVacancy->experience_level ?? 'junior');

    $context = "";
    if (!empty($existingQuestions)) {
      $context = "\nEXISTING QUESTIONS (DO NOT REPEAT THESE TOPICS/LOGIC):\n- " . implode("\n- ", $existingQuestions);
    }

    return "
      Generate EXACTLY {$count} multiple-choice questions for CODING & TECHNICAL ANALYSIS for the position: '{$this->jobVacancy->title}'.
      
      SKILLS TO TEST:
      - REQUIRED (Main Focus): {$req} (Easy, Medium, Hard)
      - PREFERRED: {$pref} (Easy or Medium only)
      - BONUS/OPTIONAL: {$bonus} (MUST be Easy only)
      
      FOCUS FOR THIS BATCH: Prioritize generating questions for '{$focusCategory}' skills.
      
      {$context}

      RULES:
      1. LANGUAGE: All question text and analysis MUST be in Indonesian (Bahasa Indonesia). Use original syntax for code snippets (e.g., PHP, JavaScript).
      2. CASE STUDY & LOGIC: Every question MUST include a code snippet wrapped in triple backticks (```) and focus on technical case studies, logic tracing, syntax analysis, bug detection, or predicting output.
      3. MATHEMATICAL ACCURACY: If the question involves calculations (e.g., discounts, taxes, loop iterations), you MUST double-check the math. Ensure the 'correct' answer exactly matches the result of the code snippet provided.
      4. DIFFICULTY DISTRIBUTION: {$diff['distribution']}.
      5. MAPPING RULES:
         - Required skills (Main Focus) can be any difficulty.
         - Preferred skills should be Easy or Medium.
         - Bonus skills MUST be Easy.
      6. CONTENT FOCUS: {$diff['tech_focus']}.
      7. STRICT UNIQUENESS: Ensure technical logic, bug types, and snippets are COMPLETELY DIFFERENT. DO NOT repeat the same logic pattern. If one question is about array filtering, make the next about data validation, session handling, or regex—avoid repetition of logic categories.
      8. DATA DIVERSITY: Use descriptive variable names (e.g., \$total_price, \$item_count, \$is_validated) and avoid repetitive simple values like 5 or 10. Use a variety of realistic numbers and scenarios.
      9. RANDOMIZE CORRECT ANSWER: Use ONLY UPPERCASE letters (A, B, C, D) for keys. Distribute the correct answer letter randomly.
      10. QUALITY OPTIONS: Answer options for technical questions should be precise. If predicting output, explain why that output occurs in the options if possible, or provide distinct alternative logic paths.
      11. MATHEMATICAL PRECISION: For average (rata-rata) or division calculations, ensure the result is exact. If the result is a decimal (e.g., 81.25), you MUST include the exact decimal value in the options. DO NOT round unless explicitly stated in the question text.
      
      JSON Format: {\"questions\": [{\"text\":\"Question text... ```code here```\",\"options\":{\"A\":\"...\",\"B\":\"...\",\"C\":\"...\",\"D\":\"...\"},\"correct\":\"B\",\"difficulty\":\"medium\",\"category\":\"required/preferred/bonus\"}]}
      
      CONTOH AKURASI MATEMATIKA (IKUTI STANDAR INI):
      Soal: ```\$p = 10000; \$d = 0.1; \$res = \$p * (1-\$d);``` Berapakah \$res?
      A. 8000
      B. 9000
      C. 9500
      D. 10000
      Kunci: B (Karena 10000 * 0.9 = 9000).

      Output ONLY JSON. Exactly {$count} UNIQUE questions. No duplicates.";
  }

  /**
   * Process AI response and save to database.
   */
  protected function generateBatch(GroqService $groq, string $prompt, string $sectionTag, int $countRequested, int $maxTokens = 4096, string $model = null)
  {
    $maxAttempts = 3;

    for ($attempt = 1; $attempt <= $maxAttempts; $attempt++) {
      try {
        $response = $groq->chat(
          [
            [
              'role' => 'system',
              'content' => "You are an expert exam question generator. Return ONLY JSON output. 
              CRITICAL: Before finalizing each question, you MUST perform a 'Step-by-Step Mental Execution' of any code or math. 
              1. Calculate the exact result (including decimals).
              2. Verify that the 'correct' option matches this exact result perfectly.
              3. Ensure all other options are distinct and plausible but incorrect.
              If the calculation is even slightly off (e.g., 81.25 vs 81), the question is FAILED.
              All questions MUST be in Indonesian (Bahasa Indonesia). Generate EXACTLY {$countRequested} questions."
            ],
            ['role' => 'user', 'content' => $prompt]
          ],
          0.1, // Lower temperature for higher accuracy and consistency
          $maxTokens,
          $model  // null = use default from config; otherwise use specified model
        );

        $questions = array_slice($response['questions'] ?? [], 0, $countRequested);
        $count = count($questions);

        Log::info("Attempt {$attempt} for {$sectionTag}: Successfully fetched {$count} questions.");

        if ($count < ($countRequested - 2) && $attempt < $maxAttempts) {
          Log::warning("Under-generated {$sectionTag}: {$count}/{$countRequested}. Retrying in 22 seconds (attempt {$attempt})...");
          sleep(22); // Rate limit protection for Groq
          continue;
        }

        // Save questions to database with duplicate protection
        foreach ($questions as $q) {
          $trimmedText = trim($q['text']);
          
          // Shuffle options to ensure 100% randomness even if AI is biased
          $options = $q['options'] ?? [];
          $correctValue = $options[$q['correct'] ?? ''] ?? null;

          if (!$correctValue) continue;

          $optionValues = array_values($options);
          shuffle($optionValues);

          $newOptions = [];
          $newCorrect = 'A';
          foreach ($optionValues as $index => $value) {
            $key = chr(65 + $index); // A, B, C, D
            $newOptions[$key] = $value;
            if ($value === $correctValue) {
              $newCorrect = $key;
            }
          }

          // Use firstOrCreate to prevent identical questions within the same vacancy
          Question::firstOrCreate(
            [
              'job_vacancy_id' => $this->jobVacancy->id,
              'question_text'  => $trimmedText,
            ],
            [
              'options'        => $newOptions,
              'correct_answer' => $newCorrect,
              'is_active'      => false,
              'section'        => $sectionTag,
              'difficulty'     => $q['difficulty'] ?? 'medium',
              'skill_category' => $q['category'] ?? 'required',
            ]
          );
        }

        Log::info("Successfully saved {$count} questions to the '{$sectionTag}' section.");
        return;

      } catch (\Exception $e) {
        Log::error("Error on attempt {$attempt} for {$sectionTag}: " . $e->getMessage());
        if ($attempt === $maxAttempts) {
          throw $e;
        }
      }
    }
  }
}
