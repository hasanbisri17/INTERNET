<?php

namespace App\Http\Controllers;

use App\Services\AIService;
use App\Models\CashTransaction;
use App\Models\Debt;
use App\Models\Receivable;
use App\Models\Payment;
use App\Models\Customer;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class ChatController extends Controller
{
    /**
     * Handle chat message
     */
    public function chat(Request $request)
    {
        $request->validate([
            'message' => 'required|string|max:1000',
            'history' => 'nullable|array',
        ]);

        $userMessage = $request->input('message');
        $history = $request->input('history', []);

        try {
            // Create AIService instance
            $aiService = new AIService();

            // Parse query to determine intent
            $intent = $aiService->parseQuery($userMessage);

            // Fetch data based on intent
            $data = $this->fetchData($intent);

            // Build context for AI
            $context = $this->buildContext($intent, $data);

            // Generate response using AI Service
            $response = $aiService->generateContent($userMessage, $context);

            if ($response['success']) {
                return response()->json([
                    'success' => true,
                    'message' => $response['message'],
                    'data' => $data,
                    'intent' => $intent,
                ]);
            } else {
                // Return error from AI service, but with 200 status so frontend can handle it
                return response()->json([
                    'success' => false,
                    'message' => $response['message'] ?? 'Terjadi kesalahan saat memproses permintaan.',
                ], 200);
            }
        } catch (\Throwable $e) {
            Log::error("Chat error", [
                'error' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString(),
                'user_message' => $userMessage,
            ]);

            // Return more user-friendly error message
            $errorMessage = $e->getMessage();
            
            // Check if it's an API key issue
            if (str_contains($errorMessage, 'API key') || str_contains($errorMessage, 'tidak dikonfigurasi')) {
                $errorMessage = 'OpenRouter API key belum dikonfigurasi. Silakan set API key di Pengaturan Sistem → AI Assistant.';
            } elseif (str_contains($errorMessage, 'timeout') || str_contains($errorMessage, 'Connection')) {
                $errorMessage = 'Tidak dapat terhubung ke server AI. Periksa koneksi internet Anda atau coba lagi nanti.';
            } elseif (str_contains($errorMessage, 'Class') || str_contains($errorMessage, 'not found')) {
                $errorMessage = 'Terjadi kesalahan konfigurasi sistem. Silakan hubungi administrator.';
            }

            return response()->json([
                'success' => false,
                'message' => $errorMessage,
            ], 200);
        }
    }

    /**
     * Fetch data based on intent
     */
    private function fetchData(array $intent): array
    {
        $data = [];

        switch ($intent['type']) {
            case 'cash':
                $data = $this->getCashData($intent);
                break;
            case 'debt':
                $data = $this->getDebtData($intent);
                break;
            case 'receivable':
                $data = $this->getReceivableData($intent);
                break;
            case 'payment':
                $data = $this->getPaymentData($intent);
                break;
            case 'customer':
                $data = $this->getCustomerData($intent);
                break;
            default:
                $data = $this->getGeneralData();
                break;
        }

        return $data;
    }

    /**
     * Get cash transaction data
     */
    private function getCashData(array $intent): array
    {
        $query = CashTransaction::whereNull('voided_at');

        // Apply date filter
        if (isset($intent['filters']['date'])) {
            $dateFilter = $intent['filters']['date'];
            if ($dateFilter === 'today') {
                $query->whereDate('date', today());
            } elseif ($dateFilter === 'this_week') {
                $query->whereBetween('date', [now()->startOfWeek(), now()->endOfWeek()]);
            } elseif ($dateFilter === 'this_month') {
                $query->whereMonth('date', now()->month)
                    ->whereYear('date', now()->year);
            }
        }

        $income = (clone $query)->where('type', 'income')->sum('amount');
        $expense = (clone $query)->where('type', 'expense')->sum('amount');
        $balance = $income - $expense;

        return [
            'balance' => $balance,
            'income' => $income,
            'expense' => $expense,
            'income_count' => (clone $query)->where('type', 'income')->count(),
            'expense_count' => (clone $query)->where('type', 'expense')->count(),
        ];
    }

    /**
     * Get debt data
     */
    private function getDebtData(array $intent): array
    {
        $query = Debt::query();

        // Apply status filter
        if (isset($intent['filters']['status'])) {
            if ($intent['filters']['status'] === 'unpaid') {
                $query->whereIn('status', ['pending', 'partial', 'overdue']);
            } elseif ($intent['filters']['status'] === 'paid') {
                $query->where('status', 'paid');
            }
        }

        $total = $query->sum('amount');
        $paid = $query->sum('paid_amount');
        $remaining = $total - $paid;
        $count = $query->count();

        // Get overdue count
        $overdueCount = Debt::where('status', 'overdue')->count();

        return [
            'total' => $total,
            'paid' => $paid,
            'remaining' => $remaining,
            'count' => $count,
            'overdue_count' => $overdueCount,
        ];
    }

    /**
     * Get receivable data
     */
    private function getReceivableData(array $intent): array
    {
        $query = Receivable::query();

        // Apply status filter
        if (isset($intent['filters']['status'])) {
            if ($intent['filters']['status'] === 'unpaid') {
                $query->whereIn('status', ['pending', 'partial', 'overdue']);
            } elseif ($intent['filters']['status'] === 'paid') {
                $query->where('status', 'paid');
            }
        }

        $total = $query->sum('amount');
        $paid = $query->sum('paid_amount');
        $remaining = $total - $paid;
        $count = $query->count();

        // Get overdue count
        $overdueCount = Receivable::where('status', 'overdue')->count();

        return [
            'total' => $total,
            'paid' => $paid,
            'remaining' => $remaining,
            'count' => $count,
            'overdue_count' => $overdueCount,
        ];
    }

    /**
     * Get payment data
     */
    private function getPaymentData(array $intent): array
    {
        $query = Payment::query();

        // Apply status filter
        if (isset($intent['filters']['status'])) {
            if ($intent['filters']['status'] === 'unpaid') {
                $query->whereIn('status', ['pending', 'overdue']);
            } elseif ($intent['filters']['status'] === 'paid') {
                $query->whereIn('status', ['paid', 'confirmed']);
            }
        }

        // Apply date filter
        if (isset($intent['filters']['date'])) {
            $dateFilter = $intent['filters']['date'];
            if ($dateFilter === 'today') {
                $query->whereDate('created_at', today());
            } elseif ($dateFilter === 'this_week') {
                $query->whereBetween('created_at', [now()->startOfWeek(), now()->endOfWeek()]);
            } elseif ($dateFilter === 'this_month') {
                $query->whereMonth('created_at', now()->month)
                    ->whereYear('created_at', now()->year);
            }
        }

        $total = $query->sum('amount');
        $count = $query->count();

        return [
            'total' => $total,
            'count' => $count,
        ];
    }

    /**
     * Get customer data
     */
    private function getCustomerData(array $intent): array
    {
        $count = Customer::count();
        $activeCount = Customer::where('status', 'active')->count();
        $suspendedCount = Customer::where('status', 'suspended')->count();

        return [
            'total' => $count,
            'active' => $activeCount,
            'suspended' => $suspendedCount,
        ];
    }

    /**
     * Get general data overview
     */
    private function getGeneralData(): array
    {
        return [
            'cash_balance' => CashTransaction::whereNull('voided_at')
                ->selectRaw('SUM(CASE WHEN type = "income" THEN amount ELSE -amount END) as balance')
                ->value('balance') ?? 0,
            'debt_count' => Debt::count(),
            'receivable_count' => Receivable::count(),
            'payment_count' => Payment::count(),
            'customer_count' => Customer::count(),
        ];
    }

    /**
     * Build context string for AI
     */
    private function buildContext(array $intent, array $data): array
    {
        $context = [];

        switch ($intent['type']) {
            case 'cash':
                $context['Saldo KAS'] = 'Rp ' . number_format($data['balance'], 2);
                $context['Total Pemasukan'] = 'Rp ' . number_format($data['income'], 2);
                $context['Total Pengeluaran'] = 'Rp ' . number_format($data['expense'], 2);
                break;
            case 'debt':
                $context['Total Hutang'] = 'Rp ' . number_format($data['total'], 2);
                $context['Sudah Dibayar'] = 'Rp ' . number_format($data['paid'], 2);
                $context['Sisa Hutang'] = 'Rp ' . number_format($data['remaining'], 2);
                $context['Jumlah Hutang'] = $data['count'] . ' item';
                if ($data['overdue_count'] > 0) {
                    $context['Hutang Terlambat'] = $data['overdue_count'] . ' item';
                }
                break;
            case 'receivable':
                $context['Total Piutang'] = 'Rp ' . number_format($data['total'], 2);
                $context['Sudah Diterima'] = 'Rp ' . number_format($data['paid'], 2);
                $context['Sisa Piutang'] = 'Rp ' . number_format($data['remaining'], 2);
                $context['Jumlah Piutang'] = $data['count'] . ' item';
                if ($data['overdue_count'] > 0) {
                    $context['Piutang Terlambat'] = $data['overdue_count'] . ' item';
                }
                break;
            case 'payment':
                $context['Total Tagihan'] = 'Rp ' . number_format($data['total'], 2);
                $context['Jumlah Tagihan'] = $data['count'] . ' item';
                break;
            case 'customer':
                $context['Total Customer'] = $data['total'] . ' customer';
                $context['Customer Aktif'] = $data['active'] . ' customer';
                $context['Customer Suspended'] = $data['suspended'] . ' customer';
                break;
            default:
                $context['Saldo KAS'] = 'Rp ' . number_format($data['cash_balance'], 2);
                $context['Jumlah Hutang'] = $data['debt_count'] . ' item';
                $context['Jumlah Piutang'] = $data['receivable_count'] . ' item';
                $context['Jumlah Tagihan'] = $data['payment_count'] . ' item';
                $context['Jumlah Customer'] = $data['customer_count'] . ' customer';
                break;
        }

        return $context;
    }
}

