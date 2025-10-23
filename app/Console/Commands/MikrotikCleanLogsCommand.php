<?php

namespace App\Console\Commands;

use App\Services\MikrotikMonitoringService;
use Illuminate\Console\Command;

class MikrotikCleanLogsCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'mikrotik:clean-logs {--days=30 : Number of days to keep}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Clean old Mikrotik monitoring logs';

    protected MikrotikMonitoringService $monitoringService;

    public function __construct(MikrotikMonitoringService $monitoringService)
    {
        parent::__construct();
        $this->monitoringService = $monitoringService;
    }

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $days = (int) $this->option('days');

        if ($days < 1) {
            $this->error('Days must be greater than 0');
            return self::FAILURE;
        }

        $this->info("Cleaning monitoring logs older than {$days} days...");

        $result = $this->monitoringService->cleanOldLogs($days);

        if ($result['success']) {
            $this->info("✓ Deleted {$result['deleted']} old monitoring logs");
            return self::SUCCESS;
        }

        $this->error("✗ {$result['message']}");
        return self::FAILURE;
    }
}

