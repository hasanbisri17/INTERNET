<?php

namespace App\Filament\Resources\AutoIsolirConfigResource\Pages;

use App\Filament\Resources\AutoIsolirConfigResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditAutoIsolirConfig extends EditRecord
{
    protected static string $resource = AutoIsolirConfigResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}

