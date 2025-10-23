<?php

namespace App\Filament\Resources\MikrotikIpBindingResource\Pages;

use App\Filament\Resources\MikrotikIpBindingResource;
use Filament\Resources\Pages\CreateRecord;

class CreateMikrotikIpBinding extends CreateRecord
{
    protected static string $resource = MikrotikIpBindingResource::class;
    
    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}

