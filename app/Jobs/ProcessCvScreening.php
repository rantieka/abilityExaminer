<?php

namespace App\Jobs;

use App\Models\Application;
use App\Services\GroqService;
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

    public $tries = 3;
    public $backoff = [10, 30, 60];

    public $application;

    public function __construct(Application $application)
    {
        $this->application = $application;
    }

    public function handle(GroqService $groq, Parser $parser): void
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

            // prepare prompt
            $job = $this->application->jobVacancy;
            // clean html tags from qualifications because Groq need plain text
            $qualifications = strip_tags($job->qualifications); 

            $prompt = "
                Anda adalah HR Assistant yang ahli. Tugas Anda adalah mencocokkan CV Kandidat dengan Kualifikasi Lowongan.
                
                LOWONGAN: {$job->title}
                KUALIFIKASI: 
                {$qualifications}

                CV KANDIDAT:
                {$cvText}

                Instruksi:
                1. Analisis relevansi skill kandidat dengan kualifikasi dalam Bahasa Indonesia.
                2. Berikan skor kesesuaian (0-100).
                3. Berikan analisis singkat (summary, pros, cons) dalam Bahasa Indonesia.
                
                Format Output WAJIB JSON (Tanpa markdown ```json):
                {
                    \"score\": 85,
                    \"analysis\": {
                        \"summary\": \"Kandidat sangat cocok karena memiliki pengalaman 3 tahun di Laravel...\",
                        \"pros\": [\"Menguasai PHP Expert\", \"Pengalaman Lead Team\"],
                        \"cons\": [\"Tidak bisa onsite\"]
                    }
                }
            ";

            // send to groq
            $messages = [
                ['role' => 'system', 'content' => 'Anda adalah AI Assistant yang hanya merespon dengan format JSON. Gunakan Bahasa Indonesia.'],
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
}