<?php

namespace App\Services;

use App\GPT\Functions\BaseFunction;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;

class OpenRouterService
{
    private string $apiKey;
    private string $model;
    private string $baseUrl = 'https://openrouter.ai/api/v1/chat/completions';
    private array $functions = [];
    private int $maxRecursionDepth = 5;
    private int $currentRecursionDepth = 0;

    public function __construct()
    {
        // Always get the latest API key and model from settings
        $this->apiKey = \App\Models\Setting::get('openrouter_api_key')
            ?: config('services.openrouter.api_key')
            ?: env('OPENROUTER_API_KEY', '');

        $this->model = \App\Models\Setting::get('openrouter_model', 'meta-llama/llama-3.2-3b-instruct')
            ?: config('services.openrouter.model', 'meta-llama/llama-3.2-3b-instruct')
            ?: env('OPENROUTER_MODEL', 'meta-llama/llama-3.2-3b-instruct');
    }

    /**
     * Register a function that can be called by AI
     */
    public function registerFunction(BaseFunction $function): self
    {
        $this->functions[] = $function;
        return $this;
    }

    /**
     * Register multiple functions at once
     */
    public function registerFunctions(array $functions): self
    {
        foreach ($functions as $function) {
            if ($function instanceof BaseFunction) {
                $this->registerFunction($function);
            }
        }
        return $this;
    }

    /**
     * Chat with AI, supporting function calling
     * 
     * @param string $message User message
     * @param array $conversationHistory Previous messages in the conversation
     * @param string|null $systemMessage Optional system message
     * @return array Response from AI
     */
    public function chat(string $message, array $conversationHistory = [], ?string $systemMessage = null): array
    {
        // Reset recursion depth for new conversation
        if ($this->currentRecursionDepth === 0) {
            $this->currentRecursionDepth = 0;
        }

        // Check recursion limit
        if ($this->currentRecursionDepth >= $this->maxRecursionDepth) {
            Log::warning('OpenRouterService: Max recursion depth reached', [
                'depth' => $this->currentRecursionDepth,
            ]);
            return [
                'success' => false,
                'message' => 'Terlalu banyak function calls. Silakan coba lagi dengan pertanyaan yang lebih spesifik.',
            ];
        }

        if (empty($this->apiKey)) {
            return [
                'success' => false,
                'message' => 'OpenRouter API key tidak dikonfigurasi. Silakan set API key di Pengaturan Sistem → AI Assistant.',
            ];
        }

        try {
            // Build messages array
            $messages = [];

            // Add system message if provided
            if ($systemMessage) {
                $messages[] = [
                    'role' => 'system',
                    'content' => $systemMessage,
                ];
            }

            // Add conversation history
            $messages = array_merge($messages, $conversationHistory);

            // Add current user message (if not empty)
            if (!empty(trim($message))) {
                $messages[] = [
                    'role' => 'user',
                    'content' => $message,
                ];
            }

            // Build request payload
            $payload = [
                'model' => $this->model,
                'messages' => $messages,
                'temperature' => 0.7,
                'max_tokens' => 1024,
            ];

            // Add functions/tools if available
            if (!empty($this->functions)) {
                $tools = [];
                foreach ($this->functions as $function) {
                    $tools[] = [
                        'type' => 'function',
                        'function' => $function->toOpenRouterFormat(),
                    ];
                }
                $payload['tools'] = $tools;
                $payload['tool_choice'] = 'auto'; // Let AI decide when to use functions
            }

            // Make API call
            $response = Http::timeout(30)
                ->withHeaders([
                    'Authorization' => 'Bearer ' . $this->apiKey,
                    'HTTP-Referer' => config('app.url', 'https://apps.fastbiz.my.id'),
                    'X-Title' => config('app.name', 'FastBiz'),
                    'Content-Type' => 'application/json',
                ])
                ->post($this->baseUrl, $payload);

            if ($response->successful()) {
                $data = $response->json();
                $choice = $data['choices'][0] ?? null;

                if (!$choice) {
                    return [
                        'success' => false,
                        'message' => 'Tidak ada response dari AI.',
                    ];
                }

                $aiMessage = $choice['message'] ?? [];

                // Check if AI wants to call function(s)
                if (isset($aiMessage['tool_calls']) && is_array($aiMessage['tool_calls'])) {
                    // AI wants to call function(s)
                    $this->currentRecursionDepth++;

                    $functionResults = [];
                    $toolCalls = [];

                    foreach ($aiMessage['tool_calls'] as $toolCall) {
                        $functionName = $toolCall['function']['name'] ?? '';
                        $argumentsJson = $toolCall['function']['arguments'] ?? '{}';
                        $toolCallId = $toolCall['id'] ?? '';

                        try {
                            $arguments = json_decode($argumentsJson, true);
                            if (!is_array($arguments)) {
                                $arguments = [];
                            }
                        } catch (\Exception $e) {
                            Log::error('OpenRouterService: Failed to parse function arguments', [
                                'error' => $e->getMessage(),
                                'arguments' => $argumentsJson,
                            ]);
                            $arguments = [];
                        }

                        // Find and execute function
                        $function = $this->findFunction($functionName);
                        if ($function) {
                            Log::info('OpenRouterService: Executing function', [
                                'function' => $functionName,
                                'arguments' => $arguments,
                            ]);

                            $result = $function->execute($arguments);

                            // Format result for AI
                            $functionContent = json_encode($result, JSON_UNESCAPED_UNICODE);

                            $functionResults[] = [
                                'tool_call_id' => $toolCallId,
                                'role' => 'tool',
                                'name' => $functionName,
                                'content' => $functionContent,
                            ];

                            $toolCalls[] = [
                                'id' => $toolCallId,
                                'function' => [
                                    'name' => $functionName,
                                    'arguments' => $argumentsJson,
                                ],
                            ];
                        } else {
                            Log::warning('OpenRouterService: Function not found', [
                                'function' => $functionName,
                            ]);

                            $functionResults[] = [
                                'tool_call_id' => $toolCallId,
                                'role' => 'tool',
                                'name' => $functionName,
                                'content' => json_encode([
                                    'success' => false,
                                    'error' => "Function '{$functionName}' tidak ditemukan.",
                                ]),
                            ];
                        }
                    }

                    // Add AI's message with tool_calls to conversation
                    $newMessages = array_merge($messages, [
                        [
                            'role' => 'assistant',
                            'content' => $aiMessage['content'] ?? null,
                            'tool_calls' => $toolCalls,
                        ],
                    ]);

                    // Add function results
                    $newMessages = array_merge($newMessages, $functionResults);

                    // Recursively call chat again with function results
                    return $this->chat('', $newMessages, $systemMessage);
                }

                // Normal response (no function calls)
                $content = $aiMessage['content'] ?? '';

                // Reset recursion depth on successful response
                $this->currentRecursionDepth = 0;

                return [
                    'success' => true,
                    'message' => trim($content),
                    'raw' => $data,
                ];
            } else {
                // Handle error response
                $error = $response->json();
                $statusCode = $response->status();

                Log::error('OpenRouterService: API error', [
                    'status' => $statusCode,
                    'error' => $error,
                    'model' => $this->model,
                ]);

                $errorMessage = 'Unknown error';
                $errorDetails = '';

                if (isset($error['error'])) {
                    $errorMessage = $error['error']['message'] ?? 'Unknown error';

                    if ($statusCode == 429 || str_contains(strtolower($errorMessage), 'rate') || str_contains(strtolower($errorMessage), 'rate-limited')) {
                        $errorMessage = 'Model sedang rate-limited atau terlalu banyak request.';
                        $errorDetails = 'Coba lagi dalam beberapa saat, atau gunakan model lain.';

                        if (isset($error['error']['metadata']['raw'])) {
                            $rawMessage = $error['error']['metadata']['raw'];
                            if (str_contains($rawMessage, 'rate-limited')) {
                                $errorDetails = 'Model ' . $this->model . ' sedang rate-limited. Silakan coba model lain atau tunggu beberapa saat.';
                            }
                        }
                    } elseif (str_contains(strtolower($errorMessage), 'provider returned error')) {
                        $errorMessage = 'Provider model mengembalikan error.';
                        $errorDetails = 'Model mungkin sedang tidak tersedia atau rate-limited. Coba gunakan model lain.';

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
            Log::error('OpenRouterService: Exception', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'model' => $this->model ?? 'unknown',
                'has_api_key' => !empty($this->apiKey),
            ]);

            return [
                'success' => false,
                'message' => 'Terjadi kesalahan saat menghubungi OpenRouter API: ' . $e->getMessage(),
            ];
        }
    }

    /**
     * Find function by name
     */
    private function findFunction(string $name): ?BaseFunction
    {
        foreach ($this->functions as $function) {
            if ($function->name() === $name) {
                return $function;
            }
        }
        return null;
    }

    /**
     * Get all registered functions
     */
    public function getFunctions(): array
    {
        return $this->functions;
    }

    /**
     * Clear all registered functions
     */
    public function clearFunctions(): self
    {
        $this->functions = [];
        return $this;
    }
}

