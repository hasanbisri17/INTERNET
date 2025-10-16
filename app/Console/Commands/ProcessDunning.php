<?php

namespace App\Console\Commands;

use App\Services\DunningService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class ProcessDunning extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'dunning:process 
                            {--dry-run : Preview what will be processed without triggering webhooks}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Process dunning for overdue payments and trigger n8n webhooks for suspend actions';

    protected DunningService $dunningService;

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->dunningService = app(DunningService::class);
        $isDryRun = $this->option('dry-run');

        $this->info('=== Dunning Process (n8n Integration) ===');
        $this->info('Date: ' . now()->format('d F Y H:i:s'));
        
        if ($isDryRun) {
            $this->warn('🔍 DRY RUN MODE - No webhooks will be triggered');
        }

        $this->newLine();
        $this->line('Processing overdue payments...');
        $this->line(str_repeat('─', 60));

        // Process dunning with config
        $result = $this->dunningService->processDunningWithConfig();

        if (!$result['success']) {
            $this->error('❌ ' . $result['message']);
            return Command::FAILURE;
        }

        $this->newLine();
        $this->info('=== Summary ===');
        $this->line("📋 Total Payments Processed: {$result['total_processed']}");
        $this->line("🚀 Total n8n Webhooks Triggered: {$result['total_triggered']}");
        
        if ($result['total_triggered'] > 0) {
            $this->info('✅ Dunning process completed successfully!');
        } else {
            $this->comment('ℹ️  No webhooks triggered (no payments met the criteria)');
        }

        return Command::SUCCESS;
    }
}
