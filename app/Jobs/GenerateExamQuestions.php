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
      $neededKnowledge = $target - $existingKnowledgeCount;
      if ($neededKnowledge > 0) {
        $promptPart1 = $this->buildPart1Prompt($neededKnowledge);
        $this->generateBatch($groq, $promptPart1, 'knowledge', $neededKnowledge, 4096);
      } else {
        Log::info("Knowledge section already has enough master questions ({$existingKnowledgeCount}). Skipping AI generation.");
      }

      // Part 2: Technical
      $neededTechnical = $target - $existingTechnicalCount;
      if ($neededTechnical > 0) {
        $promptPart2 = $this->buildPart2TechPrompt($neededTechnical);
        $this->generateBatch($groq, $promptPart2, 'technical', $neededTechnical, 8192);
      } else {
        Log::info("Technical section already has enough master questions ({$existingTechnicalCount}). Skipping AI generation.");
      }

    } catch (\Exception $e) {
      Log::error("Failed to generate exam questions: " . $e->getMessage());
    }
  }

  /**
   * Determine question difficulty based on job title.
   */
  protected function determineDifficulty(string $title): array
  {
    $titleLower = strtolower($title);

    if (str_contains($titleLower, 'senior') || str_contains($titleLower, 'lead') || str_contains($titleLower, 'manager')) {
      return [
        'distribution' => '2 Easy, 6 Medium, 12 Hard',
        'focus' => 'Advanced Knowledge, Architecture, System Scalability, Security, Advanced Optimization',
        'tech_focus' => 'Architecture, System Design, Advanced Debugging, Security'
      ];
    } elseif (str_contains($titleLower, 'junior') || str_contains($titleLower, 'smk') || str_contains($titleLower, 'entry') || str_contains($titleLower, 'intern')) {
      return [
        'distribution' => '10 Easy, 8 Medium, 2 Hard',
        'focus' => 'Fundamental Understanding, Basic Syntax, Standard Tool Usage',
        'tech_focus' => 'Basic Syntax, Fundamental Logic, Simple Debugging'
      ];
    }

    // Default (Mid-level)
    return [
      'distribution' => '6 Easy, 10 Medium, 4 Hard',
      'focus' => 'Industry standards, core concepts, common tools, working principles',
      'tech_focus' => 'Industry standard practices, framework usage, intermediate debugging'
    ];
  }

  /**
   * Build prompt for Knowledge & Foundation section.
   */
  protected function buildPart1Prompt(int $count): string
  {
    $req = implode(', ', $this->jobVacancy->required_skills ?? []);
    $pref = implode(', ', $this->jobVacancy->preferred_skills ?? []);
    $bonus = implode(', ', $this->jobVacancy->bonus_skills ?? []);

    $diff = $this->determineDifficulty($this->jobVacancy->title);

    return "
      Generate EXACTLY {$count} multiple-choice questions for THEORETICAL KNOWLEDGE for the position: '{$this->jobVacancy->title}'.
      
      SKILL CONTEXT:
      - PRIMARY (Required): {$req}
      - SECONDARY (Preferred): {$pref}
      - BONUS SKILLS: {$bonus}
      
      Qualification Description: " . strip_tags($this->jobVacancy->qualifications) . "
      
      RULES:
      1. LANGUAGE: All question text and options MUST be in Indonesian (Bahasa Indonesia).
      2. FORBIDDEN: Long case studies, code snippets, or debugging (Part 1 is strictly for fundamental theory).
      3. DIFFICULTY DISTRIBUTION: {$diff['distribution']}.
      4. CONTENT FOCUS: {$diff['focus']} related to the skills above.
      
      JSON Format: {\"questions\": [{\"text\":\"...\",\"options\":{\"A\":\"...\",\"B\":\"...\",\"C\":\"...\",\"D\":\"...\"},\"correct\":\"A\",\"difficulty\":\"easy\"}]}
      Output ONLY JSON. Exactly {$count} questions.";
  }

  /**
   * Build prompt for Technical & Analysis section.
   */
  protected function buildPart2TechPrompt(int $count): string
  {
    $req = implode(', ', $this->jobVacancy->required_skills ?? []);
    $pref = implode(', ', $this->jobVacancy->preferred_skills ?? []);
    $bonus = implode(', ', $this->jobVacancy->bonus_skills ?? []);

    $diff = $this->determineDifficulty($this->jobVacancy->title);

    return "
      Generate EXACTLY {$count} multiple-choice questions for CODING & TECHNICAL ANALYSIS for the position: '{$this->jobVacancy->title}'.
      
      SKILLS TO TEST: {$req}
      SUPPORTING SKILLS: {$pref}
      ADVANCED/OPTIONAL (Hard Level): {$bonus}

      RULES:
      1. LANGUAGE: All question text and analysis MUST be in Indonesian (Bahasa Indonesia). Use original syntax for code snippets (e.g., {$req}).
      2. EVERY question MUST include a code snippet (3-10 lines) within the question text.
      3. DIFFICULTY DISTRIBUTION: {$diff['distribution']}.
      4. CONTENT FOCUS: {$diff['tech_focus']}.
      
      QUESTION TYPES:
      - Predict output (read code)
      - Find the bug (identify errors)
      - Logic completion (choose code fix)
      - Architecture & Query analysis
      
      JSON Format: {\"questions\": [{\"text\":\"...\",\"options\":{\"A\":\"...\",\"B\":\"...\",\"C\":\"...\",\"D\":\"...\"},\"correct\":\"B\",\"difficulty\":\"medium\"}]}
      Output ONLY JSON. Exactly {$count} questions.";
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

        // Save questions to database
        foreach ($questions as $q) {
          Question::create([
            'job_vacancy_id' => $this->jobVacancy->id,
            'question_text'  => $q['text'],
            'options'        => $q['options'],
            'correct_answer' => $q['correct'],
            'is_active'      => false,
            'section'        => $sectionTag,
          ]);
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
