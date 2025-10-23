<?php

namespace App\Console\Commands;

use App\Models\MikrotikDevice;
use App\Services\AutoIsolirService;
use Illuminate\Console\Command;

class AutoIsolirCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'mikrotik:auto-isolir {device_id?}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Process auto isolir for expired customers';

    protected AutoIsolirService $autoIsolirService;

    public function __construct(AutoIsolirService $autoIsolirService)
    {
        parent::__construct();
        $this->autoIsolirService = $autoIsolirService;
    }

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        if (!config('mikrotik.auto_isolir.enabled', true)) {
            $this->info('Auto isolir is disabled');
            return self::SUCCESS;
        }

        $deviceId = $this->argument('device_id');

        if ($deviceId) {
            return $this->processDevice($deviceId);
        }

        return $this->processAllDevices();
    }

    protected function processDevice(int $deviceId): int
    {
        $device = MikrotikDevice::find($deviceId);

        if (!$device) {
            $this->error("Device with ID {$deviceId} not found");
            return self::FAILURE;
        }

        $this->info("Processing auto isolir for: {$device->name}");
        $result = $this->autoIsolirService->processDevice($device);

        if ($result['success']) {
            $this->info("✓ Isolated: {$result['isolated']}, Restored: {$result['restored']}");
            
            if (!empty($result['errors'])) {
                $this->newLine();
                $this->error('Errors:');
                foreach ($result['errors'] as $error) {
                    $this->line("  - {$error}");
                }
            }
        } else {
            $this->error("✗ {$result['message']}");
        }

        return self::SUCCESS;
    }

    protected function processAllDevices(): int
    {
        $this->info('Processing auto isolir for all devices...');
        $result = $this->autoIsolirService->processAllDevices();

        if (!$result['success']) {
            $this->error($result['message']);
            return self::FAILURE;
        }

        $totalIsolated = 0;
        $totalRestored = 0;
        $allErrors = [];

        foreach ($result['results'] as $deviceId => $deviceResult) {
            if (isset($deviceResult['isolated'])) {
                $totalIsolated += $deviceResult['isolated'];
            }
            if (isset($deviceResult['restored'])) {
                $totalRestored += $deviceResult['restored'];
            }
            if (!empty($deviceResult['errors'])) {
                $allErrors = array_merge($allErrors, $deviceResult['errors']);
            }
        }

        $this->newLine();
        $this->info("Summary:");
        $this->line("  Devices processed: " . count($result['results']));
        $this->line("  Total isolated: {$totalIsolated}");
        $this->line("  Total restored: {$totalRestored}");

        if (!empty($allErrors)) {
            $this->newLine();
            $this->error('Errors occurred:');
            foreach ($allErrors as $error) {
                $this->line("  - {$error}");
            }
        }

        return self::SUCCESS;
    }
}

