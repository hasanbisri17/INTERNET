<?php

namespace App\Console\Commands;

use App\Models\MikrotikDevice;
use App\Services\MikrotikIpBindingService;
use Illuminate\Console\Command;

class MikrotikSyncIpBindingsCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'mikrotik:sync-ip-bindings 
                            {device? : ID perangkat MikroTik (kosongkan untuk sync semua)}
                            {--all : Sync semua perangkat}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Sync IP Bindings dari MikroTik ke database';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('🔄 Memulai sinkronisasi IP Bindings dari MikroTik...');
        $this->newLine();

        $service = new MikrotikIpBindingService();
        
        $deviceId = $this->argument('device');
        $syncAll = $this->option('all');

        if ($deviceId) {
            // Sync specific device
            $device = MikrotikDevice::find($deviceId);
            
            if (!$device) {
                $this->error("❌ Perangkat MikroTik dengan ID {$deviceId} tidak ditemukan!");
                return 1;
            }
            
            $this->syncDevice($device, $service);
        } elseif ($syncAll) {
            // Sync all active devices
            $devices = MikrotikDevice::where('is_active', true)->get();
            
            if ($devices->isEmpty()) {
                $this->warn('⚠️  Tidak ada perangkat MikroTik yang aktif.');
                return 0;
            }
            
            $this->info("📡 Ditemukan {$devices->count()} perangkat aktif");
            $this->newLine();
            
            foreach ($devices as $device) {
                $this->syncDevice($device, $service);
                $this->newLine();
            }
        } else {
            // Ask user to select device
            $devices = MikrotikDevice::where('is_active', true)->get();
            
            if ($devices->isEmpty()) {
                $this->warn('⚠️  Tidak ada perangkat MikroTik yang aktif.');
                $this->info('💡 Gunakan opsi --all untuk sync semua perangkat.');
                return 0;
            }
            
            $deviceOptions = $devices->pluck('name', 'id')->toArray();
            $deviceOptions['all'] = '🌐 Semua Perangkat';
            
            $selected = $this->choice(
                'Pilih perangkat MikroTik',
                $deviceOptions,
                'all'
            );
            
            if ($selected === '🌐 Semua Perangkat') {
                $this->newLine();
                foreach ($devices as $device) {
                    $this->syncDevice($device, $service);
                    $this->newLine();
                }
            } else {
                $device = $devices->firstWhere('name', $selected);
                $this->newLine();
                $this->syncDevice($device, $service);
            }
        }

        $this->newLine();
        $this->info('✅ Sinkronisasi selesai!');
        
        return 0;
    }

    /**
     * Sync single device
     *
     * @param MikrotikDevice $device
     * @param MikrotikIpBindingService $service
     * @return void
     */
    protected function syncDevice(MikrotikDevice $device, MikrotikIpBindingService $service): void
    {
        $this->line("📡 Syncing: <fg=cyan>{$device->name}</> ({$device->ip_address})");
        
        try {
            $result = $service->syncAllBindings($device);
            
            if ($result['success']) {
                $this->info("   ✓ {$result['message']}");
            } else {
                $this->error("   ✗ Gagal: {$result['message']}");
            }
        } catch (\Exception $e) {
            $this->error("   ✗ Error: {$e->getMessage()}");
        }
    }
}

