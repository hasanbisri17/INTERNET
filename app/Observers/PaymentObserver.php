<?php

namespace App\Observers;

use App\Models\Payment;
use App\Models\User;
use App\Services\SuspendViaIpBindingService;
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
            
            // 1. Auto-unsuspend customer via IP Binding (NEW METHOD)
            try {
                $customer = $payment->customer;
                
                if (!$customer) {
                    return;
                }
                
                // Cek apakah customer punya IP Binding dengan type 'regular'
                // (artinya customer dalam status suspended, baik via auto-suspend atau manual)
                $hasRegularIpBindings = $customer->ipBindings()
                    ->where('type', 'regular')
                    ->exists();
                
                // Auto-unsuspend jika:
                // 1. Customer dalam status suspended (is_isolated=true, status=suspended), ATAU
                // 2. Customer punya IP Binding dengan type 'regular' (suspended manual tapi status tidak update)
                if ($hasRegularIpBindings || ($customer->is_isolated && $customer->status === 'suspended')) {
                    $suspendService = new SuspendViaIpBindingService();
                    $result = $suspendService->unsuspendCustomer($customer);
                    
                    if ($result['success']) {
                        Log::info("Auto unsuspend via IP Binding for payment {$payment->invoice_number}", [
                            'customer' => $customer->name,
                            'payment_id' => $payment->id,
                            'unsuspended_count' => $result['unsuspended_count'] ?? 0,
                        ]);
                    } else {
                        Log::warning("Failed to unsuspend customer via IP Binding", [
                            'payment' => $payment->invoice_number,
                            'customer' => $customer->name,
                            'message' => $result['message'],
                        ]);
                    }
                }
            } catch (\Exception $e) {
                Log::error("Failed to auto unsuspend via IP Binding for payment {$payment->invoice_number}: {$e->getMessage()}");
            }
            
            // 2. WhatsApp notification sekarang dikirim langsung dari action/page
            // untuk kontrol yang lebih baik dan menghindari duplikasi.
            // Lihat: PaymentResource.php (action 'pay') dan CreatePayment.php
            
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
