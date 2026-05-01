<?php

namespace App\Console\Commands;

use App\Models\Application;
use App\Jobs\ProcessCvScreening;
use Illuminate\Console\Command;

class RescreenSingleCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:rescreen-single {id : The ID of the Application}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Trigger AI re-screening for a single specific application ID.';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $id = $this->argument('id');
        $application = Application::find($id);

        if (!$application) {
            $this->error("Application with ID {$id} not found.");
            return 1;
        }

        $this->info("Dispatching Application ID {$id} ({$application->full_name}) for re-screening...");
        
        ProcessCvScreening::dispatch($application);

        $this->info("Job successfully dispatched to the queue.");
        $this->info("Please monitor your queue worker and logs for results.");

        return 0;
    }
}
