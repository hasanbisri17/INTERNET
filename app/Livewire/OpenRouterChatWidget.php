<?php

namespace App\Livewire;

use Livewire\Component;
use App\Services\OpenRouterService;
use App\GPT\Functions\GetCashDataFunction;
use App\GPT\Functions\GetDebtDataFunction;
use App\GPT\Functions\GetReceivableDataFunction;
use App\GPT\Functions\GetPaymentDataFunction;
use App\GPT\Functions\GetCustomerDataFunction;
use App\GPT\Functions\GetGeneralDataFunction;
use Illuminate\Support\Facades\Log;

class OpenRouterChatWidget extends Component
{
    public bool $isOpen = false;
    public array $messages = [];
    public string $inputMessage = '';
    public bool $isLoading = false;
    public bool $isFullScreen = false;

    private ?OpenRouterService $openRouterService = null;

    public function mount(): void
    {
        // Initialize OpenRouterService with all functions
        $this->initializeOpenRouterService();

        // Add welcome message
        $this->messages[] = [
            'type' => 'bot',
            'message' => 'Halo! Saya asisten AI Anda. Saya bisa membantu Anda mengecek data dengan cepat. Coba tanyakan seperti "Berapa saldo KAS?" atau "Tampilkan hutang yang belum lunas".',
            'timestamp' => now(),
        ];
    }

    private function initializeOpenRouterService(): void
    {
        if ($this->openRouterService === null) {
            $this->openRouterService = new OpenRouterService();
            $this->openRouterService
                ->registerFunction(new GetCashDataFunction())
                ->registerFunction(new GetDebtDataFunction())
                ->registerFunction(new GetReceivableDataFunction())
                ->registerFunction(new GetPaymentDataFunction())
                ->registerFunction(new GetCustomerDataFunction())
                ->registerFunction(new GetGeneralDataFunction());
        }
    }

    public function toggleChat(): void
    {
        $this->isOpen = !$this->isOpen;
    }

    public function toggleFullScreen(): void
    {
        $this->isFullScreen = !$this->isFullScreen;
    }

    public function sendMessage(): void
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

        try {
            // Initialize service if not already initialized
            $this->initializeOpenRouterService();

            // Build system message
            $systemMessage = $this->buildSystemMessage();

            // Get conversation history (last 10 messages for context)
            $conversationHistory = $this->buildConversationHistory();

            // Call OpenRouterService
            $response = $this->openRouterService->chat($userMessage, $conversationHistory, $systemMessage);

            if ($response['success']) {
                $cleanMessage = trim($response['message'] ?? 'Response received');
                $this->messages[] = [
                    'type' => 'bot',
                    'message' => $cleanMessage,
                    'timestamp' => now(),
                ];
            } else {
                $errorMsg = $response['message'] ?? 'Terjadi kesalahan. Silakan coba lagi.';
                $this->messages[] = [
                    'type' => 'bot',
                    'message' => $errorMsg,
                    'timestamp' => now(),
                ];
            }
        } catch (\Throwable $e) {
            Log::error('OpenRouterChatWidget error', [
                'error' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString(),
            ]);

            $errorMessage = $e->getMessage();

            if (str_contains($errorMessage, 'API key') || str_contains($errorMessage, 'tidak dikonfigurasi')) {
                $errorMessage = 'OpenRouter API key belum dikonfigurasi. Silakan set API key di Pengaturan Sistem → AI Assistant.';
            } elseif (str_contains($errorMessage, 'timeout') || str_contains($errorMessage, 'Connection')) {
                $errorMessage = 'Tidak dapat terhubung ke server AI. Periksa koneksi internet Anda atau coba lagi nanti.';
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

    public function clearChat(): void
    {
        $this->messages = [];
        $this->mount(); // Reset to welcome message
    }

    /**
     * Build system message for AI
     */
    private function buildSystemMessage(): string
    {
        $systemPrompt = "Anda adalah asisten AI untuk aplikasi manajemen bisnis. Aplikasi ini memiliki fitur:\n";
        $systemPrompt .= "- Manajemen Customer\n";
        $systemPrompt .= "- Manajemen Tagihan (Payments)\n";
        $systemPrompt .= "- Manajemen KAS (Cash Transactions)\n";
        $systemPrompt .= "- Manajemen Hutang (Debts)\n";
        $systemPrompt .= "- Manajemen Piutang (Receivables)\n";
        $systemPrompt .= "- WhatsApp Integration\n\n";

        $systemPrompt .= "Tugas Anda adalah membantu user mengecek data dengan cepat melalui chat.\n";
        $systemPrompt .= "Jawab dengan singkat, jelas, dan dalam bahasa Indonesia.\n";
        $systemPrompt .= "Format jawaban harus rapi dan mudah dibaca.\n\n";

        $systemPrompt .= "PANDUAN FORMAT JAWABAN:\n";
        $systemPrompt .= "1. Gunakan format yang rapi dengan bullet points atau numbering jika perlu\n";
        $systemPrompt .= "2. Untuk angka uang, gunakan format: Rp 1.234.567,00 (dengan titik sebagai pemisah ribuan dan koma untuk desimal)\n";
        $systemPrompt .= "3. Jangan tampilkan data mentah atau struktur data teknis\n";
        $systemPrompt .= "4. Fokus pada informasi yang diminta user\n";
        $systemPrompt .= "5. Gunakan bold (**text**) untuk highlight informasi penting\n";
        $systemPrompt .= "6. Jika ada beberapa item, gunakan list yang rapi\n\n";

        $systemPrompt .= "Anda memiliki akses ke beberapa fungsi untuk mengambil data:\n";
        $systemPrompt .= "- get_cash_data: Untuk data KAS\n";
        $systemPrompt .= "- get_debt_data: Untuk data Hutang\n";
        $systemPrompt .= "- get_receivable_data: Untuk data Piutang\n";
        $systemPrompt .= "- get_payment_data: Untuk data Tagihan\n";
        $systemPrompt .= "- get_customer_data: Untuk data Customer\n";
        $systemPrompt .= "- get_general_data: Untuk overview semua data\n\n";

        $systemPrompt .= "Gunakan fungsi-fungsi tersebut ketika user meminta data spesifik. Jangan membuat data palsu atau menebak data.";

        return $systemPrompt;
    }

    /**
     * Build conversation history from messages
     */
    private function buildConversationHistory(): array
    {
        $history = [];
        $lastMessages = array_slice($this->messages, -10); // Last 10 messages

        foreach ($lastMessages as $message) {
            $history[] = [
                'role' => $message['type'] === 'user' ? 'user' : 'assistant',
                'content' => $message['message'],
            ];
        }

        return $history;
    }

    public function render()
    {
        return view('livewire.openrouter-chat-widget');
    }
}

