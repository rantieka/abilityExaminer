<?php

namespace App\Jobs;

use App\Models\JobVacancy;
use App\Models\Question;
use App\Services\GroqService;
// use App\Services\OllamaService; // Fallback option
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class GenerateExamQuestions // implements ShouldQueue (Temporary force sync)
{
  use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

  public $jobVacancy;
  public $timeout = 600; // Increased timeout for heavier generation

  public function __construct(JobVacancy $jobVacancy)
  {
      $this->jobVacancy = $jobVacancy;
  }

  public function handle(GroqService $groq): void
  // public function handle(OllamaService $ai): void // Fallback
  {
    Log::info("Starting Advanced Exam Generation for: " . $this->jobVacancy->title);

    try {
      // Detect industry type
      $industryType = $this->detectIndustryType();
      Log::info("Detected industry type: {$industryType}");

      // Part 1: Knowledge & Foundation (20 Questions) - Universal
      $promptPart1 = $this->buildPart1Prompt();
      $this->generateBatch($groq, $promptPart1, 'knowledge');

      // Part 2: Technical/Case Study (15 Questions) - Industry-specific
      $promptPart2 = $industryType === 'tech' 
        ? $this->buildPart2TechPrompt() 
        : $this->buildPart2NonTechPrompt();
      $this->generateBatch($groq, $promptPart2, 'technical');

    } catch (\Exception $e) {
      Log::error("Failed to generate exam questions: " . $e->getMessage());
    }
  }

  protected function detectIndustryType(): string
  {
    $techKeywords = ['developer', 'programmer', 'engineer', 'IT', 'software', 'web', 'mobile', 
                     'data', 'devops', 'backend', 'frontend', 'fullstack', 'QA', 'tester',
                     'system', 'network', 'database', 'cloud', 'security', 'AI', 'ML'];
    
    $title = strtolower($this->jobVacancy->title);
    $qualifications = strtolower(strip_tags($this->jobVacancy->qualifications ?? ''));
    
    foreach ($techKeywords as $keyword) {
      if (str_contains($title, $keyword) || str_contains($qualifications, $keyword)) {
        return 'tech';
      }
    }
    
    return 'non-tech';
  }

  protected function buildPart1Prompt(): string
  {
    return "
      Buatkan TEPAT 20 soal pilihan ganda untuk posisi '{$this->jobVacancy->title}'.
      Kualifikasi: " . strip_tags($this->jobVacancy->qualifications) . "

      Distribusi: 6 Easy, 10 Medium, 4 Hard
      
      Format JSON:
      {\"questions\": [{\"text\": \"...\", \"options\": {\"A\":\"...\",\"B\":\"...\",\"C\":\"...\",\"D\":\"...\"}, \"correct\": \"A\", \"difficulty\": \"easy\"}]}
      
      WAJIB: Output HANYA JSON. HARUS 20 soal, tidak boleh kurang.";
  }

  protected function buildPart2TechPrompt(): string
  {
    return "
      Buatkan TEPAT 20 soal TECHNICAL untuk posisi '{$this->jobVacancy->title}'.
      Kualifikasi: " . strip_tags($this->jobVacancy->qualifications) . "

      Distribusi: 6 Easy, 10 Medium, 4 Hard
      
      WAJIB ada:
      - Code snippet (5-10 baris)
      - Architecture scenario
      - Debugging case (N+1, performance)
      - Best practice
      
      Contoh [MEDIUM]:
      Database lambat karena N+1 saat load 1000 records. Solusi:
      A) Upgrade server B) Eager loading with() C) Index saja D) Cache semua
      
      Format JSON:
      {\"questions\": [{\"text\": \"...\", \"options\": {\"A\":\"...\",\"B\":\"...\",\"C\":\"...\",\"D\":\"...\"}, \"correct\": \"B\", \"difficulty\": \"medium\"}]}
      
      WAJIB: Output HANYA JSON. HARUS 20 soal, tidak boleh kurang.";
  }

  protected function buildPart2NonTechPrompt(): string
  {
    return "
      Buatkan TEPAT 20 soal CASE STUDY untuk posisi '{$this->jobVacancy->title}'.
      Kualifikasi: " . strip_tags($this->jobVacancy->qualifications) . "

      Distribusi: 6 Easy, 10 Medium, 4 Hard
      
      WAJIB ada:
      - Workplace conflict
      - Resource allocation (budget/time)
      - Ethical dilemma
      - Strategic decision
      
      Contoh [HARD]:
      Candidate excellent, tapi resign 3x dalam 2 tahun. Pertanyaan terbaik:
      A) \"Kenapa sering resign?\" B) Reject C) \"Cerita career journey 2 tahun terakhir?\" D) Check reference
      
      Format JSON:
      {\"questions\": [{\"text\": \"...\", \"options\": {\"A\":\"...\",\"B\":\"...\",\"C\":\"...\",\"D\":\"...\"}, \"correct\": \"C\", \"difficulty\": \"hard\"}]}
      
      WAJIB: Output HANYA JSON. HARUS 20 soal, tidak boleh kurang.";
  }

  protected function generateBatch(GroqService $groq, string $prompt, string $sectionTag)
  // protected function generateBatch(OllamaService $ai, string $prompt, string $sectionTag) // Fallback
  {
    try {
      $expectedCount = 20; // Both parts generate 20 questions
      
      $response = $groq->chat([
        ['role' => 'system', 'content' => "Anda Senior Technical Recruiter. Output JSON Only. WAJIB generate TEPAT {$expectedCount} soal, tidak boleh kurang atau lebih."],
        ['role' => 'user', 'content' => $prompt]
      ]);

      if (!empty($response['questions'])) {
        foreach ($response['questions'] as $q) {
          $data = [
            'job_vacancy_id' => $this->jobVacancy->id,
            'question_text' => $q['text'],
            'options' => $q['options'],
            'correct_answer' => $q['correct'],
            'is_active' => false,
            'section' => $sectionTag
          ];
          
          Log::info("Saving Question:", $data); // Debug Insert

          Question::create($data);
        }
        Log::info("Generated batch for {$sectionTag} with " . count($response['questions']) . " questions.");
      }
    } catch (\Exception $e) {
      Log::error("Error generating batch {$sectionTag}: " . $e->getMessage());
    }
  }
}
