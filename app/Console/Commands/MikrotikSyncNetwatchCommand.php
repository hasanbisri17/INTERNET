<?php

namespace App\Console\Commands;

use App\Services\MikrotikNetwatchService;
use Illuminate\Console\Command;

class MikrotikSyncNetwatchCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'mikrotik:sync-netwatch';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Sync Netwatch entries dari MikroTik ke database';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->info('Starting Netwatch sync from MikroTik...');

        $service = new MikrotikNetwatchService();
        $result = $service->syncAllNetwatch();

        if ($result['success']) {
            $this->info("✅ {$result['message']}");
            
            if (!empty($result['errors'])) {
                $this->warn("\n⚠️  Errors:");
                foreach ($result['errors'] as $error) {
                    $this->error("  - {$error}");
                }
            }
            
            return Command::SUCCESS;
        }

        $this->error("❌ Sync failed: {$result['message']}");
        return Command::FAILURE;
    }
}

