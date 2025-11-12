<?php

namespace App\Filament\Resources\DebtResource\Pages;

use App\Filament\Resources\DebtResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
use Filament\Notifications\Notification;

class EditDebt extends EditRecord
{
    protected static string $resource = DebtResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        // Validasi saldo KAS hanya jika amount berubah dan lebih besar dari saldo
        $originalAmount = $this->record->amount;
        if (isset($data['amount']) && $data['amount'] != $originalAmount) {
            $cashBalance = DebtResource::getCurrentCashBalance();
            if ($data['amount'] > $cashBalance) {
                Notification::make()
                    ->danger()
                    ->title('Error')
                    ->body("Jumlah hutang (Rp " . number_format($data['amount'], 2) . ") melebihi saldo KAS saat ini (Rp " . number_format($cashBalance, 2) . ").")
                    ->persistent()
                    ->send();
                
                throw new \Exception("Jumlah hutang melebihi saldo KAS.");
            }
        }

        return $data;
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
