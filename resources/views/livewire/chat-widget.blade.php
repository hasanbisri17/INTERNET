<div class="chat-widget-container">
    <!-- Chat Button (Fixed Bottom Right Corner - Floating) -->
    @if(!$isOpen)
        <button 
            wire:click="toggleChat"
            class="chat-button-floating"
            style="position: fixed; bottom: 20px; right: 20px; z-index: 9999; animation: gentlePulse 3s ease-in-out infinite;"
            title="Buka Chat Assistant"
            x-data="{ mounted: false }"
            x-init="setTimeout(() => mounted = true, 100)"
            x-show="mounted"
            x-transition:enter="transition ease-out duration-500"
            x-transition:enter-start="opacity-0 scale-0 rotate-180"
            x-transition:enter-end="opacity-100 scale-100 rotate-0"
        >
            <svg class="w-6 h-6 transform transition-transform duration-300 group-hover:rotate-12" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"></path>
            </svg>
        </button>
    @endif

    <!-- Chat Window (Fixed Bottom Right Corner - Floating) -->
    @if($isOpen)
        <div 
            class="chat-window-floating"
            style="position: fixed; bottom: 20px; right: 20px; z-index: 9999;"
            x-data="{ show: false }"
            x-init="setTimeout(() => show = true, 10)"
            x-show="show"
            x-transition:enter="transition ease-out duration-300 transform"
            x-transition:enter-start="opacity-0 translate-y-4 scale-95"
            x-transition:enter-end="opacity-100 translate-y-0 scale-100"
            x-transition:leave="transition ease-in duration-200 transform"
            x-transition:leave-start="opacity-100 translate-y-0 scale-100"
            x-transition:leave-end="opacity-0 translate-y-4 scale-95"
        >
            <!-- Header with Avatar and Status -->
            <div class="bg-green-600 text-white p-4 flex items-center justify-between">
                <div class="flex items-center gap-3">
                    <!-- Avatar -->
                    <div class="w-10 h-10 rounded-full bg-white text-green-600 flex items-center justify-center font-bold text-sm">
                        AI
                    </div>
                    <div>
                        <div class="font-semibold">AI Assistant</div>
                        <div class="text-xs text-green-100 flex items-center gap-1">
                            <span class="w-2 h-2 bg-green-300 rounded-full animate-pulse"></span>
                            Online
                        </div>
                    </div>
                </div>
                <div class="flex items-center gap-2">
                    <button 
                        wire:click="clearChat"
                        class="p-1.5 hover:bg-green-700 rounded transition-colors"
                        title="Bersihkan Chat"
                    >
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                        </svg>
                    </button>
                    <button 
                        wire:click="toggleChat"
                        class="p-1.5 hover:bg-green-700 rounded transition-colors"
                        title="Tutup Chat"
                    >
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                        </svg>
                    </button>
                </div>
            </div>

            <!-- Messages Area (Dark Theme) -->
            <div 
                class="flex-1 overflow-y-auto p-4 space-y-3 bg-gray-900"
                id="chat-messages"
                x-data="{ 
                    scrollToBottom() {
                        this.$el.scrollTop = this.$el.scrollHeight;
                    }
                }"
                x-init="$watch('$wire.messages', () => { setTimeout(() => scrollToBottom(), 100) })"
            >
                @foreach($messages as $index => $message)
                    <div class="flex {{ $message['type'] === 'user' ? 'justify-end' : 'justify-start' }} {{ $message['type'] === 'user' ? 'message-user' : 'message-bot' }}">
                        @if($message['type'] === 'bot')
                            <!-- Bot Message with Avatar -->
                            <div class="flex items-start gap-2 max-w-[80%]">
                                <div class="w-8 h-8 rounded-full bg-green-600 text-white flex items-center justify-center font-bold text-xs flex-shrink-0">
                                    AI
                                </div>
                                <div class="flex flex-col">
                                    <div class="bg-green-600 text-white rounded-lg px-4 py-2 rounded-tl-none">
                                        @php
                                            // Simple markdown-like formatting
                                            $formatted = $message['message'];
                                            // Convert **text** to <strong>text</strong>
                                            $formatted = preg_replace('/\*\*(.+?)\*\*/', '<strong>$1</strong>', $formatted);
                                            // Convert *text* to <em>text</em>
                                            $formatted = preg_replace('/\*(.+?)\*/', '<em>$1</em>', $formatted);
                                            // Convert - item to bullet list
                                            $formatted = preg_replace('/^- (.+)$/m', '<li>$1</li>', $formatted);
                                            // Wrap consecutive <li> in <ul>
                                            $formatted = preg_replace('/(<li>.*<\/li>\n?)+/s', '<ul class="list-disc list-inside my-1">$0</ul>', $formatted);
                                            // Convert line breaks to <br>
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
                            <!-- User Message -->
                            <div class="flex flex-col items-end max-w-[80%]">
                                <div class="bg-gray-700 text-gray-100 rounded-lg px-4 py-2 rounded-tr-none">
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
                    <div class="flex justify-start">
                        <div class="flex items-start gap-2">
                            <div class="w-8 h-8 rounded-full bg-green-600 text-white flex items-center justify-center font-bold text-xs flex-shrink-0">
                                AI
                            </div>
                            <div class="bg-green-600 text-white rounded-lg px-4 py-2 rounded-tl-none">
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

            <!-- Input Area (Dark Theme) -->
            <div class="border-t border-gray-700 p-4 bg-gray-800">
                <form wire:submit.prevent="sendMessage" class="flex gap-2">
                    <input 
                        type="text" 
                        wire:model="inputMessage"
                        placeholder="Ketik pertanyaan anda"
                        class="flex-1 px-4 py-2 bg-white text-gray-900 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500 focus:border-transparent placeholder-gray-500"
                        style="color: #111827 !important;"
                        :disabled="$isLoading"
                    >
                    <button 
                        type="button"
                        class="p-2 text-gray-400 hover:text-gray-200 transition-colors"
                        title="Lampirkan File"
                    >
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.172 7l-6.586 6.586a2 2 0 102.828 2.828l6.414-6.586a4 4 0 00-5.656-5.656l-6.415 6.585a6 6 0 108.486 8.486L20.5 13"></path>
                        </svg>
                    </button>
                    <button 
                        type="submit"
                        :disabled="$isLoading || empty(trim($inputMessage))"
                        class="bg-green-600 hover:bg-green-700 disabled:bg-gray-600 disabled:cursor-not-allowed text-white p-2 rounded-lg transition-colors"
                        title="Kirim Pesan"
                    >
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8"></path>
                        </svg>
                    </button>
                </form>
                
                <!-- Footer -->
                <div class="mt-2 text-center">
                    <p class="text-xs text-gray-500">Powered By <span class="font-semibold text-gray-400">{{ config('app.name', 'FastBiz') }}</span></p>
                </div>
            </div>
        </div>
        
    @endif

    @script
    <script>
        // Handle Enter key to send message
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Enter' && !e.shiftKey && $wire.isOpen) {
                if (!e.target.disabled && e.target.value.trim()) {
                    e.preventDefault();
                    $wire.sendMessage();
                }
            }
        });

        // Smooth scroll for chat messages
        Livewire.hook('message.processed', (message, component) => {
            if (component.name === 'chat-widget') {
                setTimeout(() => {
                    const chatMessages = document.getElementById('chat-messages');
                    if (chatMessages) {
                        chatMessages.scrollTo({
                            top: chatMessages.scrollHeight,
                            behavior: 'smooth'
                        });
                    }
                }, 100);
            }
        });
    </script>
    @endscript

    <style>
        /* Chat Widget Container - Ensure it's always on top */
        .chat-widget-container {
            position: relative;
        }

        /* Chat Button - Floating in bottom right */
        .chat-button-floating {
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
        }

        .chat-button-floating:hover {
            background-color: #15803d;
            transform: scale(1.1);
        }

        .chat-button-floating:active {
            transform: scale(0.95);
        }

        /* Chat Window - Floating in bottom right */
        .chat-window-floating {
            width: 384px;
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

        @media (max-width: 640px) {
            .chat-window-floating {
                width: calc(100vw - 1rem);
                height: calc(100vh - 1rem);
                bottom: 0.5rem !important;
                right: 0.5rem !important;
            }
            
            .chat-button-floating {
                bottom: 1rem !important;
                right: 1rem !important;
            }
        }

        @keyframes gentlePulse {
            0%, 100% {
                opacity: 1;
                transform: scale(1);
                box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.3), 0 10px 10px -5px rgba(0, 0, 0, 0.2);
            }
            50% {
                opacity: 0.95;
                transform: scale(1.02);
                box-shadow: 0 20px 40px -5px rgba(0, 0, 0, 0.4), 0 15px 15px -5px rgba(0, 0, 0, 0.3);
            }
        }
        
        /* Smooth scrollbar for chat messages */
        #chat-messages::-webkit-scrollbar {
            width: 6px;
        }
        
        #chat-messages::-webkit-scrollbar-track {
            background: #1f2937;
            border-radius: 10px;
        }
        
        #chat-messages::-webkit-scrollbar-thumb {
            background: #4b5563;
            border-radius: 10px;
        }
        
        #chat-messages::-webkit-scrollbar-thumb:hover {
            background: #6b7280;
        }

        /* Smooth message animation */
        @keyframes slideInRight {
            from {
                opacity: 0;
                transform: translateX(20px);
            }
            to {
                opacity: 1;
                transform: translateX(0);
            }
        }

        @keyframes slideInLeft {
            from {
                opacity: 0;
                transform: translateX(-20px);
            }
            to {
                opacity: 1;
                transform: translateX(0);
            }
        }

        .message-user {
            animation: slideInRight 0.3s ease-out;
        }

        .message-bot {
            animation: slideInLeft 0.3s ease-out;
        }
    </style>
</div>
