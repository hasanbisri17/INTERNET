<?php

namespace App\Filament\Resources\MikrotikMonitoringResource\Pages;

use App\Models\MikrotikDevice;

use App\Filament\Resources\MikrotikMonitoringResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;
use Illuminate\Support\Facades\Blade;
use Filament\Support\Enums\MaxWidth;

class ViewMikrotikMonitoring extends ViewRecord
{
    protected static string $resource = MikrotikMonitoringResource::class;
    
    protected static string $view = 'filament.resources.mikrotik-monitoring-resource.pages.view-mikrotik-monitoring';
    
    public function mount($record): void
    {
        parent::mount($record);
        
        // Simpan device ID ke session untuk diakses oleh widget
        session(['current_mikrotik_device_id' => $this->record->id]);
    }
    
    protected function getHeaderActions(): array
    {
        return [];
    }
    

    
    public function getHeaderWidgetsColumns(): int | array
    {
        return 1;
    }
    
    public function getPollingInterval(): int
    {
        return $this->pollingInterval;
    }
    
    protected function getViewData(): array
    {
        return [
            ...parent::getViewData(),
            'autoRefreshScript' => '<script>
                document.addEventListener("DOMContentLoaded", function() {
                    setInterval(function() {
                        // Refresh halaman setiap 5 detik
                        window.location.reload();
                    }, 5000);
                });
            </script>',
        ];
    }
    
    protected function getFooterWidgets(): array
    {
        return [
            // Traffic chart telah dihapus
        ];
    }
    
    public function getRecord(): MikrotikDevice
    {
        return $this->record;
    }
    
    protected function getHeaderWidgets(): array
    {
        return [
            MikrotikMonitoringResource\Widgets\AutoRefreshInfoWidget::class,
        ];
    }
    
    public function getMaxContentWidth(): MaxWidth
    {
        return MaxWidth::SevenExtraLarge;
    }
    

}