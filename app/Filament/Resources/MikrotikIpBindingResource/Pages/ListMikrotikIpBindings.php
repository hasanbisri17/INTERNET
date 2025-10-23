<?php

namespace App\Filament\Resources\MikrotikIpBindingResource\Pages;

use App\Filament\Resources\MikrotikIpBindingResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListMikrotikIpBindings extends ListRecords
{
    protected static string $resource = MikrotikIpBindingResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}

