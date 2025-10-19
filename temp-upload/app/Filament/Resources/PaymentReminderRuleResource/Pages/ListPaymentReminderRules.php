<?php

namespace App\Filament\Resources\PaymentReminderRuleResource\Pages;

use App\Filament\Resources\PaymentReminderRuleResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListPaymentReminderRules extends ListRecords
{
    protected static string $resource = PaymentReminderRuleResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
