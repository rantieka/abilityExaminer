<?php

namespace App\Jobs;

use App\Models\JobVacancy;
use App\Models\Question;
use App\Services\GeminiService;
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
  public $userId;
  public $timeout = 600;

  public function __construct(JobVacancy $jobVacancy, ?int $userId = null)
  {
    $this->jobVacancy = $jobVacancy;
    $this->userId = $userId;
  }

  /**
   * Execute the job.
   */
  public function handle(GroqService $groq, GeminiService $gemini): void
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
        
        $currentKnowledgeCount = Question::where('job_vacancy_id', $this->jobVacancy->id)
          ->where('section', 'knowledge')
          ->count();

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
          ->limit(40)
          ->pluck('question_text')
          ->toArray();

        $batchSize = min(5, $neededForFocus, $neededKnowledge);
        $promptPart1 = $this->buildPart1Prompt($batchSize, $focus, $existingTexts);
        $this->generateBatch($groq, $promptPart1, 'knowledge', $batchSize, $focus, 8192);
        
        sleep(20);
      }

      // Part 2: Technical
      while (true) {
        $counts = [
            'required'  => Question::where('job_vacancy_id', $this->jobVacancy->id)->where('section', 'technical')->where('skill_category', 'required')->count(),
            'preferred' => Question::where('job_vacancy_id', $this->jobVacancy->id)->where('section', 'technical')->where('skill_category', 'preferred')->count(),
            'bonus'     => Question::where('job_vacancy_id', $this->jobVacancy->id)->where('section', 'technical')->where('skill_category', 'bonus')->count(),
        ];

        $currentTechnicalCount = Question::where('job_vacancy_id', $this->jobVacancy->id)
          ->where('section', 'technical')
          ->count();

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
          ->limit(40)
          ->pluck('question_text')
          ->toArray();

        $batchSize = min(5, $neededForFocus, $neededTechnical);
        $promptPart2 = $this->buildPart2TechPrompt($batchSize, $focus, $existingTexts);
        $this->generateBatch($gemini, $promptPart2, 'technical', $batchSize, $focus, 8192);
        
        sleep(20);
      }

      if ($this->userId) {
          $user = \App\Models\User::find($this->userId);
          if ($user) {
              \Filament\Notifications\Notification::make()
                  ->title('Berhasil Membuat Soal')
                  ->body("AI berhasil memproduksi seluruh soal (Knowledge & Technical) untuk lowongan: {$this->jobVacancy->title}.")
                  ->success()
                  ->sendToDatabase($user);
          }
      }

    } catch (\Exception $e) {
      Log::error("Failed to generate exam questions: " . $e->getMessage());
      if ($this->userId) {
          $user = \App\Models\User::find($this->userId);
          if ($user) {
              \Filament\Notifications\Notification::make()
                  ->title('Gagal Membuat Soal')
                  ->body('AI belum bisa melakukan generate soal, coba sesaat lagi atau lain hal.')
                  ->danger()
                  ->sendToDatabase($user);
          }
      }
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
      
      STRICT FOCUS FOR THIS BATCH: You MUST only generate questions for the '{$focusCategory}' category.
      
      Qualification Description: " . strip_tags($this->jobVacancy->qualifications) . "
      {$context}

      RULES:
      1. LANGUAGE: All question text and options MUST be in Indonesian (Bahasa Indonesia).\n
      2. STRICTLY THEORY ONLY: ABSOLUTELY NO code snippets, NO variable assignment calculations (e.g., a=b), NO logic tracing, and NO technical symbols. Questions must focus on definitions, functions, concepts, and best practices.\n
      3. DIFFICULTY DISTRIBUTION: {$diff['distribution']}.\n
      4. MAPPING RULES: 
         - Required skills can be Easy, Medium, or Hard.\n
         - Preferred skills should be Easy or Medium.\n
         - Bonus skills MUST be Easy.\n
      5. CONTENT FOCUS: {$diff['focus']} related to the skills above.\n
      6. STRICT UNIQUENESS: EVERY question MUST cover a completely different topic or concept. DO NOT generate similar questions with slightly different wording. If a concept (e.g., Encapsulation, MVC, Middleware) is already in the EXISTING QUESTIONS list below, DO NOT generate another question about it. Focus on breadth of knowledge. DO NOT just rephrase existing questions; they must be conceptually different.\n
      7. RANDOMIZE CORRECT ANSWER: Distribute the correct answer letter (A, B, C, D) randomly across the set of questions. Do not bias towards 'A'.\n
      8. QUALITY OPTIONS: Ensure answer options are descriptive, professional, and clear. Avoid overly short or repetitive phrases. Each option should be a plausible but distinct explanation of the concept.\n
      9. DIVERSITY RULES: Use unique numerical values (avoid 10, 50, 100 repeatedly). If a concept (like MVC or OOP) is already mentioned, explore sub-topics or different technologies.\n
      10. CATEGORY TAGGING: Every question in this response MUST be tagged as 'category': '{$focusCategory}'.\n
      
      JSON Format: {\"questions\": [{\"text\":\"Question text...\",\"options\":{\"A\":\"...\",\"B\":\"...\",\"C\":\"...\",\"D\":\"...\"},\"correct\":\"C\",\"difficulty\":\"easy\",\"category\":\"{$focusCategory}\"}]}
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
      
      STRICT FOCUS FOR THIS BATCH: You MUST only generate questions for the '{$focusCategory}' category.
      
      {$context}

      RULES:
      1. LANGUAGE: All question text and analysis MUST be in Indonesian (Bahasa Indonesia). Use original syntax for code snippets (e.g., PHP, JavaScript).\n
      2. CASE STUDY & LOGIC: Every question MUST include a code snippet wrapped in triple backticks (```) and focus on technical case studies, logic tracing, syntax analysis, bug detection, or predicting output.\n
      3. MATHEMATICAL ACCURACY: If the question involves calculations (e.g., discounts, taxes, loop iterations), you MUST double-check the math. Ensure the 'correct' answer exactly matches the result of the code snippet provided.\n
      4. DIFFICULTY DISTRIBUTION: {$diff['distribution']}.\n
      5. MAPPING RULES:\n
         - Required skills (Main Focus) can be any difficulty.\n
         - Preferred skills should be Easy or Medium.\n
         - Bonus skills MUST be Easy.\n
      6. CONTENT FOCUS: {$diff['tech_focus']}.\n
      7. STRICT UNIQUENESS: Ensure technical logic, bug types, and snippets are COMPLETELY DIFFERENT. DO NOT repeat the same logic pattern. If one question is about array filtering, make the next about data validation, session handling, or regex—avoid repetition of logic categories. DO NOT just change variable names or add/remove the word 'JavaScript' to make a duplicate look unique. It must be a new logic scenario.\n
      8. DATA DIVERSITY: Use descriptive variable names (e.g., \$total_price, \$item_count, \$is_validated) and avoid repetitive simple values like 5 or 10. Use a variety of realistic numbers and scenarios.\n
      9. RANDOMIZE CORRECT ANSWER: Use ONLY UPPERCASE letters (A, B, C, D) for keys. Distribute the correct answer letter randomly.\n
      10. QUALITY OPTIONS: Answer options for technical questions should be precise. If predicting output, explain why that output occurs in the options if possible, or provide distinct alternative logic paths.\n
      11. DIVERSITY RULES: Use realistic and diverse numerical values (e.g., 127.50, 43500, 0.085). AVOID repeating logic like 'calculate 15% discount' if it already exists. Instead, use logic like 'calculate tax + shipping', 'find max in array', 'check string length', or 'nested conditions'.\n
      12. CATEGORY TAGGING: Every question in this response MUST be tagged as 'category': '{$focusCategory}'.\n
      
      JSON Format: {\"questions\": [{\"text\":\"Question text... ```code here```\",\"options\":{\"A\":\"...\",\"B\":\"...\",\"C\":\"...\",\"D\":\"...\"},\"correct\":\"B\",\"difficulty\":\"medium\",\"category\":\"{$focusCategory}\"}]}
      
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
   * Can accept either GroqService or GeminiService.
   */
  protected function generateBatch($aiService, string $prompt, string $sectionTag, int $countRequested, string $forcedCategory, int $maxTokens = 4096, string $model = null)
  {
    $maxAttempts = 3;

    for ($attempt = 1; $attempt <= $maxAttempts; $attempt++) {
      try {
        $response = $aiService->chat(
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
        $countFetched = count($questions);
        $countSaved = 0;

        Log::info("Attempt {$attempt} for {$sectionTag}: Successfully fetched {$countFetched} questions.");

        if ($countFetched < ($countRequested - 2) && $attempt < $maxAttempts) {
          Log::warning("Under-generated {$sectionTag}: {$countFetched}/{$countRequested}. Retrying in 22 seconds (attempt {$attempt})...");
          sleep(22); // Rate limit protection for Gemini free tier
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
          $question = Question::firstOrCreate(
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
              'skill_category' => $forcedCategory, // Use forced category to maintain strict distribution
            ]
          );

          if ($question->wasRecentlyCreated) {
            $countSaved++;
          }
        }

        Log::info("Successfully saved {$countSaved} NEW questions to the '{$sectionTag}' section (Category: {$forcedCategory}).");
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
