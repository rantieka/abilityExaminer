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

      // Target is 20 questions per section
      $target = 20;

      // Part 1: Knowledge & Foundation
      $existingKnowledge = Question::where('job_vacancy_id', $this->jobVacancy->id)
        ->where('section', 'knowledge')
        ->get();

      $neededKnowledge = $target - $existingKnowledge->count();
      if ($neededKnowledge > 0) {
        $promptPart1 = $this->buildPart1Prompt($neededKnowledge, $existingKnowledge->pluck('question_text')->toArray());
        $this->generateBatch($groq, $promptPart1, 'knowledge', $neededKnowledge, 4096);
      } else {
        Log::info("Knowledge section already has enough master questions. Skipping AI generation.");
      }

      // Part 2: Technical
      $existingTechnical = Question::where('job_vacancy_id', $this->jobVacancy->id)
        ->where('section', 'technical')
        ->get();

      $neededTechnical = $target - $existingTechnical->count();
      if ($neededTechnical > 0) {
        $promptPart2 = $this->buildPart2TechPrompt($neededTechnical, $existingTechnical->pluck('question_text')->toArray());
        $this->generateBatch($groq, $promptPart2, 'technical', $neededTechnical, 8192);
      } else {
        Log::info("Technical section already has enough master questions. Skipping AI generation.");
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

    if ($level === 'senior' || $level === 'lead' || $level === 'manager') {
      return [
        'distribution' => '2 Easy, 6 Medium, 12 Hard',
        'focus' => 'Advanced Knowledge, Architecture, System Scalability, Security, Advanced Optimization',
        'tech_focus' => 'Architecture, System Design, Advanced Debugging, Security'
      ];
    } elseif ($level === 'junior' || $level === 'entry' || $level === 'intern') {
      return [
        'distribution' => '10 Easy, 8 Medium, 2 Hard',
        'focus' => 'Fundamental Understanding, Basic Syntax, Standard Tool Usage',
        'tech_focus' => 'Basic Syntax, Fundamental Logic, Simple Debugging'
      ];
    }

    // Default (Middle)
    return [
      'distribution' => '6 Easy, 10 Medium, 4 Hard',
      'focus' => 'Industry standards, core concepts, common tools, working principles',
      'tech_focus' => 'Industry standard practices, framework usage, intermediate debugging'
    ];
  }

  /**
   * Build prompt for Knowledge & Foundation section.
   */
  protected function buildPart1Prompt(int $count, array $existingQuestions = []): string
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
      - PRIMARY (Required): {$req}
      - SECONDARY (Preferred): {$pref}
      - BONUS SKILLS: {$bonus}
      
      Qualification Description: " . strip_tags($this->jobVacancy->qualifications) . "
      {$context}

      RULES:
      1. LANGUAGE: All question text and options MUST be in Indonesian (Bahasa Indonesia).
      2. FORBIDDEN: Long case studies, code snippets, or debugging (Part 1 is strictly for fundamental theory).
      3. DIFFICULTY DISTRIBUTION: {$diff['distribution']}.
      4. CONTENT FOCUS: {$diff['focus']} related to the skills above.
      5. UNIQUENESS: EVERY question MUST have a unique topic. DO NOT repeat the same concept (e.g. if one question is about Variables, others must be about Loops, OOP, or SQL). Complement existing questions.
      
      JSON Format: {\"questions\": [{\"text\":\"...\",\"options\":{\"A\":\"...\",\"B\":\"...\",\"C\":\"...\",\"D\":\"...\"},\"correct\":\"A\",\"difficulty\":\"easy\"}]}
      Output ONLY JSON. Exactly {$count} UNIQUE questions. No duplicates.";
  }

  /**
   * Build prompt for Technical & Analysis section.
   */
  protected function buildPart2TechPrompt(int $count, array $existingQuestions = []): string
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
      
      SKILLS TO TEST: {$req}
      SUPPORTING SKILLS: {$pref}
      ADVANCED/OPTIONAL (Hard Level): {$bonus}
      {$context}

      RULES:
      1. LANGUAGE: All question text and analysis MUST be in Indonesian (Bahasa Indonesia). Use original syntax for code snippets (e.g., {$req}).
      2. EVERY question MUST include a unique code snippet (3-10 lines) within the question text.
      3. DIFFICULTY DISTRIBUTION: {$diff['distribution']}.
      4. CONTENT FOCUS: {$diff['tech_focus']}.
      5. UNIQUENESS: Ensure technical logic, bug types, and snippets are COMPLETELY DIFFERENT across all {$count} questions. No repetition of the same bug or logic pattern.
      
      JSON Format: {\"questions\": [{\"text\":\"...\",\"options\":{\"A\":\"...\",\"B\":\"...\",\"C\":\"...\",\"D\":\"...\"},\"correct\":\"B\",\"difficulty\":\"medium\"}]}
      Output ONLY JSON. Exactly {$count} UNIQUE questions. No duplicates.";
  }

  /**
   * Process AI response and save to database.
   */
  protected function generateBatch(GroqService $groq, string $prompt, string $sectionTag, int $countRequested, int $maxTokens = 4096)
  {
    $maxAttempts = 3;

    for ($attempt = 1; $attempt <= $maxAttempts; $attempt++) {
      try {
        $response = $groq->chat(
          [
            [
              'role' => 'system',
              'content' => "You are an expert exam question generator. Return ONLY JSON output—no explanations or other text. All questions MUST be in Indonesian (Bahasa Indonesia). Generate EXACTLY {$countRequested} questions."
            ],
            ['role' => 'user', 'content' => $prompt]
          ],
          0.5,
          $maxTokens
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
          
          // Use firstOrCreate to prevent identical questions within the same vacancy
          Question::firstOrCreate(
            [
              'job_vacancy_id' => $this->jobVacancy->id,
              'question_text'  => $trimmedText,
            ],
            [
              'options'        => $q['options'],
              'correct_answer' => $q['correct'],
              'is_active'      => false,
              'section'        => $sectionTag,
              'difficulty'     => $q['difficulty'] ?? 'medium',
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
