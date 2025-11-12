<?php

namespace App\Filament\Resources\ReceivableResource\Pages;

use App\Filament\Resources\ReceivableResource;
use App\Services\WhatsAppService;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class CreateReceivable extends CreateRecord
{
    protected static string $resource = ReceivableResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['created_by'] = Auth::id();
        $data['paid_amount'] = 0;
        $data['status'] = 'pending';

        return $data;
    }

    protected function afterCreate(): void
    {
        // Send WhatsApp notification for new receivable
        try {
            $receivable = $this->record;
            
            // Get debtor contact (prefer phone number for WhatsApp)
            $contact = null;
            
            // If debtor_type is 'customer', get phone from customer relationship
            if ($receivable->debtor_type === 'customer' && $receivable->debtorCustomer) {
                $contact = $receivable->debtorCustomer->phone;
            }
            // If debtor_type is 'user', get phone from user relationship
            elseif ($receivable->debtor_type === 'user' && $receivable->debtorUser) {
                $contact = $receivable->debtorUser->phone;
            }
            
            // Fallback to debtor_contact field if no phone from relationship
            if (empty($contact)) {
                $contact = $receivable->debtor_contact;
                // Check if it's a phone number (starts with digit or +)
                if (!empty($contact) && !preg_match('/^[0-9+]/', $contact)) {
                    $contact = null; // Not a phone number, skip
                }
            }
            
            // Only send notification if contact (phone number) is available
            if (!empty($contact) && preg_match('/^[0-9+]/', $contact)) {
                $whatsapp = new WhatsAppService();
                
                // Format message for new receivable notification
                $amount = number_format($receivable->amount, 0, ',', '.');
                $dueDate = Carbon::parse($receivable->due_date)->format('d F Y');
                $daysUntilDue = Carbon::parse($receivable->due_date)->diffInDays(now(), false);
                
                $message = "📋 *Notifikasi Piutang Baru*\n\n";
                $message .= "Kepada: {$receivable->debtor_display_name}\n";
                $message .= "Jumlah Piutang: Rp {$amount}\n";
                $message .= "Jatuh Tempo: {$dueDate}\n";
                
                if ($daysUntilDue > 0) {
                    $message .= "Sisa Hari: {$daysUntilDue} hari\n";
                } elseif ($daysUntilDue == 0) {
                    $message .= "⚠️ Jatuh tempo hari ini\n";
                } else {
                    $message .= "⚠️ Terlambat: " . abs($daysUntilDue) . " hari\n";
                }
                
                if ($receivable->description) {
                    $message .= "\nCatatan: {$receivable->description}";
                }
                
                $message .= "\n\nMohon persiapkan pembayaran sebelum jatuh tempo. Terima kasih.";
                
                // Send WhatsApp message
                $result = $whatsapp->sendMessage($contact, $message);
                
                if ($result['success']) {
                    Log::info("Receivable creation WhatsApp notification sent", [
                        'receivable_id' => $receivable->id,
                        'debtor' => $receivable->debtor_display_name,
                        'contact' => $contact,
                        'amount' => $receivable->amount,
                    ]);
                } else {
                    throw new \Exception($result['message'] ?? 'Failed to send notification');
                }
            } else {
                // No valid phone number, log warning but don't fail
                Log::warning("Receivable created but WhatsApp notification skipped: No valid phone number", [
                    'receivable_id' => $receivable->id,
                    'debtor' => $receivable->debtor_display_name,
                    'debtor_type' => $receivable->debtor_type,
                ]);
            }
        } catch (\Exception $e) {
            // Log error but don't fail the creation
            Log::error("Failed to send WhatsApp notification for new receivable", [
                'receivable_id' => $this->record->id ?? null,
                'error' => $e->getMessage(),
            ]);
            
            // Show warning notification to user
            Notification::make()
                ->warning()
                ->title('Piutang Berhasil Dibuat')
                ->body('Piutang berhasil dibuat, tetapi gagal mengirim notifikasi WhatsApp. Pastikan debitur memiliki nomor telepon yang valid.')
                ->send();
        }
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
