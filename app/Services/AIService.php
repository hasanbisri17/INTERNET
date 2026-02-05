<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;

class AIService
{
    private string $apiKey;
    private string $model;
    private string $baseUrl = 'https://openrouter.ai/api/v1/chat/completions';

    public function __construct()
    {
        // Priority: Database Setting > Config > Env
        $this->apiKey = \App\Models\Setting::get('openrouter_api_key') 
            ?: config('services.openrouter.api_key') 
            ?: env('OPENROUTER_API_KEY', '');
        
        $this->model = \App\Models\Setting::get('openrouter_model', 'meta-llama/llama-3.2-3b-instruct')
            ?: config('services.openrouter.model', 'meta-llama/llama-3.2-3b-instruct')
            ?: env('OPENROUTER_MODEL', 'meta-llama/llama-3.2-3b-instruct');
    }

    /**
     * Get list of free models from OpenRouter
     * Updated with verified free models that are commonly available
     * Note: Some models use :free suffix (e.g., deepseek/deepseek-chat-v3.1:free), others don't
     */
    public static function getFreeModels(): array
    {
        return [
            // Meta Llama models (Most stable and reliable)
            'meta-llama/llama-3.2-3b-instruct' => 'Meta Llama 3.2 3B Instruct - Free (Most Stable)',
            'meta-llama/llama-3.1-8b-instruct' => 'Meta Llama 3.1 8B Instruct - Free',
            'meta-llama/llama-3.2-1b-instruct' => 'Meta Llama 3.2 1B Instruct - Free',
            
            // DeepSeek models (High performance but may be rate-limited)
            'deepseek/deepseek-chat-v3.1:free' => 'DeepSeek V3.1 - Free (May be rate-limited)',
            'deepseek/deepseek-chat-v3:free' => 'DeepSeek V3 - Free',
            
            // Google Gemini models
            'google/gemini-flash-1.5-8b' => 'Google Gemini Flash 1.5 8B - Free',
            'google/gemini-2.0-flash-exp' => 'Google Gemini 2.0 Flash Exp - Free',
            'google/gemini-2.0-flash-thinking-exp' => 'Google Gemini 2.0 Flash Thinking - Free',
            
            // Microsoft models
            'microsoft/phi-3-mini-128k-instruct' => 'Microsoft Phi-3 Mini 128K - Free',
            
            // Qwen models
            'qwen/qwen-2.5-7b-instruct' => 'Qwen 2.5 7B Instruct - Free',
            'qwen/qwen-2.5-3b-instruct' => 'Qwen 2.5 3B Instruct - Free',
            
            // Mistral models
            'mistralai/mistral-7b-instruct' => 'Mistral 7B Instruct - Free',
            
            // Other models
            'openchat/openchat-7b' => 'OpenChat 7B - Free',
            'huggingface/zephyr-7b-beta' => 'Hugging Face Zephyr 7B Beta - Free',
            'gryphe/mythomist-7b' => 'Gryphe MythoMist 7B - Free',
            'undi95/toppy-m-7b' => 'Undi95 Toppy M 7B - Free',
        ];
    }
    
    /**
     * Get available models from OpenRouter API
     * This fetches the actual list of models from OpenRouter
     */
    public static function getAvailableModelsFromAPI(?string $apiKey = null): array
    {
        if (empty($apiKey)) {
            $apiKey = \App\Models\Setting::get('openrouter_api_key') 
                ?: config('services.openrouter.api_key') 
                ?: env('OPENROUTER_API_KEY', '');
        }
        
        if (empty($apiKey)) {
            return [];
        }
        
        try {
            $response = Http::timeout(10)
                ->withHeaders([
                    'Authorization' => 'Bearer ' . $apiKey,
                ])
                ->get('https://openrouter.ai/api/v1/models');
            
            if ($response->successful()) {
                $data = $response->json();
                $models = [];
                
                if (isset($data['data']) && is_array($data['data'])) {
                    foreach ($data['data'] as $model) {
                        // Filter only free models (pricing prompt = 0 or null)
                        $promptPrice = $model['pricing']['prompt'] ?? null;
                        $completionPrice = $model['pricing']['completion'] ?? null;
                        
                        if (($promptPrice === '0' || $promptPrice === 0 || $promptPrice === null) && 
                            ($completionPrice === '0' || $completionPrice === 0 || $completionPrice === null)) {
                            $id = $model['id'] ?? '';
                            $name = $model['name'] ?? $id;
                            // Keep model ID as-is (some models use :free suffix, some don't)
                            $models[$id] = $name . ' - Free';
                        }
                    }
                }
                
                return $models;
            }
        } catch (\Exception $e) {
            Log::error('Failed to fetch models from OpenRouter API', [
                'error' => $e->getMessage(),
            ]);
        }
        
        return [];
    }

    /**
     * Generate content using OpenRouter API
     */
    public function generateContent(string $prompt, array $context = []): array
    {
        // Always get the latest API key and model (in case they were updated)
        $apiKey = \App\Models\Setting::get('openrouter_api_key') 
            ?: config('services.openrouter.api_key') 
            ?: env('OPENROUTER_API_KEY', '');
        
        $model = \App\Models\Setting::get('openrouter_model', 'meta-llama/llama-3.2-3b-instruct')
            ?: config('services.openrouter.model', 'meta-llama/llama-3.2-3b-instruct')
            ?: env('OPENROUTER_MODEL', 'meta-llama/llama-3.2-3b-instruct');
        
        if (empty($apiKey)) {
            Log::warning('OpenRouter API key is empty', [
                'has_db_setting' => !empty(\App\Models\Setting::get('openrouter_api_key')),
                'has_config' => !empty(config('services.openrouter.api_key')),
                'has_env' => !empty(env('OPENROUTER_API_KEY')),
            ]);
            
            return [
                'success' => false,
                'message' => 'OpenRouter API key tidak dikonfigurasi. Silakan set API key di Pengaturan Sistem → AI Assistant.',
            ];
        }

        try {
            // Build the prompt with context
            $fullPrompt = $this->buildPrompt($prompt, $context);

            // Check cache first
            $cacheKey = 'openrouter_' . md5($fullPrompt . $model);
            $cached = Cache::get($cacheKey);
            if ($cached) {
                Log::info("OpenRouter response from cache", ['cache_key' => $cacheKey]);
                return $cached;
            }

            $response = Http::timeout(30)
                ->withHeaders([
                    'Authorization' => 'Bearer ' . $apiKey,
                    'HTTP-Referer' => config('app.url', 'https://apps.fastbiz.my.id'),
                    'X-Title' => config('app.name', 'FastBiz'),
                    'Content-Type' => 'application/json',
                ])
                ->post($this->baseUrl, [
                    'model' => $model,
                    'messages' => [
                        [
                            'role' => 'user',
                            'content' => $fullPrompt,
                        ],
                    ],
                    'temperature' => 0.7,
                    'max_tokens' => 1024,
                ]);

            if ($response->successful()) {
                $data = $response->json();
                $text = $data['choices'][0]['message']['content'] ?? '';

                $result = [
                    'success' => true,
                    'message' => trim($text),
                    'raw' => $data,
                ];

                // Cache for 5 minutes
                Cache::put($cacheKey, $result, 300);

                Log::info("OpenRouter API response successful", [
                    'model' => $model,
                    'prompt_length' => strlen($fullPrompt),
                    'response_length' => strlen($text),
                ]);

                return $result;
            } else {
                $error = $response->json();
                $statusCode = $response->status();
                
                Log::error("OpenRouter API error", [
                    'status' => $statusCode,
                    'error' => $error,
                    'model' => $model,
                    'api_key_prefix' => substr($apiKey, 0, 10) . '...',
                ]);

                // Extract detailed error message
                $errorMessage = 'Unknown error';
                $errorDetails = '';
                
                if (isset($error['error'])) {
                    $errorMessage = $error['error']['message'] ?? 'Unknown error';
                    
                    // Check for rate limit or provider error
                    if ($statusCode == 429 || str_contains(strtolower($errorMessage), 'rate') || str_contains(strtolower($errorMessage), 'rate-limited')) {
                        $errorMessage = 'Model sedang rate-limited atau terlalu banyak request.';
                        $errorDetails = 'Coba lagi dalam beberapa saat, atau gunakan model lain.';
                        
                        // Try to extract provider message
                        if (isset($error['error']['metadata']['raw'])) {
                            $rawMessage = $error['error']['metadata']['raw'];
                            if (str_contains($rawMessage, 'rate-limited')) {
                                $errorDetails = 'Model ' . $model . ' sedang rate-limited. Silakan coba model lain atau tunggu beberapa saat.';
                            }
                        }
                    } elseif (str_contains(strtolower($errorMessage), 'provider returned error')) {
                        $errorMessage = 'Provider model mengembalikan error.';
                        $errorDetails = 'Model mungkin sedang tidak tersedia atau rate-limited. Coba gunakan model lain.';
                        
                        // Try to extract provider message
                        if (isset($error['error']['metadata']['raw'])) {
                            $rawMessage = $error['error']['metadata']['raw'];
                            $errorDetails = $rawMessage;
                        }
                    } elseif ($statusCode == 401) {
                        $errorMessage = 'API Key tidak valid atau tidak memiliki akses.';
                        $errorDetails = 'Pastikan API Key dari OpenRouter benar dan memiliki akses ke model gratis.';
                    } elseif ($statusCode == 400) {
                        $errorMessage = 'Request tidak valid.';
                        $errorDetails = 'Model mungkin tidak tersedia atau format request salah.';
                    }
                }

                $fullMessage = $errorMessage;
                if ($errorDetails) {
                    $fullMessage .= ' ' . $errorDetails;
                }

                return [
                    'success' => false,
                    'message' => $fullMessage,
                ];
            }
        } catch (\Exception $e) {
            Log::error("OpenRouter API exception", [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'model' => $model ?? 'unknown',
                'has_api_key' => !empty($apiKey),
            ]);

            return [
                'success' => false,
                'message' => 'Terjadi kesalahan saat menghubungi OpenRouter API: ' . $e->getMessage(),
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
        $systemPrompt .= "Format jawaban harus rapi dan mudah dibaca.\n\n";
        
        $systemPrompt .= "PANDUAN FORMAT JAWABAN:\n";
        $systemPrompt .= "1. Gunakan format yang rapi dengan bullet points atau numbering jika perlu\n";
        $systemPrompt .= "2. Untuk angka uang, gunakan format: Rp 1.234.567,00 (dengan titik sebagai pemisah ribuan dan koma untuk desimal)\n";
        $systemPrompt .= "3. Jangan tampilkan data mentah atau struktur data teknis\n";
        $systemPrompt .= "4. Fokus pada informasi yang diminta user\n";
        $systemPrompt .= "5. Gunakan bold (**text**) untuk highlight informasi penting\n";
        $systemPrompt .= "6. Jika ada beberapa item, gunakan list yang rapi\n\n";

        if (!empty($context)) {
            $systemPrompt .= "Data yang tersedia:\n";
            foreach ($context as $key => $value) {
                $systemPrompt .= "- {$key}: {$value}\n";
            }
            $systemPrompt .= "\n";
        }

        $systemPrompt .= "Pertanyaan user: {$userPrompt}\n\n";
        $systemPrompt .= "Jawab dengan format yang rapi, informatif, dan mudah dibaca. Jangan tampilkan data mentah atau struktur teknis.";

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

