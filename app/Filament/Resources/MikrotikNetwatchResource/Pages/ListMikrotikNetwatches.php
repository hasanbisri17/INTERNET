<?php

namespace App\Filament\Resources\MikrotikNetwatchResource\Pages;

use App\Filament\Resources\MikrotikNetwatchResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListMikrotikNetwatches extends ListRecords
{
    protected static string $resource = MikrotikNetwatchResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}

