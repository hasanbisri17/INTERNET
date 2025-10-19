<?php

namespace App\Filament\Resources\DunningConfigResource\Pages;

use App\Filament\Resources\DunningConfigResource;
use Filament\Resources\Pages\CreateRecord;

class CreateDunningConfig extends CreateRecord
{
    protected static string $resource = DunningConfigResource::class;
    
    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}