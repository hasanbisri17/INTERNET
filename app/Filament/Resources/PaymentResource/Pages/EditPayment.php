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
        // Jika status pembayaran adalah 'paid', kirim notifikasi WhatsApp WITH PDF INVOICE
        if ($this->record->status === 'paid' && $this->record->payment_date) {
            try {
                $whatsapp = new \App\Services\WhatsAppService();
                $whatsapp->sendBillingNotification($this->record, 'paid', true); // true = send PDF invoice lunas
                
                \Filament\Notifications\Notification::make()
                    ->success()
                    ->title('Notifikasi WhatsApp Terkirim')
                    ->body('Notifikasi pembayaran + invoice PDF berhasil dikirim ke pelanggan.')
                    ->send();
            } catch (\Exception $e) {
                \Filament\Notifications\Notification::make()
                    ->warning()
                    ->title('Notifikasi WhatsApp Gagal')
                    ->body('Gagal mengirim notifikasi WhatsApp: ' . $e->getMessage())
                    ->send();
                
                \Illuminate\Support\Facades\Log::error('Gagal mengirim notifikasi WhatsApp pembayaran', [
                    'payment_id' => $this->record->id,
                    'error' => $e->getMessage()
                ]);
            }
        }
    }
}
