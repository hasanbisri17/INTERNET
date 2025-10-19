<x-filament-panels::page>
    <style>
        @media (min-width: 1024px) {
            .broadcast-layout {
                display: grid;
                grid-template-columns: 1fr 400px;
                gap: 1.5rem;
            }
        }

        /* FORCE HIDE Filament FileUpload preview to prevent CORS/404 errors */
        [wire\\:id] .filepond--image-preview-wrapper,
        [wire\\:id] .filepond--image-preview,
        .filepond--image-preview-wrapper,
        .filepond--image-preview {
            display: none !important;
            visibility: hidden !important;
            height: 0 !important;
            max-height: 0 !important;
            overflow: hidden !important;
        }

        /* Hide file status that causes preview loading */
        .filepond--file-status-main {
            display: none !important;
        }

        /* Compact file upload area */
        .filepond--root {
            min-height: 60px !important;
        }

        .filepond--item {
            min-height: 40px !important;
        }
    </style>
    
    <div class="broadcast-layout">
        <!-- Form Section (Left) - 60% width -->
        <div class="space-y-6">
            <form wire:submit="sendBroadcast">
                {{ $this->form }}

                <div class="mt-6 flex items-center justify-between">
                    <div class="text-sm text-gray-600 dark:text-gray-400">
                        <span class="font-semibold">{{ $this->getRecipientCount() }}</span> penerima akan menerima pesan ini
                    </div>
                    
                    <x-filament::button 
                        type="submit"
                        size="lg"
                        icon="heroicon-o-paper-airplane"
                    >
                        Kirim Broadcast
                    </x-filament::button>
                </div>
            </form>
        </div>

        <!-- Preview Section (Right) - 40% width -->
        <div class="sticky top-6 self-start">
            <div class="bg-white dark:bg-gray-800 rounded-xl shadow-lg overflow-hidden border border-gray-200 dark:border-gray-700">
                <!-- Preview Header -->
                <div class="bg-gradient-to-r from-green-600 to-green-700 px-4 py-3 flex items-center space-x-3">
                    <div class="w-10 h-10 rounded-full bg-white/20 flex items-center justify-center">
                        <svg class="w-6 h-6 text-white" fill="currentColor" viewBox="0 0 24 24">
                            <path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413z"/>
                        </svg>
                    </div>
                    <div class="flex-1">
                        <h3 class="text-white font-semibold text-sm">Preview Pesan</h3>
                        <p class="text-white/80 text-xs">Tampilan di WhatsApp</p>
                    </div>
                </div>

                <!-- WhatsApp Chat Background -->
                <div class="bg-[#e5ddd5] dark:bg-gray-900 p-4 min-h-[650px] max-h-[700px] overflow-y-auto" 
                     style="background-image: url('data:image/svg+xml;base64,PHN2ZyB3aWR0aD0iMTAwJSIgaGVpZ2h0PSIxMDAlIiB4bWxucz0iaHR0cDovL3d3dy53My5vcmcvMjAwMC9zdmciPjxkZWZzPjxwYXR0ZXJuIGlkPSJhIiBwYXR0ZXJuVW5pdHM9InVzZXJTcGFjZU9uVXNlIiB3aWR0aD0iNDAiIGhlaWdodD0iNDAiPjxwYXRoIGQ9Ik0wIDBoNDB2NDBIMHoiIGZpbGw9Im5vbmUiLz48cGF0aCBkPSJNMjAgMjBsLTEgMSIgc3Ryb2tlPSIjZDlkOWQ5IiBzdHJva2Utd2lkdGg9IjEiLz48L3BhdHRlcm4+PC9kZWZzPjxyZWN0IHdpZHRoPSIxMDAlIiBoZWlnaHQ9IjEwMCUiIGZpbGw9InVybCgjYSkiLz48L3N2Zz4=');"
                     wire:poll.5s>
                    
                    <!-- Message Bubble -->
                    <div class="flex justify-end mb-2">
                        <div class="max-w-[85%]">
                            <!-- DEBUG: Media Path = {{ $mediaPath ?? 'NULL' }} -->
                            <!-- Image Indicator (if uploaded) -->
                            @if(!empty($mediaPath))
                                <div class="rounded-lg shadow-md mb-2 p-4 flex items-center space-x-3 border-2 border-green-500" wire:key="image-indicator-{{ $mediaPath }}">
                                    <div class="w-12 h-12 bg-green-100 rounded-lg flex items-center justify-center flex-shrink-0">
                                        <svg class="w-8 h-8 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                                        </svg>
                                    </div>
                                    <div class="flex-1 min-w-0">
                                        <p class="text-sm font-bold !text-black dark:!text-black">📷 Gambar Siap Dikirim</p>
                                        <p class="text-xs !text-gray-600 dark:!text-gray-700 truncate">{{ basename($mediaPath) }}</p>
                                    </div>
                                    <div class="flex-shrink-0">
                                        <svg class="w-6 h-6 text-green-600" fill="currentColor" viewBox="0 0 20 20">
                                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                                        </svg>
                                    </div>
                                </div>
                            @endif

                            <!-- Document Indicator (if uploaded) -->
                            @if(!empty($documentPath))
                                <div class="rounded-lg shadow-md mb-2 p-4 flex items-center space-x-3 border-2 border-blue-500" wire:key="doc-indicator-{{ $documentPath }}">
                                    <div class="w-12 h-12 bg-blue-100 rounded-lg flex items-center justify-center flex-shrink-0">
                                        <svg class="w-8 h-8 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z"/>
                                        </svg>
                                    </div>
                                    <div class="flex-1 min-w-0">
                                        <p class="text-sm font-bold !text-black dark:!text-black">📄 Dokumen Siap Dikirim</p>
                                        <p class="text-xs !text-gray-600 dark:!text-gray-700 truncate">{{ basename($documentPath) }}</p>
                                    </div>
                                    <div class="flex-shrink-0">
                                        <svg class="w-6 h-6 text-blue-600" fill="currentColor" viewBox="0 0 20 20">
                                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                                        </svg>
                                    </div>
                                </div>
                            @endif

                            <!-- Text Message Bubble -->
                            <div class="bg-[#dcf8c6] rounded-lg px-3 py-2 shadow-md relative">
                                @if(empty($message))
                                    <p class="text-white text-sm italic">
                                        Ketik pesan untuk melihat preview...
                                    </p>
                                @else
                                    <div class="text-white text-sm whitespace-pre-wrap break-words prose prose-sm max-w-none prose-strong:font-bold prose-strong:text-white prose-em:italic prose-em:text-white">
                                        {!! nl2br(strip_tags($message, '<strong><em><b><i><s><del><strike><code><br><p><ul><ol><li><h2><h3><blockquote>')) !!}
                                    </div>
                                @endif
                                
                                <!-- Timestamp and Check Marks -->
                                <div class="flex items-center justify-end space-x-1 mt-1">
                                    <span class="text-[10px] text-white">
                                        {{ now()->format('H:i') }}
                                    </span>
                                    <svg class="w-4 h-4 text-white" fill="currentColor" viewBox="0 0 16 16">
                                        <path d="M12.354 4.354a.5.5 0 0 0-.708-.708L5 10.293 1.854 7.146a.5.5 0 1 0-.708.708l3.5 3.5a.5.5 0 0 0 .708 0l7-7zm-4.208 7-.896-.897.707-.707.543.543 6.646-6.647a.5.5 0 0 1 .708.708l-7 7a.5.5 0 0 1-.708 0z"/>
                                        <path d="m5.354 7.146.896.897-.707.707-.897-.896a.5.5 0 1 1 .708-.708z"/>
                                    </svg>
                                </div>

                                <!-- Bubble Tail -->
                                <div class="absolute -right-2 bottom-0 w-0 h-0 border-l-[10px] border-l-[#dcf8c6] border-b-[10px] border-b-transparent"></div>
                            </div>
                        </div>
                    </div>

                    <!-- Info Text -->
                    <div class="mt-4 text-center">
                        <p class="text-xs text-gray-600 dark:text-gray-400 bg-white/50 dark:bg-gray-800/50 rounded-full px-3 py-1 inline-block">
                            🔒 Preview ini hanya simulasi tampilan
                        </p>
                    </div>
                </div>

                <!-- Preview Footer -->
                <div class="bg-gray-50 dark:bg-gray-700 px-4 py-3 border-t border-gray-200 dark:border-gray-600">
                    <div class="flex items-center justify-between text-xs text-gray-600 dark:text-gray-400">
                        <div class="flex items-center space-x-2">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                            </svg>
                            <span>Variabel akan diganti otomatis</span>
                        </div>
                        @if($message)
                            <span class="font-medium">{{ strlen($message) }} karakter</span>
                        @endif
                    </div>
                </div>
            </div>

            <!-- Tips Card -->
            <div class="mt-6 bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-800 rounded-lg p-4">
                <h4 class="text-sm font-semibold text-blue-900 dark:text-blue-100 mb-2 flex items-center">
                    <svg class="w-4 h-4 mr-2" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd"/>
                    </svg>
                    Tips Variabel
                </h4>
                <ul class="text-xs text-blue-800 dark:text-blue-200 space-y-1">
                    <li><code class="bg-blue-100 dark:bg-blue-800 px-1 rounded">{nama}</code> - Nama pelanggan</li>
                    <li><code class="bg-blue-100 dark:bg-blue-800 px-1 rounded">{paket}</code> - Nama paket internet</li>
                    <li><code class="bg-blue-100 dark:bg-blue-800 px-1 rounded">{tagihan}</code> - Jumlah tagihan</li>
                    <li><code class="bg-blue-100 dark:bg-blue-800 px-1 rounded">{status}</code> - Status pelanggan</li>
                </ul>
            </div>

            <!-- Emoji Helper Card -->
            <div class="mt-4 bg-gradient-to-r from-yellow-50 to-orange-50 dark:from-yellow-900/20 dark:to-orange-900/20 border border-yellow-200 dark:border-yellow-800 rounded-lg p-4">
                <h4 class="text-sm font-semibold text-yellow-900 dark:text-yellow-100 mb-3 flex items-center">
                    😊 Cara Tambah Emoji
                </h4>
                <div class="space-y-3">
                    <div class="flex items-start space-x-3">
                        <div class="w-8 h-8 rounded-full bg-yellow-200 dark:bg-yellow-700 flex items-center justify-center flex-shrink-0 text-lg">
                            💻
                        </div>
                        <div>
                            <p class="text-sm font-medium text-yellow-900 dark:text-yellow-100">Windows</p>
                            <p class="text-xs text-yellow-700 dark:text-yellow-300">
                                Tekan <kbd class="px-2 py-1 bg-yellow-200 dark:bg-yellow-800 rounded text-xs font-mono">Win + .</kbd> (titik)
                            </p>
                        </div>
                    </div>
                    <div class="flex items-start space-x-3">
                        <div class="w-8 h-8 rounded-full bg-yellow-200 dark:bg-yellow-700 flex items-center justify-center flex-shrink-0 text-lg">
                            🍎
                        </div>
                        <div>
                            <p class="text-sm font-medium text-yellow-900 dark:text-yellow-100">Mac</p>
                            <p class="text-xs text-yellow-700 dark:text-yellow-300">
                                Tekan <kbd class="px-2 py-1 bg-yellow-200 dark:bg-yellow-800 rounded text-xs font-mono">Cmd + Ctrl + Space</kbd>
                            </p>
                        </div>
                    </div>
                </div>
                <div class="mt-4 p-3 bg-white/50 dark:bg-gray-800/50 rounded-lg border border-yellow-300 dark:border-yellow-700">
                    <p class="text-xs text-yellow-800 dark:text-yellow-200">
                        <strong>💡 Tip:</strong> Emoji akan langsung muncul di posisi cursor Anda saat mengetik di editor
                    </p>
                </div>
            </div>
        </div>
    </div>
</x-filament-panels::page>

<script>
    // Listen for Livewire events to refresh preview
    document.addEventListener('livewire:initialized', () => {
        // Listen for media upload completion
        Livewire.on('media-updated', () => {
            // Force refresh of the preview section
            console.log('Media updated, refreshing preview...');
        });

        // Listen for document upload completion
        Livewire.on('document-updated', () => {
            // Force refresh of the preview section
            console.log('Document updated, refreshing preview...');
        });
    });

    // Fix for file upload spinner issue - ensure it completes properly
    document.addEventListener('livewire:init', () => {
        Livewire.hook('morph.updated', ({ el, component }) => {
            // Check if this is a file upload field
            if (el.querySelector('[x-data*="fileUploadFormComponent"]')) {
                console.log('File upload component updated');
            }
        });
    });
</script>

