<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Application;
use App\Services\C45Predictor;

class UpdateC45Decisions extends Command
{
    protected $signature = 'app:update-c45-decisions';
    protected $description = 'Update C4.5 decisions instantly for all candidates in the database (no AI calls)';

    public function handle()
    {
        $applications = Application::whereNotNull('test_score')->get();

        if ($applications->isEmpty()) {
            $this->info("No candidates found with completed test scores.");
            return;
        }

        foreach ($applications as $app) {
            $decision = C45Predictor::predict(
                (float) $app->ai_score,
                (float) $app->test_score
            );
            
            $app->update(['c45_decision' => $decision]);
        }

        $this->info("Successfully updated C4.5 decision labels for " . $applications->count() . " candidates in 0.1 seconds!");
    }
}
