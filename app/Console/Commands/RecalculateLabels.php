<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Application;

class RecalculateLabels extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:recalculate-labels';

    /**
     * The description of the console command.
     *
     * @var string
     */
    protected $description = 'Recalculate screening_label and experience_level based on current logic without re-screening';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $applications = Application::whereNotNull('ai_score')->get();
        $count = $applications->count();

        if ($count === 0) {
            $this->info('No applications found with ai_score. Nothing to recalculate.');
            return;
        }

        $this->info("Recalculating labels for {$count} applications...");
        $bar = $this->output->createProgressBar($count);

        $updatedCount = 0;

        foreach ($applications as $app) {
            $score = $app->ai_score;
            $oldLabel = $app->screening_label;
            
            // New logic: Threshold 60
            $newLabel = $score >= 60 ? 'suitable' : 'not_suitable';
            
            // Sync experience_level just in case
            $expYears = (float)($app->ai_analysis['extracted_data']['experience_years'] ?? 0);
            $newExpLevel = $this->getExperienceLevel($expYears);

            if ($oldLabel !== $newLabel || $app->experience_level !== $newExpLevel) {
                $app->update([
                    'screening_label' => $newLabel,
                    'experience_level' => $newExpLevel
                ]);
                $updatedCount++;
            }

            $bar->advance();
        }

        $bar->finish();
        $this->newLine();
        $this->info("Successfully recalculated. {$updatedCount} labels were updated.");
    }

    /**
     * Mirror of the logic in ProcessCvScreening.php
     */
    protected function getExperienceLevel(float $expYears): string {
        $normalizedYear = floor($expYears);
        return match (true) {
            $normalizedYear == 0   => 'fresher',
            $normalizedYear <= 1  => 'newcomer',
            $normalizedYear <= 2  => 'junior',
            $normalizedYear <= 5  => 'early_career',
            $normalizedYear <= 10 => 'mid_level',
            default                => 'senior',
        };
    }
}
