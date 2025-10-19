<?php

namespace App\Filament\Resources\CustomerPortalConfigResource\Pages;

use App\Filament\Resources\CustomerPortalConfigResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditCustomerPortalConfig extends EditRecord
{
    protected static string $resource = CustomerPortalConfigResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}