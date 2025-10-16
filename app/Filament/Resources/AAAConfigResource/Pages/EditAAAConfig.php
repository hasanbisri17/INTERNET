<?php

namespace App\Filament\Resources\AAAConfigResource\Pages;

use App\Filament\Resources\AAAConfigResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditAAAConfig extends EditRecord
{
    protected static string $resource = AAAConfigResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}