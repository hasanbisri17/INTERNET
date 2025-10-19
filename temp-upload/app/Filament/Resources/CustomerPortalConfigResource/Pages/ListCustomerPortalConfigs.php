<?php

namespace App\Filament\Resources\CustomerPortalConfigResource\Pages;

use App\Filament\Resources\CustomerPortalConfigResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListCustomerPortalConfigs extends ListRecords
{
    protected static string $resource = CustomerPortalConfigResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}