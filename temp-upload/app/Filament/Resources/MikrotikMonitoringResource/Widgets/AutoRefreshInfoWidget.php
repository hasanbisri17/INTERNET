<?php

namespace App\Filament\Resources\MikrotikMonitoringResource\Widgets;

use Filament\Widgets\Widget;

class AutoRefreshInfoWidget extends Widget
{
    protected static string $view = 'filament.resources.mikrotik-monitoring-resource.widgets.auto-refresh-info-widget';
    
    protected int $pollingInterval = 5; // Interval dalam detik
    
    public function getPollingInterval(): int
    {
        return $this->pollingInterval;
    }
}