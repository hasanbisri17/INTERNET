<?php

namespace App\Services\Payments;

use App\Models\Payment;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class PaymentGatewayService
{
    /**
     * Buat transaksi pembayaran di payment gateway
     */
    public function createTransaction(Payment $payment)
    {
        // Implementasi integrasi dengan payment gateway
        // Contoh implementasi dengan mock data
        
        $transactionId = 'TRX-' . Str::random(10);
        $paymentUrl = 'https://payment-gateway.example.com/pay/' . $transactionId;
        
        // Update payment dengan data transaksi
        $payment->gateway_transaction_id = $transactionId;
        $payment->payment_url = $paymentUrl;
        $payment->gateway_data = [
            'transaction_id' => $transactionId,
            'payment_url' => $paymentUrl,
            'created_at' => now()->toIso8601String(),
            'status' => 'pending'
        ];
        $payment->save();
        
        Log::info("Transaksi pembayaran dibuat: {$transactionId} untuk invoice {$payment->invoice_number}");
        
        return [
            'transaction_id' => $transactionId,
            'payment_url' => $paymentUrl
        ];
    }
    
    /**
     * Proses callback/webhook dari payment gateway
     */
    public function processWebhook(array $data)
    {
        // Validasi signature webhook
        if (!$this->validateWebhookSignature($data)) {
            Log::warning('Signature webhook tidak valid', $data);
            return false;
        }
        
        $transactionId = $data['transaction_id'] ?? null;
        
        if (!$transactionId) {
            Log::warning('Transaction ID tidak ditemukan di data webhook', $data);
            return false;
        }
        
        // Cari payment berdasarkan transaction ID
        $payment = Payment::where('gateway_transaction_id', $transactionId)->first();
        
        if (!$payment) {
            Log::warning("Payment dengan transaction ID {$transactionId} tidak ditemukan");
            return false;
        }
        
        // Update status payment berdasarkan status di webhook
        $status = $data['status'] ?? 'unknown';
        
        switch ($status) {
            case 'success':
                $this->markPaymentAsPaid($payment, $data);
                break;
                
            case 'failed':
                $this->markPaymentAsFailed($payment, $data);
                break;
                
            case 'pending':
                // Tidak perlu melakukan apa-apa
                break;
                
            default:
                Log::warning("Status webhook tidak dikenal: {$status}", $data);
                break;
        }
        
        return true;
    }
    
    /**
     * Tandai pembayaran sebagai lunas
     */
    protected function markPaymentAsPaid(Payment $payment, array $data)
    {
        $payment->status = 'paid';
        $payment->paid_at = now();
        $payment->payment_method_id = $data['payment_method_id'] ?? $payment->payment_method_id;
        $payment->gateway_data = array_merge($payment->gateway_data ?? [], [
            'payment_data' => $data,
            'paid_at' => now()->toIso8601String()
        ]);
        $payment->save();
        
        Log::info("Payment {$payment->invoice_number} ditandai sebagai lunas");
        
        // Jika ada langkah dunning yang suspend, unsuspend layanan
        $this->unsuspendServiceIfNeeded($payment);
    }
    
    /**
     * Tandai pembayaran sebagai gagal
     */
    protected function markPaymentAsFailed(Payment $payment, array $data)
    {
        $payment->gateway_data = array_merge($payment->gateway_data ?? [], [
            'failure_data' => $data,
            'failed_at' => now()->toIso8601String()
        ]);
        $payment->save();
        
        Log::info("Payment {$payment->invoice_number} gagal diproses oleh payment gateway");
    }
    
    /**
     * Unsuspend layanan jika diperlukan
     */
    protected function unsuspendServiceIfNeeded(Payment $payment)
    {
        // Cek apakah ada langkah dunning yang suspend
        $suspendStep = \App\Models\DunningStep::where('payment_id', $payment->id)
            ->where('action_type', 'suspend')
            ->where('status', 'executed')
            ->first();
            
        if ($suspendStep) {
            // Implementasi unsuspend layanan melalui integrasi AAA
            // Ini akan diimplementasikan di AAAService
            
            Log::info("Layanan untuk customer {$payment->customer->name} di-unsuspend setelah pembayaran lunas");
        }
    }
    
    /**
     * Validasi signature webhook
     */
    protected function validateWebhookSignature(array $data)
    {
        // Implementasi validasi signature webhook
        // Contoh implementasi sederhana
        
        $signature = request()->header('X-Signature');
        
        if (!$signature) {
            return false;
        }
        
        // Dalam implementasi nyata, signature akan divalidasi dengan secret key
        // Contoh: hash_hmac('sha256', json_encode($data), $secretKey) === $signature
        
        return true;
    }
}