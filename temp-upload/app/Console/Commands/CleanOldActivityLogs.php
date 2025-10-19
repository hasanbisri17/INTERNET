<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Spatie\Activitylog\Models\Activity;
use Carbon\Carbon;

class CleanOldActivityLogs extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'activitylog:clean {--days=180 : Number of days to keep logs}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Clean old activity logs older than specified days';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $days = (int) $this->option('days');
        
        $this->info("Starting cleanup of activity logs older than {$days} days...");
        
        // Calculate cutoff date
        $cutoffDate = Carbon::now()->subDays($days);
        
        // Count logs to be deleted
        $count = Activity::where('created_at', '<', $cutoffDate)->count();
        
        if ($count === 0) {
            $this->info('No old logs to clean up.');
            return Command::SUCCESS;
        }
        
        // Ask for confirmation if running interactively
        if ($this->input->isInteractive()) {
            if (!$this->confirm("This will delete {$count} activity log(s). Do you want to continue?")) {
                $this->info('Operation cancelled.');
                return Command::FAILURE;
            }
        }
        
        // Delete old logs
        $deleted = Activity::where('created_at', '<', $cutoffDate)->delete();
        
        $this->info("Successfully deleted {$deleted} activity log(s) older than {$days} days.");
        $this->info("Cutoff date: {$cutoffDate->format('Y-m-d H:i:s')}");
        
        return Command::SUCCESS;
    }
}

