<?php

namespace App\Filament\Resources\DunningConfigResource\Pages;

use App\Filament\Resources\DunningConfigResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditDunningConfig extends EditRecord
{
    protected static string $resource = DunningConfigResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}