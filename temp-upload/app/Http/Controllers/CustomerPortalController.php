<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use App\Models\Payment;
use App\Services\Payments\PaymentGatewayService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class CustomerPortalController extends Controller
{
    protected $paymentGatewayService;
    
    public function __construct(PaymentGatewayService $paymentGatewayService)
    {
        $this->paymentGatewayService = $paymentGatewayService;
        $this->middleware('auth:customer');
    }
    
    /**
     * Tampilkan dashboard portal pelanggan
     */
    public function dashboard()
    {
        $customer = Auth::guard('customer')->user();
        
        $unpaidPayments = Payment::where('customer_id', $customer->id)
            ->where('status', 'unpaid')
            ->orderBy('due_date')
            ->get();
            
        $paidPayments = Payment::where('customer_id', $customer->id)
            ->where('status', 'paid')
            ->orderBy('paid_at', 'desc')
            ->limit(5)
            ->get();
            
        return view('customer-portal.dashboard', [
            'customer' => $customer,
            'unpaidPayments' => $unpaidPayments,
            'paidPayments' => $paidPayments
        ]);
    }
    
    /**
     * Tampilkan halaman riwayat pembayaran
     */
    public function paymentHistory()
    {
        $customer = Auth::guard('customer')->user();
        
        $payments = Payment::where('customer_id', $customer->id)
            ->orderBy('created_at', 'desc')
            ->paginate(10);
            
        return view('customer-portal.payment-history', [
            'customer' => $customer,
            'payments' => $payments
        ]);
    }
    
    /**
     * Tampilkan halaman detail pembayaran
     */
    public function paymentDetail($id)
    {
        $customer = Auth::guard('customer')->user();
        
        $payment = Payment::where('id', $id)
            ->where('customer_id', $customer->id)
            ->firstOrFail();
            
        return view('customer-portal.payment-detail', [
            'customer' => $customer,
            'payment' => $payment
        ]);
    }
    
    /**
     * Proses pembayaran
     */
    public function processPayment(Request $request)
    {
        $customer = Auth::guard('customer')->user();
        
        $paymentId = $request->input('payment_id');
        
        $payment = Payment::where('id', $paymentId)
            ->where('customer_id', $customer->id)
            ->where('status', 'unpaid')
            ->firstOrFail();
            
        try {
            // Buat transaksi di payment gateway
            $transaction = $this->paymentGatewayService->createTransaction($payment);
            
            return redirect()->to($transaction['payment_url']);
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'Gagal membuat transaksi pembayaran');
        }
    }
    
    /**
     * Tampilkan halaman profil
     */
    public function profile()
    {
        $customer = Auth::guard('customer')->user();
        
        return view('customer-portal.profile', [
            'customer' => $customer
        ]);
    }
    
    /**
     * Update profil
     */
    public function updateProfile(Request $request)
    {
        $customer = Auth::guard('customer')->user();
        
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:customers,email,' . $customer->id,
            'phone' => 'required|string|max:20',
            'address' => 'required|string'
        ]);
        
        $customer->update($validated);
        
        return redirect()->route('customer.profile')->with('success', 'Profil berhasil diperbarui');
    }
    
    /**
     * Tampilkan halaman ganti password
     */
    public function changePassword()
    {
        return view('customer-portal.change-password');
    }
    
    /**
     * Proses ganti password
     */
    public function updatePassword(Request $request)
    {
        $customer = Auth::guard('customer')->user();
        
        $validated = $request->validate([
            'current_password' => 'required',
            'password' => 'required|string|min:8|confirmed'
        ]);
        
        if (!Hash::check($validated['current_password'], $customer->password)) {
            return redirect()->back()->withErrors(['current_password' => 'Password saat ini tidak valid']);
        }
        
        $customer->password = Hash::make($validated['password']);
        $customer->save();
        
        return redirect()->route('customer.profile')->with('success', 'Password berhasil diperbarui');
    }
}