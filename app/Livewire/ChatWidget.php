<?php

namespace App\Livewire;

use Livewire\Component;
use Illuminate\Support\Facades\Auth;
use App\Services\OpenRouterService;
use App\Services\AIService;
use App\GPT\Functions\GetCashDataFunction;
use App\GPT\Functions\GetDebtDataFunction;
use App\GPT\Functions\GetReceivableDataFunction;
use App\GPT\Functions\GetPaymentDataFunction;
use App\GPT\Functions\GetCustomerDataFunction;
use App\GPT\Functions\GetGeneralDataFunction;
use App\Models\CashTransaction;
use App\Models\Debt;
use App\Models\Receivable;
use App\Models\Payment;
use App\Models\Customer;
use Illuminate\Support\Facades\Log;

class ChatWidget extends Component
{
    public bool $isOpen = false;
    public array $messages = [];
    public string $inputMessage = '';
    public bool $isLoading = false;
    private ?OpenRouterService $openRouterService = null;

    public function mount()
    {
        // Add welcome message
        $this->messages[] = [
            'type' => 'bot',
            'message' => 'Halo! Saya asisten AI Anda. Saya bisa membantu Anda mengecek data dengan cepat. Coba tanyakan seperti "Berapa saldo KAS?", "Tampilkan hutang yang belum lunas", atau "Berapa jumlah customer aktif?".',
            'timestamp' => now(),
        ];
    }

    /**
     * Initialize OpenRouterService with GPT Functions
     */
    private function initializeOpenRouterService(): void
    {
        if ($this->openRouterService === null) {
            $this->openRouterService = new OpenRouterService();
            
            // Register all GPT Functions
            $this->openRouterService
                ->registerFunction(new GetCashDataFunction())
                ->registerFunction(new GetDebtDataFunction())
                ->registerFunction(new GetReceivableDataFunction())
                ->registerFunction(new GetPaymentDataFunction())
                ->registerFunction(new GetCustomerDataFunction())
                ->registerFunction(new GetGeneralDataFunction());
        }
    }

    public function toggleChat()
    {
        $this->isOpen = !$this->isOpen;
    }

    public function sendMessage()
    {
        if (empty(trim($this->inputMessage))) {
            return;
        }

        // Add user message
        $this->messages[] = [
            'type' => 'user',
            'message' => $this->inputMessage,
            'timestamp' => now(),
        ];

        $userMessage = $this->inputMessage;
        $this->inputMessage = '';
        $this->isLoading = true;

        // Process chat - try with GPT Functions first, fallback to manual parsing
        try {
            // Try with OpenRouterService with GPT Functions
            $this->initializeOpenRouterService();

            // Build conversation history from messages
            $conversationHistory = $this->buildConversationHistory();

            // Build comprehensive system message
            $systemMessage = $this->buildSystemMessage();

            // Call OpenRouterService with function calling support
            $response = $this->openRouterService->chat($userMessage, $conversationHistory, $systemMessage);

            // Check if error is about tool use not supported
            if (!$response['success'] && (
                str_contains($response['message'], 'No endpoints found that support tool use') ||
                str_contains($response['message'], 'tool use') ||
                str_contains($response['message'], 'function calling')
            )) {
                // Fallback to manual parsing method
                Log::info('Model does not support function calling, using fallback method');
                $response = $this->processWithFallback($userMessage);
            }

            if ($response['success']) {
                // Clean up the message - remove any trailing characters or formatting issues
                $cleanMessage = trim($response['message'] ?? 'Response received');
                
                // Remove any trailing pipe characters or broken formatting
                $cleanMessage = preg_replace('/\s*[|<]\s*$/', '', $cleanMessage);
                
                $this->messages[] = [
                    'type' => 'bot',
                    'message' => $cleanMessage,
                    'timestamp' => now(),
                ];
            } else {
                // Show the actual error message from AI service
                $errorMsg = $response['message'] ?? 'Terjadi kesalahan. Silakan coba lagi.';
                $this->messages[] = [
                    'type' => 'bot',
                    'message' => $errorMsg,
                    'timestamp' => now(),
                ];
            }
        } catch (\Throwable $e) {
            Log::error("ChatWidget error", [
                'error' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString(),
            ]);

            $errorMessage = $e->getMessage();
            
            // Check if it's an API key issue
            if (str_contains($errorMessage, 'API key') || str_contains($errorMessage, 'tidak dikonfigurasi')) {
                $errorMessage = 'OpenRouter API key belum dikonfigurasi. Silakan set API key di Pengaturan Sistem → AI Assistant.';
            } elseif (str_contains($errorMessage, 'timeout') || str_contains($errorMessage, 'Connection')) {
                $errorMessage = 'Tidak dapat terhubung ke server AI. Periksa koneksi internet Anda atau coba lagi nanti.';
            } elseif (str_contains($errorMessage, 'Class') || str_contains($errorMessage, 'not found')) {
                $errorMessage = 'Terjadi kesalahan konfigurasi sistem. Silakan hubungi administrator.';
            }

            $this->messages[] = [
                'type' => 'bot',
                'message' => $errorMessage,
                'timestamp' => now(),
            ];
        } finally {
            $this->isLoading = false;
        }

        // Auto scroll to bottom
        $this->dispatch('scroll-to-bottom');
    }

    public function clearChat()
    {
        $this->messages = [];
        $this->mount(); // Reset to welcome message
    }

    /**
     * Build conversation history from messages
     */
    private function buildConversationHistory(): array
    {
        $history = [];
        foreach ($this->messages as $message) {
            if ($message['type'] === 'user') {
                $history[] = [
                    'role' => 'user',
                    'content' => $message['message'],
                ];
            } elseif ($message['type'] === 'bot') {
                $history[] = [
                    'role' => 'assistant',
                    'content' => $message['message'],
                ];
            }
        }
        return $history;
    }

    /**
     * Fallback method: Process with manual parsing (for models that don't support function calling)
     */
    private function processWithFallback(string $userMessage): array
    {
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
            return $aiService->generateContent($userMessage, $context);
        } catch (\Exception $e) {
            Log::error("Fallback method error", [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            return [
                'success' => false,
                'message' => 'Terjadi kesalahan saat memproses permintaan: ' . $e->getMessage(),
            ];
        }
    }

    /**
     * Fetch data based on intent (for fallback method)
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
     * Get cash transaction data (for fallback method)
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
     * Get debt data (for fallback method)
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
     * Get receivable data (for fallback method)
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
     * Get payment data (for fallback method)
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
     * Get customer data (for fallback method)
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
     * Get general data overview (for fallback method)
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
     * Build context string for AI (for fallback method)
     */
    private function buildContext(array $intent, array $data): array
    {
        $context = [];

        switch ($intent['type']) {
            case 'cash':
                $context['Saldo KAS'] = 'Rp ' . number_format($data['balance'], 0, ',', '.');
                $context['Total Pemasukan'] = 'Rp ' . number_format($data['income'], 0, ',', '.');
                $context['Total Pengeluaran'] = 'Rp ' . number_format($data['expense'], 0, ',', '.');
                break;
            case 'debt':
                $context['Total Hutang'] = 'Rp ' . number_format($data['total'], 0, ',', '.');
                $context['Sudah Dibayar'] = 'Rp ' . number_format($data['paid'], 0, ',', '.');
                $context['Sisa Hutang'] = 'Rp ' . number_format($data['remaining'], 0, ',', '.');
                $context['Jumlah Hutang'] = $data['count'] . ' item';
                if ($data['overdue_count'] > 0) {
                    $context['Hutang Terlambat'] = $data['overdue_count'] . ' item';
                }
                break;
            case 'receivable':
                $context['Total Piutang'] = 'Rp ' . number_format($data['total'], 0, ',', '.');
                $context['Sudah Diterima'] = 'Rp ' . number_format($data['paid'], 0, ',', '.');
                $context['Sisa Piutang'] = 'Rp ' . number_format($data['remaining'], 0, ',', '.');
                $context['Jumlah Piutang'] = $data['count'] . ' item';
                if ($data['overdue_count'] > 0) {
                    $context['Piutang Terlambat'] = $data['overdue_count'] . ' item';
                }
                break;
            case 'payment':
                $context['Total Tagihan'] = 'Rp ' . number_format($data['total'], 0, ',', '.');
                $context['Jumlah Tagihan'] = $data['count'] . ' item';
                break;
            case 'customer':
                $context['Total Customer'] = $data['total'] . ' customer';
                $context['Customer Aktif'] = $data['active'] . ' customer';
                $context['Customer Suspended'] = $data['suspended'] . ' customer';
                break;
            default:
                $context['Saldo KAS'] = 'Rp ' . number_format($data['cash_balance'], 0, ',', '.');
                $context['Jumlah Hutang'] = $data['debt_count'] . ' item';
                $context['Jumlah Piutang'] = $data['receivable_count'] . ' item';
                $context['Jumlah Tagihan'] = $data['payment_count'] . ' item';
                $context['Jumlah Customer'] = $data['customer_count'] . ' customer';
                break;
        }

        return $context;
    }

    /**
     * Build comprehensive system message
     */
    private function buildSystemMessage(): string
    {
        return "Anda adalah asisten AI untuk aplikasi manajemen bisnis FastBiz. Aplikasi ini adalah sistem manajemen internet service provider (ISP) yang memiliki fitur lengkap untuk mengelola bisnis internet.\n\n"
            . "MODUL YANG TERSEDIA:\n"
            . "1. **Manajemen Customer**: Mengelola data pelanggan, paket internet, status aktif/suspended, instalasi, aktivasi\n"
            . "2. **Manajemen Tagihan (Payments)**: Tagihan bulanan pelanggan dengan status pending, paid, overdue, confirmed\n"
            . "3. **Manajemen KAS (Cash Transactions)**: Transaksi pemasukan dan pengeluaran kas, saldo kas\n"
            . "4. **Manajemen Hutang (Debts)**: Hutang perusahaan dengan status pending, partial, paid, overdue\n"
            . "5. **Manajemen Piutang (Receivables)**: Piutang dari customer/user dengan status pending, partial, paid, overdue\n"
            . "6. **Integrasi MikroTik**: Manajemen perangkat MikroTik, IP binding, queue, netwatch, monitoring\n"
            . "7. **Integrasi WhatsApp**: Notifikasi tagihan, pengingat pembayaran, broadcast message\n"
            . "8. **Portal Pelanggan**: Portal untuk customer melihat tagihan dan melakukan pembayaran\n\n"
            . "FUNGSI YANG TERSEDIA:\n"
            . "- get_cash_data: Mengambil data KAS (saldo, pemasukan, pengeluaran) dengan filter tanggal\n"
            . "- get_debt_data: Mengambil data hutang dengan filter status\n"
            . "- get_receivable_data: Mengambil data piutang dengan filter status\n"
            . "- get_payment_data: Mengambil data tagihan dengan filter status dan tanggal\n"
            . "- get_customer_data: Mengambil data customer dengan filter status\n"
            . "- get_general_data: Mengambil overview semua modul\n\n"
            . "PANDUAN JAWABAN:\n"
            . "1. Gunakan fungsi yang tersedia untuk mengambil data dari database\n"
            . "2. Jawab dengan singkat, jelas, dan dalam bahasa Indonesia\n"
            . "3. Format jawaban harus rapi dengan bullet points atau numbering jika perlu\n"
            . "4. Untuk angka uang, gunakan format: Rp 1.234.567 (dengan titik sebagai pemisah ribuan)\n"
            . "5. Jangan tampilkan data mentah atau struktur data teknis\n"
            . "6. Fokus pada informasi yang diminta user\n"
            . "7. Gunakan bold (**text**) untuk highlight informasi penting\n"
            . "8. Jika data tidak ditemukan atau kosong, beri tahu user dengan jelas\n"
            . "9. Jika user bertanya tentang sesuatu yang tidak tersedia, beri tahu dengan sopan\n"
            . "10. Selalu gunakan fungsi yang sesuai untuk mengambil data real-time dari database\n\n"
            . "CATATAN PENTING:\n"
            . "- Data yang ditampilkan adalah data real-time dari database\n"
            . "- Gunakan fungsi yang tepat berdasarkan pertanyaan user\n"
            . "- Jika user bertanya tentang data spesifik, gunakan filter yang sesuai\n"
            . "- Format tanggal: hari ini (today), minggu ini (this_week), bulan ini (this_month), atau semua (all)\n"
            . "- Status: unpaid (belum lunas), paid (sudah lunas), atau all (semua)";
    }

    public function render()
    {
        return view('livewire.chat-widget');
    }
}

