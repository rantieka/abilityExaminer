<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Application;
use App\Services\C45Predictor;

class ExportDataset extends Command
{
  protected $signature = 'app:export-dataset';
  protected $description = 'Export the 100-record dataset to a CSV file for research';

  public function handle(){
    $applications = Application::all();
    $filePath = base_path('export_dataset_100_clean.csv');
    $handle = fopen($filePath, 'w');

    // CSV Header
    fputcsv($handle, [
      'Full Name', 
      'AI Score (CV)', 
      'Test Score (Exam)', 
      'Experience Level', 
      'Screening Label',
      'Decision (Label C4.5)'
    ]);

    foreach ($applications as $app) {
      // Get actual C4.5 decision without noise
      $decision = $app->c45_decision ?? C45Predictor::predict(
        (float) $app->ai_score,
        (float) $app->test_score
      );

      fputcsv($handle, [
        $app->full_name,
        $app->ai_score,
        $app->test_score,
        $app->experience_level,
        $app->screening_label,
        $decision
      ]);
    }

    fclose($handle);
    $this->info("Dataset successfully exported to: {$filePath} (Clean, without noise).");
  }
}

