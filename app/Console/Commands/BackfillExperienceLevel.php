<?php

namespace App\Console\Commands;

use App\Models\Application;
use Illuminate\Console\Command;

class BackfillExperienceLevel extends Command
{
  protected $signature = 'app:backfill-experience-level {--dry-run : Show what would be updated without making changes}';
  protected $description = 'Backfill experience_level column from existing ai_analysis data';

  public function handle(): int
  {
    $dryRun = $this->option('dry-run');

    $applications = Application::whereNotNull('ai_analysis')->get();

    if ($applications->isEmpty()) {
      $this->info('No applications with ai_analysis found.');
      return self::SUCCESS;
    }

    $this->info("Found {$applications->count()} applications to process.");

    $bar = $this->output->createProgressBar($applications->count());
    $bar->start();

    $updated = 0;

    foreach ($applications as $app) {
      $expYears = $app->ai_analysis['extracted_data']['experience_years'] ?? 0;
      $expLevel = $this->computeExpLevel((float) $expYears);

      if ($dryRun) {
          $this->line("App ID {$app->id}: {$expYears} years -> {$expLevel}");
      } else {
          $app->experience_level = $expLevel;
          $app->save();
      }

      $updated++;
      $bar->advance();
    }

    $bar->finish();
    $this->newLine(2);

    if ($dryRun) {
      $this->info("Dry run complete. {$updated} records would be updated.");
      $this->info('Run without --dry-run to apply changes.');
    } else {
      $this->info("Backfill complete. {$updated} records updated.");
    }

    return self::SUCCESS;
  }

  private function computeExpLevel(float $expYears): string {
    $normalizedYear = floor($expYears); // Normalize to kill decimal noise
    return match (true) {
      $normalizedYear == 0   => 'fresher',
      $normalizedYear <= 0.5 => 'newcomer',
      $normalizedYear <= 1   => 'junior',
      $normalizedYear <= 2   => 'early_career',
      $normalizedYear <= 5   => 'mid_level',
      default          => 'senior',
    };
  }
}