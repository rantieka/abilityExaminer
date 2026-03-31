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
  public $timeout = 600;

  public function __construct(JobVacancy $jobVacancy)
  {
    $this->jobVacancy = $jobVacancy;
  }

  public function handle(GroqService $groq): void
  // public function handle(OllamaService $ai): void // Fallback
  {
    try {
      // Part 1: Knowledge & Foundation (20 Questions) - Universal
      $promptPart1 = $this->buildPart1Prompt();
      $this->generateBatch($groq, $promptPart1, 'knowledge', 4096);

      // Part 2: Technical (20 Questions)
      $promptPart2 = $this->buildPart2TechPrompt();
      $this->generateBatch($groq, $promptPart2, 'technical', 8192);

    } catch (\Exception $e) {
      Log::error("Failed to generate exam questions: " . $e->getMessage());
    }
  }

  protected function buildPart1Prompt(): string
  {
    return "
      Buat TEPAT 20 soal pilihan ganda PENGETAHUAN DASAR (definisi, teori, terminologi) untuk posisi '{$this->jobVacancy->title}'.
      Kualifikasi: " . strip_tags($this->jobVacancy->qualifications) . "
      BAHASA: Semua teks soal dan pilihan jawaban WAJIB dalam Bahasa Indonesia.
      DILARANG: studi kasus, skenario, debugging, atau dilema.
      Distribusi: 6 Easy, 10 Medium, 4 Hard.
      Topik wajib: terminologi bidang, konsep dasar, standar industri, tools umum, prinsip kerja posisi.
      Format JSON: {\"questions\": [{\"text\":\"...\",\"options\":{\"A\":\"...\",\"B\":\"...\",\"C\":\"...\",\"D\":\"...\"},\"correct\":\"A\",\"difficulty\":\"easy\"}]}
      Output HANYA JSON. Tepat 20 soal.";
  }

  protected function buildPart2TechPrompt(): string
  {
    return "
      Buat TEPAT 20 soal CODING & TECHNICAL untuk posisi '{$this->jobVacancy->title}'.
      Kualifikasi: " . strip_tags($this->jobVacancy->qualifications) . "
      BAHASA: Semua teks soal, penjelasan, dan pilihan jawaban WAJIB dalam Bahasa Indonesia. Hanya snippet kode yang boleh dalam bahasa pemrograman (PHP/SQL).
      SETIAP soal WAJIB menyertakan snippet kode (PHP, SQL, atau pseudocode, 3-8 baris) di dalam teks soal.
      DILARANG: soal tanpa kode, soal definisi murni, atau soal yang hanya berupa paragraf teks.
      Distribusi: 6 Easy, 10 Medium, 4 Hard.
      Distribusi tipe soal:
      - 6 soal: Predict output (baca kode, pilih output yang benar)
      - 6 soal: Find the bug (kode ada error, pilih baris/penyebab yang salah)
      - 4 soal: Fix the code (diberikan kode bermasalah, pilih perbaikan yang tepat)
      - 4 soal: SQL/query analysis (query lambat atau salah, pilih solusi terbaik)
      Format JSON: {\"questions\": [{\"text\":\"...\",\"options\":{\"A\":\"...\",\"B\":\"...\",\"C\":\"...\",\"D\":\"...\"},\"correct\":\"B\",\"difficulty\":\"medium\"}]}
      Output HANYA JSON. Tepat 20 soal.";
  }

  protected function generateBatch(GroqService $groq, string $prompt, string $sectionTag, int $maxTokens = 4096)
  // protected function generateBatch(OllamaService $ai, string $prompt, string $sectionTag, int $maxTokens = 4096) // Fallback
  {
    $expectedCount = 20;
    $maxAttempts   = 3;

    for ($attempt = 1; $attempt <= $maxAttempts; $attempt++) {
      try {
        $response = $groq->chat(
          [
            ['role' => 'system', 'content' => "Anda adalah generator soal ujian. Output JSON SAJA — tanpa penjelasan atau teks lain. Semua soal WAJIB dalam Bahasa Indonesia. Generate TEPAT {$expectedCount} soal, tidak kurang tidak lebih."],
            ['role' => 'user', 'content' => $prompt]
          ],
          0.5,
          $maxTokens
        );

        $questions = array_slice($response['questions'] ?? [], 0, $expectedCount);
        $count     = count($questions);

        Log::info("Attempt {$attempt} for {$sectionTag}: got {$count} questions (raw: " . count($response['questions'] ?? []) . ").");

        if ($count < $expectedCount && $attempt < $maxAttempts) {
          Log::warning("Under-generated {$sectionTag}: {$count}/{$expectedCount}. Retrying (attempt {$attempt})...");
          continue;
        }

        // Save whatever we have (could be less on final attempt)
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

        Log::info("Saved {$count} questions for {$sectionTag}.");
        return; // success

      } catch (\Exception $e) {
        Log::error("Error on attempt {$attempt} for {$sectionTag}: " . $e->getMessage());
        if ($attempt === $maxAttempts) {
          throw $e;
        }
      }
    }
  }
}
