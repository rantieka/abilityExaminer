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

  // public $connection = 'database'; // REMOVED to avoid Fatal Error with Queueable trait
  public $tries = 3;
  public $backoff = [10, 30, 60];

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
      Log::error("ProcessCvScreening Job failed: Application model is null. It may have been deleted.");
      return;
    }

    Log::info("Starting Screening for Application ID: " . $this->application->id);

    // read cv
    $path = Storage::disk('public')->path($this->application->cv_path);
    
    try {
      $pdf = $parser->parseFile($path);
      $cvText = $pdf->getText();
    } catch (\Exception $e) {
      Log::error("PDF Parsing failed: " . $e->getMessage());
      // Don't fail the job, just mark as failed analysis
      return;
    }

    if (trim($cvText) === '') {
      Log::warning("Empty CV text (possibly image-based PDF) for Application ID: " . $this->application->id);
      // Update with default/error analysis
      $this->application->update([
        'ai_analysis' => [
          'summary' => 'Gagal membaca teks CV (Mungkin format gambar/scanned). Silakan review manual.',
          'pros' => [],
          'cons' => []
        ],
        'ai_score' => 0
      ]);
      return;
    }

    // =========================================================
    // 🛡️ PRIVACY GUARD: PII DATA MASKING
    // =========================================================
    // PII (Name, Email, Phone) is removed before sending to Groq Cloud
    // ensuring candidate data remains SAFE and ANONYMOUS.
    $anonymizedCvText = $this->maskPII($cvText, $this->application);

    // prepare prompt
    $job = $this->application->jobVacancy;
    // clean html tags from qualifications because Groq need plain text
    $qualifications = strip_tags($job->qualifications); 

    $prompt = "
              Lowongan: {$job->title}
              Kualifikasi: 
              {$qualifications}

              Teks CV (Anonymized): 
              {$anonymizedCvText}

              Aturan:
              1. Analisis relevansi skill kandidat dengan kualifikasi secara FAKTUAL.
              2. KETERKAITAN SINONIM DIIZINKAN (Misal 'API' ≈ 'REST API', 'React' ≈ 'ReactJS').
              3. JANGAN berasumsi skill yang TIDAK tertulis.

              Output JSON Only:
              {
                  \"score\": 0-100,
                  \"analysis\": {
                      \"summary\": \"Ringkasan singkat relevansi skill.\",
                      \"pros\": [\"Skill yang cocok\"],
                      \"cons\": [\"Skill yang kurang\"]
                  }
              }
          ";
    // send to groq
    $messages = [
      ['role' => 'system', 'content' => 'Anda adalah AI Recruiter Assistant. Output JSON Only.'],
      ['role' => 'user', 'content' => $prompt]
    ];

    $result = $groq->chat($messages);
    Log::info("Groq Raw Result: " . json_encode($result)); // DEBUG LINE

    if ($result) {
      // save to db
      $this->application->update([
        'ai_score' => $result['score'] ?? 0,
        'ai_analysis' => $result['analysis'] ?? [],
        'status' => ($result['score'] >= 75) ? 'reviewed' : 'pending' // Auto status update if high score
      ]);
        
      Log::info("Screening Success. Score: " . ($result['score'] ?? 0));
    }
  }

  protected function maskPII(string $text, Application $app): string
  {
      $masked = $text;
      
      // Mask Email
      if ($app->email) {
          $masked = str_ireplace($app->email, '[EMAIL_REDACTED]', $masked);
      }
      
      // Mask Phone
      if ($app->phone) {
          $masked = str_ireplace($app->phone, '[PHONE_REDACTED]', $masked);
          // Try masking formatted phone numbers? Simpler is better for now
      }
      
      // Mask Name (Fullname and parts)
      if ($app->full_name) {
          $masked = str_ireplace($app->full_name, '[CANDIDATE_NAME]', $masked);
          
          // Mask First Name part to be safe
          $parts = explode(' ', $app->full_name);
          if (count($parts) > 0 && strlen($parts[0]) > 2) {
               $masked = str_ireplace($parts[0], '[CANDIDATE]', $masked);
          }
      }

      return $masked;
  }
}