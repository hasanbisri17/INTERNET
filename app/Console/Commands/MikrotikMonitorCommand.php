<?php

namespace App\Console\Commands;

use App\Models\MikrotikDevice;
use App\Services\MikrotikMonitoringService;
use Illuminate\Console\Command;

class MikrotikMonitorCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'mikrotik:monitor {device_id?}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Monitor Mikrotik devices and log their status';

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
        if (!config('mikrotik.monitoring.enabled', true)) {
            $this->info('Monitoring is disabled');
            return self::SUCCESS;
        }

        $deviceId = $this->argument('device_id');

        if ($deviceId) {
            return $this->monitorSingleDevice($deviceId);
        }

        return $this->monitorAllDevices();
    }

    protected function monitorSingleDevice(int $deviceId): int
    {
        $device = MikrotikDevice::find($deviceId);

        if (!$device) {
            $this->error("Device with ID {$deviceId} not found");
            return self::FAILURE;
        }

        $this->info("Monitoring device: {$device->name}");
        $result = $this->monitoringService->checkDeviceStatus($device);

        if ($result['success']) {
            $this->info("✓ {$device->name} is {$result['status']}");
        } else {
            $this->error("✗ {$device->name} is {$result['status']}: {$result['message']}");
        }

        return self::SUCCESS;
    }

    protected function monitorAllDevices(): int
    {
        $devices = MikrotikDevice::where('is_active', true)->get();

        if ($devices->isEmpty()) {
            $this->info('No active devices found');
            return self::SUCCESS;
        }

        $this->info("Monitoring {$devices->count()} devices...");

        $online = 0;
        $offline = 0;

        foreach ($devices as $device) {
            $result = $this->monitoringService->checkDeviceStatus($device);

            if ($result['success']) {
                $online++;
                $this->line("✓ {$device->name} is online");
            } else {
                $offline++;
                $this->error("✗ {$device->name} is offline: {$result['message']}");
            }
        }

        $this->newLine();
        $this->info("Summary: {$online} online, {$offline} offline");

        return self::SUCCESS;
    }
}

