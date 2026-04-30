<?php

namespace App\Console\Commands;

use App\Models\Application;
use App\Models\JobVacancy;
use App\Jobs\ProcessCvScreening;
use Illuminate\Console\Command;

class RescreenCvCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:rescreen-cv {job_id : The ID of the Job Vacancy}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Trigger AI re-screening for all applications in a specific job vacancy using the new Gemini model.';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $jobId = $this->argument('job_id');
        $job = JobVacancy::find($jobId);

        if (!$job) {
            $this->error("Job Vacancy with ID {$jobId} not found.");
            return 1;
        }

        $applications = Application::where('job_vacancy_id', $jobId)->get();
        $count = $applications->count();

        if ($count === 0) {
            $this->info("No applications found for job: {$job->title}");
            return 0;
        }

        $this->info("Starting re-screening for {$count} applications for job: {$job->title}...");
        $bar = $this->output->createProgressBar($count);
        $bar->start();

        foreach ($applications as $app) {
            ProcessCvScreening::dispatch($app);
            $bar->advance();
        }

        $bar->finish();
        $this->newLine();
        $this->info("Successfully dispatched {$count} CVs to the background queue for re-screening.");
        $this->info("Please ensure your queue worker is running: php artisan queue:work");

        return 0;
    }
}
