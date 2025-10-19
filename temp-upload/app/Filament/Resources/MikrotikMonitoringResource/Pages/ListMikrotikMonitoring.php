<?php

namespace App\Filament\Resources\MikrotikMonitoringResource\Pages;

use App\Filament\Resources\MikrotikMonitoringResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListMikrotikMonitoring extends ListRecords
{
    protected static string $resource = MikrotikMonitoringResource::class;

    protected function getHeaderActions(): array
    {
        return [
            // Tidak perlu action tambahan
        ];
    }
}