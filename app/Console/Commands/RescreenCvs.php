<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class RescreenCvs extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:rescreen-cvs';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Re-run AI CV screening for all applications';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $applications = \App\Models\Application::whereNotNull('cv_path')->get();

        if ($applications->isEmpty()) {
            $this->info('No applications found with a CV path.');
            return;
        }

        $this->info("Dispatching CV screening for {$applications->count()} applications...");

        $bar = $this->output->createProgressBar($applications->count());
        $bar->start();

        foreach ($applications as $application) {
            $application->update([
                'ai_score' => null,
                'ai_analysis' => null,
                'status' => 'pending'
            ]);
            \App\Jobs\ProcessCvScreening::dispatch($application);
            $bar->advance();
        }

        $bar->finish();
        $this->newLine();
        $this->info('All CV screening jobs have been dispatched to the queue.');
    }
}
