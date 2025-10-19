<?php

namespace App\Filament\Resources\WhatsAppMessageResource\Pages;

use App\Filament\Resources\WhatsAppMessageResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;

class ViewWhatsAppMessage extends ViewRecord
{
    protected static string $resource = WhatsAppMessageResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('resend')
                ->label('Kirim Ulang')
                ->icon('heroicon-o-paper-airplane')
                ->color('warning')
                ->requiresConfirmation()
                ->modalHeading('Kirim Ulang Pesan?')
                ->modalDescription('Pesan akan dikirim ulang ke pelanggan.')
                ->action(function () {
                    $message = $this->record;
                    
                    // Resend logic here
                    try {
                        $whatsappService = new \App\Services\WhatsAppService();
                        $result = $whatsappService->sendMessage(
                            $message->customer->phone,
                            $message->message
                        );
                        
                        if ($result['success']) {
                            $message->update([
                                'status' => 'sent',
                                'sent_at' => now(),
                                'response' => json_encode($result),
                            ]);
                            
                            \Filament\Notifications\Notification::make()
                                ->title('Pesan Berhasil Dikirim Ulang')
                                ->success()
                                ->send();
                        } else {
                            throw new \Exception($result['error'] ?? 'Gagal mengirim pesan');
                        }
                    } catch (\Exception $e) {
                        \Filament\Notifications\Notification::make()
                            ->title('Gagal Mengirim Ulang')
                            ->body($e->getMessage())
                            ->danger()
                            ->send();
                    }
                })
                ->visible(fn ($record) => in_array($record->status, ['failed', 'pending'])),
        ];
    }
}

