<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;

class GeminiService
{
    private string $apiKey;
    private string $baseUrl = 'https://generativelanguage.googleapis.com/v1beta/models/gemini-2.0-flash-exp:generateContent';
    
    // Alternative models:
    // gemini-2.0-flash-exp (experimental)
    // gemini-1.5-flash (stable)
    // gemini-1.5-pro (more capable)

    public function __construct()
    {
        // Priority: Database Setting > Config > Env
        $this->apiKey = \App\Models\Setting::get('gemini_api_key') 
            ?: config('services.gemini.api_key') 
            ?: env('GEMINI_API_KEY', '');
    }

    /**
     * Generate content using Gemini API
     */
    public function generateContent(string $prompt, array $context = []): array
    {
        if (empty($this->apiKey)) {
            return [
                'success' => false,
                'message' => 'Gemini API key tidak dikonfigurasi. Silakan set GEMINI_API_KEY di .env',
            ];
        }

        try {
            // Build the prompt with context
            $fullPrompt = $this->buildPrompt($prompt, $context);

            // Check cache first
            $cacheKey = 'gemini_' . md5($fullPrompt);
            $cached = Cache::get($cacheKey);
            if ($cached) {
                Log::info("Gemini response from cache", ['cache_key' => $cacheKey]);
                return $cached;
            }

            $response = Http::timeout(30)
                ->post($this->baseUrl . '?key=' . $this->apiKey, [
                    'contents' => [
                        [
                            'parts' => [
                                ['text' => $fullPrompt]
                            ]
                        ]
                    ],
                    'generationConfig' => [
                        'temperature' => 0.7,
                        'topK' => 40,
                        'topP' => 0.95,
                        'maxOutputTokens' => 1024,
                    ],
                ]);

            if ($response->successful()) {
                $data = $response->json();
                $text = $data['candidates'][0]['content']['parts'][0]['text'] ?? '';

                $result = [
                    'success' => true,
                    'message' => trim($text),
                    'raw' => $data,
                ];

                // Cache for 5 minutes
                Cache::put($cacheKey, $result, 300);

                Log::info("Gemini API response successful", [
                    'prompt_length' => strlen($fullPrompt),
                    'response_length' => strlen($text),
                ]);

                return $result;
            } else {
                $error = $response->json();
                Log::error("Gemini API error", [
                    'status' => $response->status(),
                    'error' => $error,
                ]);

                return [
                    'success' => false,
                    'message' => 'Error dari Gemini API: ' . ($error['error']['message'] ?? 'Unknown error'),
                ];
            }
        } catch (\Exception $e) {
            Log::error("Gemini API exception", [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return [
                'success' => false,
                'message' => 'Terjadi kesalahan saat menghubungi Gemini API: ' . $e->getMessage(),
            ];
        }
    }

    /**
     * Build prompt with context and system instructions
     */
    private function buildPrompt(string $userPrompt, array $context = []): string
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
        $systemPrompt .= "Jika user bertanya tentang data spesifik, berikan informasi yang relevan.\n\n";

        if (!empty($context)) {
            $systemPrompt .= "Konteks data saat ini:\n";
            foreach ($context as $key => $value) {
                $systemPrompt .= "- {$key}: {$value}\n";
            }
            $systemPrompt .= "\n";
        }

        $systemPrompt .= "Pertanyaan user: {$userPrompt}\n\n";
        $systemPrompt .= "Jawab dengan format yang mudah dibaca dan informatif.";

        return $systemPrompt;
    }

    /**
     * Parse user query to determine what data to fetch
     */
    public function parseQuery(string $query): array
    {
        $query = strtolower(trim($query));
        
        $intent = [
            'type' => 'general',
            'entity' => null,
            'action' => 'query',
            'filters' => [],
        ];

        // Check for specific entities
        if (str_contains($query, 'kas') || str_contains($query, 'saldo') || str_contains($query, 'uang')) {
            $intent['type'] = 'cash';
            $intent['entity'] = 'cash_transaction';
        } elseif (str_contains($query, 'hutang') || str_contains($query, 'utang')) {
            $intent['type'] = 'debt';
            $intent['entity'] = 'debt';
        } elseif (str_contains($query, 'piutang') || str_contains($query, 'receivable')) {
            $intent['type'] = 'receivable';
            $intent['entity'] = 'receivable';
        } elseif (str_contains($query, 'tagihan') || str_contains($query, 'payment') || str_contains($query, 'bill')) {
            $intent['type'] = 'payment';
            $intent['entity'] = 'payment';
        } elseif (str_contains($query, 'customer') || str_contains($query, 'pelanggan')) {
            $intent['type'] = 'customer';
            $intent['entity'] = 'customer';
        }

        // Check for actions
        if (str_contains($query, 'berapa') || str_contains($query, 'jumlah') || str_contains($query, 'total')) {
            $intent['action'] = 'count';
        } elseif (str_contains($query, 'tampilkan') || str_contains($query, 'lihat') || str_contains($query, 'list')) {
            $intent['action'] = 'list';
        } elseif (str_contains($query, 'status') || str_contains($query, 'keadaan')) {
            $intent['action'] = 'status';
        }

        // Check for time filters
        if (str_contains($query, 'hari ini') || str_contains($query, 'today')) {
            $intent['filters']['date'] = 'today';
        } elseif (str_contains($query, 'bulan ini') || str_contains($query, 'this month')) {
            $intent['filters']['date'] = 'this_month';
        } elseif (str_contains($query, 'minggu ini') || str_contains($query, 'this week')) {
            $intent['filters']['date'] = 'this_week';
        }

        // Check for status filters
        if (str_contains($query, 'belum lunas') || str_contains($query, 'pending') || str_contains($query, 'unpaid')) {
            $intent['filters']['status'] = 'unpaid';
        } elseif (str_contains($query, 'lunas') || str_contains($query, 'paid')) {
            $intent['filters']['status'] = 'paid';
        }

        return $intent;
    }
}

