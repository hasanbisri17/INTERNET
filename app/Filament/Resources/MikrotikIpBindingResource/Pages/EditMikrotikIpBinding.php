<?php

namespace App\Filament\Resources\MikrotikIpBindingResource\Pages;

use App\Filament\Resources\MikrotikIpBindingResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditMikrotikIpBinding extends EditRecord
{
    protected static string $resource = MikrotikIpBindingResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
    
    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}

