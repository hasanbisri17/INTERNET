<?php

namespace App\Observers;

use App\Models\Payment;
use App\Models\User;
use App\Services\DunningService;
use App\Services\WhatsAppService;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\Log;

class PaymentObserver
{
    /**
     * Handle the Payment "updated" event.
     */
    public function updated(Payment $payment): void
    {
        // Check if payment status changed to 'paid' or 'confirmed'
        if ($payment->isDirty('status') && in_array($payment->status, ['paid', 'confirmed'])) {
            
            // 1. Trigger unsuspend webhook via n8n
            try {
                $dunningService = app(DunningService::class);
                $result = $dunningService->triggerUnsuspendOnPayment($payment);
                
                if ($result['success']) {
                    Log::info("Auto unsuspend triggered for payment {$payment->invoice_number}", [
                        'customer' => $payment->customer->name,
                        'payment_id' => $payment->id,
                    ]);
                }
            } catch (\Exception $e) {
                Log::error("Failed to trigger auto unsuspend for payment {$payment->invoice_number}: {$e->getMessage()}");
            }
            
            // 2. Send WhatsApp notification to customer
            try {
                $whatsAppService = app(WhatsAppService::class);
                
                // Send payment confirmation notification with PDF invoice
                $whatsAppService->sendBillingNotification($payment, 'paid', true);
                
                Log::info("Payment confirmation WhatsApp sent for {$payment->invoice_number}", [
                    'customer' => $payment->customer->name,
                    'payment_id' => $payment->id,
                ]);
            } catch (\Exception $e) {
                Log::error("Failed to send WhatsApp notification for payment {$payment->invoice_number}: {$e->getMessage()}");
            }
            
            // 3. Send database notification to all admin users
            $this->sendPaymentPaidNotification($payment);
        }
    }
    
    /**
     * Send database notification when payment is confirmed
     */
    protected function sendPaymentPaidNotification(Payment $payment): void
    {
        try {
            $adminUsers = User::where('is_admin', true)->get();
            
            Notification::make()
                ->title('💰 Pembayaran Diterima')
                ->body("Pembayaran {$payment->invoice_number} dari {$payment->customer->name} sebesar Rp " . number_format($payment->amount, 0, ',', '.') . " telah dikonfirmasi.")
                ->success()
                ->icon('heroicon-o-check-circle')
                ->actions([
                    \Filament\Notifications\Actions\Action::make('view')
                        ->label('Lihat Detail')
                        ->url(route('filament.admin.resources.payments.edit', $payment))
                        ->button(),
                ])
                ->sendToDatabase($adminUsers);
        } catch (\Exception $e) {
            Log::error("Failed to send payment notification: {$e->getMessage()}");
        }
    }
}
