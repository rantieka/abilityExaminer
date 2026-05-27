<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Application;
use App\Services\C45Predictor;
use App\Models\Setting;

class VerifyC45Decisions extends Command
{
  protected $signature = 'app:verify-c45';
  protected $description = 'Verifikasi dan tampilkan kecocokan hasil klasifikasi C4.5 untuk semua pelamar di database';

  public function handle() {
    $applications = Application::whereNotNull('test_score')->get();

    if ($applications->isEmpty()) {
      $this->error("Tidak ada pelamar yang memiliki skor ujian.");
      return;
    }

    $aiThreshold = (float) Setting::get('c45_ai_threshold', 57.0);
    $testThreshold = (float) Setting::get('c45_test_threshold', 63.0);
    $confidenceThreshold = (float) Setting::get('c45_confidence_threshold', 80.0);

    $this->info("Parameter C4.5 Aktif di Database");
    $this->line("Ambang Batas AI Score: $aiThreshold%");
    $this->line("Ambang Batas Ujian Score: $testThreshold");
    $this->line("Batas Alert Confidence: $confidenceThreshold%");
    $this->line("\n");

    $rows = [];
    $matchedCount = 0;
    $alertCount = 0;

    foreach ($applications as $app) {
      // Prediksi dinamis
      $predictedDecision = C45Predictor::predict((float) $app->ai_score, (float) $app->test_score);
      $confidence = C45Predictor::getConfidence((float) $app->ai_score, (float) $app->test_score);

      // Cek apakah status c45_decision di database sama dengan prediksi dinamis
      $isMatch = ($app->c45_decision === $predictedDecision) ? 'Match' : 'MISMATCH';
      if ($isMatch === 'Match') {
        $matchedCount++;
      }

      // Apakah kena alert review manual?
      $needReview = ($confidence <= $confidenceThreshold) ? 'Ya (Alert)' : 'Tidak';
      if ($confidence <= $confidenceThreshold) {
        $alertCount++;
      }

      $rows[] = [
        $app->id,
        $app->full_name,
        $app->ai_score . '%',
        $app->test_score,
        $app->c45_decision ?? 'Belum ada',
        $predictedDecision,
        $confidence . '%',
        $needReview,
        $isMatch
      ];
    }

    // Tampilkan 15 sampel teratas
    $this->info("Menampilkan sampel pelamar (maksimal 15 data):");
    $this->table(
      ['ID', 'Nama Pelamar', 'Skor AI', 'Skor Ujian', 'DB Decision', 'Predict Decision', 'Confidence', 'Perlu Review?', 'Status Match'],
      array_slice($rows, 0, 15)
    );

    $this->info("Ringkasan Hasil Verifikasi");
    $this->info("Total Pelamar diuji: " . $applications->count());
    $this->info("Prediksi Cocok dengan Database: $matchedCount/" . $applications->count());
    $this->info("Jumlah Pelamar 'Perlu Review Manual' (Confidence <= $confidenceThreshold%): $alertCount");
    
    if ($matchedCount === $applications->count()) {
      $this->info("Semua data terverifikasi 100% cocok dengan model Weka!");
    } else {
      $this->error("Terdeteksi ketidakcocokan antara database dan prediksi C4.5 baru. Harap jalankan 'php artisan app:update-c45-decisions' untuk menyelaraskannya.");
    }
  }
}
