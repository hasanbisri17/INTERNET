<div class="openrouter-chat-container" x-data="openRouterChat()">
    <!-- Chat Button (Fixed Bottom Right) -->
    @if(!$isOpen)
        <button 
            wire:click="toggleChat"
            class="openrouter-chat-button"
            x-show="mounted"
            x-transition:enter="transition ease-out duration-500"
            x-transition:enter-start="opacity-0 scale-0 rotate-180"
            x-transition:enter-end="opacity-100 scale-100 rotate-0"
            title="Buka AI Assistant"
        >
            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"></path>
            </svg>
        </button>
    @endif

    <!-- Chat Window -->
    @if($isOpen)
        <div 
            class="openrouter-chat-window {{ $isFullScreen ? 'openrouter-chat-fullscreen' : '' }}"
            x-show="show"
            x-transition:enter="transition ease-out duration-300 transform"
            x-transition:enter-start="opacity-0 translate-y-4 scale-95"
            x-transition:enter-end="opacity-100 translate-y-0 scale-100"
            x-transition:leave="transition ease-in duration-200 transform"
            x-transition:leave-start="opacity-100 translate-y-0 scale-100"
            x-transition:leave-end="opacity-0 translate-y-4 scale-95"
        >
            <!-- Header -->
            <div class="openrouter-chat-header">
                <div class="flex items-center gap-3">
                    <div class="openrouter-chat-avatar">
                        <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
                            <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"></path>
                        </svg>
                    </div>
                    <div>
                        <div class="font-semibold text-white">AI Assistant</div>
                        <div class="text-xs text-green-100 flex items-center gap-1">
                            <span class="w-2 h-2 bg-green-300 rounded-full animate-pulse"></span>
                            Online
                        </div>
                    </div>
                </div>
                <div class="flex items-center gap-2">
                    <button 
                        wire:click="toggleFullScreen"
                        class="openrouter-chat-header-button"
                        title="{{ $isFullScreen ? 'Exit Full Screen' : 'Full Screen' }}"
                    >
                        @if($isFullScreen)
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                            </svg>
                        @else
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 8V4m0 0h4M4 4l5 5m11-1V4m0 0h-4m4 0l-5 5M4 16v4m0 0h4m-4 0l5-5m11 5l-5-5m5 5v-4m0 4h-4"></path>
                            </svg>
                        @endif
                    </button>
                    <button 
                        wire:click="clearChat"
                        class="openrouter-chat-header-button"
                        title="Bersihkan Chat"
                    >
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                        </svg>
                    </button>
                    <button 
                        wire:click="toggleChat"
                        class="openrouter-chat-header-button"
                        title="Tutup Chat"
                    >
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                        </svg>
                    </button>
                </div>
            </div>

            <!-- Messages Area -->
            <div 
                class="openrouter-chat-messages"
                id="openrouter-chat-messages"
                x-data="{ 
                    scrollToBottom() {
                        this.$el.scrollTop = this.$el.scrollHeight;
                    }
                }"
                x-init="$watch('$wire.messages', () => { setTimeout(() => scrollToBottom(), 100) })"
            >
                @foreach($messages as $index => $message)
                    <div class="flex {{ $message['type'] === 'user' ? 'justify-end' : 'justify-start' }} mb-4 message-item">
                        @if($message['type'] === 'bot')
                            <div class="flex items-start gap-2 max-w-[85%]">
                                <div class="openrouter-chat-avatar-small">
                                    <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                                        <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"></path>
                                    </svg>
                                </div>
                                <div class="flex flex-col">
                                    <div class="openrouter-chat-message-bot">
                                        @php
                                            // Markdown-like formatting
                                            $formatted = $message['message'];
                                            $formatted = preg_replace('/\*\*(.+?)\*\*/', '<strong>$1</strong>', $formatted);
                                            $formatted = preg_replace('/\*(.+?)\*/', '<em>$1</em>', $formatted);
                                            $formatted = preg_replace('/^- (.+)$/m', '<li>$1</li>', $formatted);
                                            $formatted = preg_replace('/(<li>.*<\/li>\n?)+/s', '<ul class="list-disc list-inside my-1">$0</ul>', $formatted);
                                            $formatted = nl2br($formatted);
                                        @endphp
                                        <div class="text-sm whitespace-pre-wrap">{!! $formatted !!}</div>
                                    </div>
                                    <div class="text-xs text-gray-400 mt-1 px-1">
                                        {{ $message['timestamp']->locale('id')->translatedFormat('d F Y') }} pukul {{ $message['timestamp']->format('H.i') }}
                                    </div>
                                </div>
                            </div>
                        @else
                            <div class="flex flex-col items-end max-w-[85%]">
                                <div class="openrouter-chat-message-user">
                                    <p class="text-sm whitespace-pre-wrap">{{ $message['message'] }}</p>
                                </div>
                                <div class="text-xs text-gray-400 mt-1 px-1">
                                    {{ $message['timestamp']->locale('id')->translatedFormat('d F Y') }} pukul {{ $message['timestamp']->format('H.i') }}
                                </div>
                            </div>
                        @endif
                    </div>
                @endforeach

                @if($isLoading)
                    <div class="flex justify-start mb-4">
                        <div class="flex items-start gap-2">
                            <div class="openrouter-chat-avatar-small">
                                <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                                    <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"></path>
                                </svg>
                            </div>
                            <div class="openrouter-chat-message-bot">
                                <div class="flex items-center gap-2">
                                    <svg class="animate-spin h-4 w-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                    </svg>
                                    <span class="text-sm">Memproses...</span>
                                </div>
                            </div>
                        </div>
                    </div>
                @endif
            </div>

            <!-- Input Area -->
            <div class="openrouter-chat-input-area">
                <form wire:submit.prevent="sendMessage" class="flex gap-2">
                    <input 
                        type="text" 
                        wire:model="inputMessage"
                        placeholder="Ketik pertanyaan anda..."
                        class="openrouter-chat-input"
                        style="color: #111827 !important;"
                        wire:loading.attr="disabled"
                        x-on:keydown.enter.prevent="if (!$wire.isLoading && $wire.inputMessage.trim()) { $wire.sendMessage(); }"
                    >
                    <button 
                        type="submit"
                        :disabled="$isLoading || empty(trim($wire.inputMessage))"
                        class="openrouter-chat-send-button"
                        title="Kirim Pesan"
                    >
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8"></path>
                        </svg>
                    </button>
                </form>
                <div class="mt-2 text-center">
                    <p class="text-xs text-gray-500">Powered By <span class="font-semibold text-gray-400">{{ config('app.name', 'FastBiz') }}</span></p>
                </div>
            </div>
        </div>
    @endif

    @script
    <script>
        function openRouterChat() {
            return {
                mounted: false,
                show: false,
                init() {
                    setTimeout(() => {
                        this.mounted = true;
                        if (@js($isOpen)) {
                            this.show = true;
                        }
                    }, 100);
                },
            }
        }
    </script>
    @endscript

    <style>
        .openrouter-chat-container {
            position: relative;
        }

        .openrouter-chat-button {
            position: fixed;
            bottom: 20px;
            right: 20px;
            z-index: 9999;
            background-color: #16a34a;
            color: white;
            border-radius: 9999px;
            padding: 1rem;
            box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.3), 0 10px 10px -5px rgba(0, 0, 0, 0.2);
            transition: all 0.3s;
            cursor: pointer;
            border: none;
            display: flex;
            align-items: center;
            justify-content: center;
            animation: gentlePulse 3s ease-in-out infinite;
        }

        .openrouter-chat-button:hover {
            background-color: #15803d;
            transform: scale(1.1);
        }

        .openrouter-chat-window {
            position: fixed;
            bottom: 20px;
            right: 20px;
            z-index: 9999;
            width: 400px;
            max-width: calc(100vw - 2.5rem);
            height: 600px;
            max-height: calc(100vh - 2.5rem);
            background-color: #1f2937;
            border-radius: 0.5rem;
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.5);
            display: flex;
            flex-direction: column;
            border: 1px solid #374151;
            overflow: hidden;
        }

        .openrouter-chat-fullscreen {
            width: calc(100vw - 2rem) !important;
            height: calc(100vh - 2rem) !important;
            max-width: none !important;
            max-height: none !important;
            bottom: 1rem !important;
            right: 1rem !important;
        }

        .openrouter-chat-header {
            background-color: #16a34a;
            color: white;
            padding: 1rem;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }

        .openrouter-chat-avatar {
            width: 2.5rem;
            height: 2.5rem;
            border-radius: 9999px;
            background-color: white;
            color: #16a34a;
            display: flex;
            align-items: center;
            justify-content: center;
            font-bold;
        }

        .openrouter-chat-avatar-small {
            width: 2rem;
            height: 2rem;
            border-radius: 9999px;
            background-color: #16a34a;
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
        }

        .openrouter-chat-header-button {
            padding: 0.375rem;
            color: white;
            background-color: transparent;
            border: none;
            border-radius: 0.25rem;
            cursor: pointer;
            transition: background-color 0.2s;
        }

        .openrouter-chat-header-button:hover {
            background-color: rgba(255, 255, 255, 0.1);
        }

        .openrouter-chat-messages {
            flex: 1;
            overflow-y: auto;
            padding: 1rem;
            background-color: #111827;
        }

        .openrouter-chat-message-bot {
            background-color: #16a34a;
            color: white;
            border-radius: 0.5rem;
            padding: 0.75rem 1rem;
            border-top-left-radius: 0.25rem;
        }

        .openrouter-chat-message-user {
            background-color: #374151;
            color: #f3f4f6;
            border-radius: 0.5rem;
            padding: 0.75rem 1rem;
            border-top-right-radius: 0.25rem;
        }

        .openrouter-chat-input-area {
            border-top: 1px solid #374151;
            padding: 1rem;
            background-color: #1f2937;
        }

        .openrouter-chat-input {
            flex: 1;
            padding: 0.75rem 1rem;
            background-color: white;
            color: #111827;
            border: 1px solid #d1d5db;
            border-radius: 0.5rem;
            outline: none;
            transition: all 0.2s;
        }

        .openrouter-chat-input:focus {
            ring: 2px;
            ring-color: #16a34a;
            border-color: transparent;
        }

        .openrouter-chat-send-button {
            background-color: #16a34a;
            color: white;
            padding: 0.75rem;
            border-radius: 0.5rem;
            border: none;
            cursor: pointer;
            transition: background-color 0.2s;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .openrouter-chat-send-button:hover:not(:disabled) {
            background-color: #15803d;
        }

        .openrouter-chat-send-button:disabled {
            background-color: #4b5563;
            cursor: not-allowed;
        }

        @keyframes gentlePulse {
            0%, 100% {
                opacity: 1;
                transform: scale(1);
            }
            50% {
                opacity: 0.95;
                transform: scale(1.02);
            }
        }

        .message-item {
            animation: slideIn 0.3s ease-out;
        }

        @keyframes slideIn {
            from {
                opacity: 0;
                transform: translateY(10px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        /* Custom scrollbar */
        .openrouter-chat-messages::-webkit-scrollbar {
            width: 6px;
        }

        .openrouter-chat-messages::-webkit-scrollbar-track {
            background: #1f2937;
        }

        .openrouter-chat-messages::-webkit-scrollbar-thumb {
            background: #4b5563;
            border-radius: 10px;
        }

        .openrouter-chat-messages::-webkit-scrollbar-thumb:hover {
            background: #6b7280;
        }

        @media (max-width: 640px) {
            .openrouter-chat-window {
                width: calc(100vw - 1rem);
                height: calc(100vh - 1rem);
                bottom: 0.5rem !important;
                right: 0.5rem !important;
            }

            .openrouter-chat-button {
                bottom: 1rem !important;
                right: 1rem !important;
            }
        }
    </style>
</div>

