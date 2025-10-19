<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use App\Models\Payment;
use App\Services\Payments\PaymentGatewayService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class CaptivePortalController extends Controller
{
    protected $paymentGatewayService;
    
    public function __construct(PaymentGatewayService $paymentGatewayService)
    {
        $this->paymentGatewayService = $paymentGatewayService;
    }
    
    /**
     * Tampilkan halaman Captive Portal untuk pelanggan yang disuspend
     */
    public function showSuspendedPage(Request $request)
    {
        $token = $request->input('token');
        $customerId = $request->input('customer_id');
        
        if (!$token || !$customerId) {
            return response()->view('captive-portal.error', [
                'message' => 'Parameter tidak valid'
            ]);
        }
        
        $customer = Customer::find($customerId);
        
        if (!$customer) {
            return response()->view('captive-portal.error', [
                'message' => 'Pelanggan tidak ditemukan'
            ]);
        }
        
        // Validasi token
        if (!$this->validateCaptivePortalToken($token, $customer)) {
            return response()->view('captive-portal.error', [
                'message' => 'Token tidak valid atau kedaluwarsa'
            ]);
        }
        
        // Ambil tagihan yang belum dibayar
        $unpaidPayments = Payment::where('customer_id', $customer->id)
            ->where('status', 'unpaid')
            ->orderBy('due_date')
            ->get();
            
        return response()->view('captive-portal.suspended', [
            'customer' => $customer,
            'unpaidPayments' => $unpaidPayments
        ]);
    }
    
    /**
     * Proses pembayaran dari Captive Portal
     */
    public function processPayment(Request $request)
    {
        $paymentId = $request->input('payment_id');
        
        if (!$paymentId) {
            return response()->json([
                'success' => false,
                'message' => 'ID pembayaran tidak valid'
            ]);
        }
        
        $payment = Payment::find($paymentId);
        
        if (!$payment) {
            return response()->json([
                'success' => false,
                'message' => 'Pembayaran tidak ditemukan'
            ]);
        }
        
        try {
            // Buat transaksi di payment gateway
            $transaction = $this->paymentGatewayService->createTransaction($payment);
            
            return response()->json([
                'success' => true,
                'payment_url' => $transaction['payment_url']
            ]);
        } catch (\Exception $e) {
            Log::error("Gagal membuat transaksi pembayaran: " . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Gagal membuat transaksi pembayaran'
            ]);
        }
    }
    
    /**
     * Validasi token Captive Portal
     */
    protected function validateCaptivePortalToken($token, Customer $customer)
    {
        try {
            $decoded = json_decode(base64_decode($token), true);
            
            if (!isset($decoded['data']) || !isset($decoded['signature'])) {
                return false;
            }
            
            $data = $decoded['data'];
            $signature = $decoded['signature'];
            
            // Validasi customer ID
            if ($data['customer_id'] != $customer->id) {
                return false;
            }
            
            // Validasi expiry
            if ($data['expires'] < time()) {
                return false;
            }
            
            // Validasi signature
            $secret = config('services.captive_portal.secret', 'secret-key');
            $expectedSignature = hash_hmac('sha256', json_encode($data), $secret);
            
            return hash_equals($expectedSignature, $signature);
        } catch (\Exception $e) {
            Log::error("Gagal memvalidasi token Captive Portal: " . $e->getMessage());
            return false;
        }
    }
}