<?php

namespace App\Filament\Resources\MikrotikDeviceResource\Pages;

use App\Filament\Resources\MikrotikDeviceResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListMikrotikDevices extends ListRecords
{
    protected static string $resource = MikrotikDeviceResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}