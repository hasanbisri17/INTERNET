<?php

namespace App\Filament\Resources\AutoIsolirConfigResource\Pages;

use App\Filament\Resources\AutoIsolirConfigResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListAutoIsolirConfigs extends ListRecords
{
    protected static string $resource = AutoIsolirConfigResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}

