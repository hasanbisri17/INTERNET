<?php

namespace App\Filament\Resources\AAAConfigResource\Pages;

use App\Filament\Resources\AAAConfigResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListAAAConfigs extends ListRecords
{
    protected static string $resource = AAAConfigResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}