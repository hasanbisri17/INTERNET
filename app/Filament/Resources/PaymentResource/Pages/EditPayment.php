<?php

namespace App\Filament\Resources\PaymentResource\Pages;

use App\Filament\Resources\PaymentResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditPayment extends EditRecord
{
    protected static string $resource = PaymentResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make()
                ->visible(fn () => $this->record->status !== 'paid')
                ->before(function () {
                    if ($this->record->status === 'paid') {
                        \Filament\Notifications\Notification::make()
                            ->danger()
                            ->title('Tagihan tidak dapat dihapus')
                            ->body('Tagihan yang sudah dibayar tidak dapat dihapus karena sudah memiliki data pembayaran.')
                            ->send();
                            
                        $this->halt();
                    }
                }),
        ];
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        // If payment_date is set, automatically set status to paid
        if (!empty($data['payment_date'])) {
            $data['status'] = 'paid';
        }
        
        // If due date has passed and no payment, set to overdue
        if (empty($data['payment_date']) && strtotime($data['due_date']) < time()) {
            $data['status'] = 'overdue';
        }

        return $data;
    }
    
    protected function afterSave(): void
    {
        // WhatsApp notification dengan PDF invoice sudah ditangani oleh PaymentObserver
        // saat status berubah menjadi 'paid'.
        // Tidak perlu kirim ulang di sini untuk menghindari duplikasi.
        
        // Hanya tampilkan notifikasi sukses jika status adalah paid
        if ($this->record->status === 'paid' && $this->record->payment_date) {
            \Filament\Notifications\Notification::make()
                ->success()
                ->title('Pembayaran Berhasil Disimpan')
                ->body('Notifikasi WhatsApp + invoice PDF akan dikirim otomatis ke pelanggan.')
                ->send();
        }
    }
}
