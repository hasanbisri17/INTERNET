<x-filament-panels::page>
    <style>
        .custom-tabs {
            border-bottom: 1px solid rgb(229 231 235 / 1);
            margin-bottom: 1.5rem;
        }
        
        .custom-tabs-nav {
            display: flex;
            gap: 0;
            margin: 0;
            padding: 0;
            list-style: none;
        }
        
        .custom-tab-button {
            position: relative;
            padding: 0.75rem 1.5rem;
            font-weight: 500;
            font-size: 0.875rem;
            color: rgb(107 114 128 / 1);
            background: transparent;
            border: none;
            border-bottom: 2px solid transparent;
            cursor: pointer;
            transition: all 0.2s;
        }
        
        .custom-tab-button:hover {
            color: rgb(59 130 246 / 1);
            background: rgb(239 246 255 / 1);
        }
        
        .custom-tab-button.active {
            color: rgb(37 99 235 / 1);
            border-bottom-color: rgb(37 99 235 / 1);
            background: rgb(239 246 255 / 1);
        }
        
        .custom-tab-content {
            display: none;
        }
        
        .custom-tab-content.active {
            display: block;
        }

        /* Dark mode styles */
        .dark .custom-tabs {
            border-bottom-color: rgb(55 65 81 / 1);
        }
        
        .dark .custom-tab-button {
            color: rgb(156 163 175 / 1);
        }
        
        .dark .custom-tab-button:hover {
            color: rgb(96 165 250 / 1);
            background: rgb(30 58 138 / 0.3);
        }
        
        .dark .custom-tab-button.active {
            color: rgb(96 165 250 / 1);
            border-bottom-color: rgb(96 165 250 / 1);
            background: rgb(30 58 138 / 0.3);
        }
    </style>

    <!-- Tabs Navigation -->
    <div class="custom-tabs">
        <ul class="custom-tabs-nav" role="tablist">
            <li role="presentation">
                <button 
                    class="custom-tab-button active" 
                    id="tab-info" 
                    data-tab="info" 
                    role="tab" 
                    aria-selected="true"
                    onclick="switchTab('info')"
                >
                    Info
                </button>
            </li>
            <li role="presentation">
                <button 
                    class="custom-tab-button" 
                    id="tab-hasil" 
                    data-tab="hasil" 
                    role="tab" 
                    aria-selected="false"
                    onclick="switchTab('hasil')"
                >
                    Hasil
                </button>
            </li>
        </ul>
    </div>

    <!-- Tab Content: Info -->
    <div class="custom-tab-content active" id="content-info">
        {{ $this->infolist }}
    </div>

    <!-- Tab Content: Hasil (Results) -->
    <div class="custom-tab-content" id="content-hasil">
        <div class="space-y-6">
            <!-- Statistics Cards -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6 border border-gray-200 dark:border-gray-700">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm font-medium text-gray-600 dark:text-gray-400">Total Kontak</p>
                            <p class="text-3xl font-bold text-gray-900 dark:text-white mt-2">
                                {{ $record->messages()->count() }}
                            </p>
                        </div>
                        <div class="w-12 h-12 bg-blue-100 dark:bg-blue-900/30 rounded-lg flex items-center justify-center">
                            <svg class="w-6 h-6 text-blue-600 dark:text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"/>
                            </svg>
                        </div>
                    </div>
                </div>

                <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6 border border-gray-200 dark:border-gray-700">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm font-medium text-gray-600 dark:text-gray-400">Berhasil</p>
                            <p class="text-3xl font-bold text-green-600 dark:text-green-400 mt-2">
                                {{ $record->messages()->where('status', 'sent')->count() }}
                            </p>
                        </div>
                        <div class="w-12 h-12 bg-green-100 dark:bg-green-900/30 rounded-lg flex items-center justify-center">
                            <svg class="w-6 h-6 text-green-600 dark:text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                            </svg>
                        </div>
                    </div>
                    @if($record->messages()->count() > 0)
                        <p class="text-xs text-gray-500 dark:text-gray-400 mt-2">
                            {{ number_format(($record->messages()->where('status', 'sent')->count() / $record->messages()->count()) * 100, 1) }}% dari total
                        </p>
                    @endif
                </div>

                <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6 border border-gray-200 dark:border-gray-700">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm font-medium text-gray-600 dark:text-gray-400">Gagal</p>
                            <p class="text-3xl font-bold text-red-600 dark:text-red-400 mt-2">
                                {{ $record->messages()->where('status', 'failed')->count() }}
                            </p>
                        </div>
                        <div class="w-12 h-12 bg-red-100 dark:bg-red-900/30 rounded-lg flex items-center justify-center">
                            <svg class="w-6 h-6 text-red-600 dark:text-red-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                            </svg>
                        </div>
                    </div>
                    @if($record->messages()->count() > 0)
                        <p class="text-xs text-gray-500 dark:text-gray-400 mt-2">
                            {{ number_format(($record->messages()->where('status', 'failed')->count() / $record->messages()->count()) * 100, 1) }}% dari total
                        </p>
                    @endif
                </div>
            </div>

            <!-- Recipients List -->
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow border border-gray-200 dark:border-gray-700">
                <div class="p-6 border-b border-gray-200 dark:border-gray-700 flex items-center justify-between">
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-white">
                        Daftar Penerima ({{ $record->messages()->count() }} Kontak)
                    </h3>
                </div>

                <div class="overflow-x-auto">
                    <table class="w-full divide-y divide-gray-200 dark:divide-gray-700">
                        <thead class="bg-gradient-to-r from-orange-500 to-orange-600 dark:from-orange-600 dark:to-orange-700">
                            <tr>
                                <th scope="col" class="px-6 py-4 text-left text-xs font-semibold text-white uppercase tracking-wider">
                                    Nama Pelanggan
                                </th>
                                <th scope="col" class="px-6 py-4 text-left text-xs font-semibold text-white uppercase tracking-wider">
                                    No. WhatsApp
                                </th>
                                <th scope="col" class="px-6 py-4 text-left text-xs font-semibold text-white uppercase tracking-wider">
                                    Status
                                </th>
                                <th scope="col" class="px-6 py-4 text-left text-xs font-semibold text-white uppercase tracking-wider">
                                    Waktu Kirim
                                </th>
                            </tr>
                        </thead>
                        <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                            @forelse($record->messages as $message)
                                <tr class="hover:bg-orange-50 dark:hover:bg-orange-900/20 transition-colors cursor-pointer">
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="flex items-center">
                                            <div class="flex-shrink-0 h-10 w-10">
                                                <div class="h-10 w-10 rounded-full bg-primary-100 dark:bg-primary-900/30 flex items-center justify-center">
                                                    <span class="text-primary-600 dark:text-primary-400 font-medium text-sm">
                                                        {{ substr($message->customer->name ?? 'N/A', 0, 2) }}
                                                    </span>
                                                </div>
                                            </div>
                                            <div class="ml-4">
                                                <div class="text-sm font-medium text-gray-900 dark:text-gray-100">
                                                    {{ $message->customer->name ?? 'N/A' }}
                                                </div>
                                                @if($message->customer && $message->customer->internetPackage)
                                                    <div class="text-xs text-gray-500 dark:text-gray-400">
                                                        {{ $message->customer->internetPackage->name }}
                                                    </div>
                                                @endif
                                            </div>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="text-sm text-gray-900 dark:text-gray-100">
                                            {{ $message->customer->phone ?? '-' }}
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        @if($message->status === 'sent')
                                            <span class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-400">
                                                <svg class="w-3.5 h-3.5" fill="currentColor" viewBox="0 0 20 20">
                                                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                                                </svg>
                                                Berhasil
                                            </span>
                                        @elseif($message->status === 'failed')
                                            <span class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-400">
                                                <svg class="w-3.5 h-3.5" fill="currentColor" viewBox="0 0 20 20">
                                                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"/>
                                                </svg>
                                                Gagal
                                            </span>
                                        @else
                                            <span class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full text-xs font-medium bg-yellow-100 text-yellow-800 dark:bg-yellow-900/30 dark:text-yellow-400">
                                                <svg class="w-3.5 h-3.5" fill="currentColor" viewBox="0 0 20 20">
                                                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm1-12a1 1 0 10-2 0v4a1 1 0 00.293.707l2.828 2.829a1 1 0 101.415-1.415L11 9.586V6z" clip-rule="evenodd"/>
                                                </svg>
                                                {{ ucfirst($message->status) }}
                                            </span>
                                        @endif
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">
                                        {{ $message->sent_at ? $message->sent_at->format('d M Y, H:i') : '-' }}
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="4" class="px-6 py-12 text-center">
                                        <div class="flex flex-col items-center justify-center text-gray-500 dark:text-gray-400">
                                            <svg class="w-12 h-12 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4"/>
                                            </svg>
                                            <p class="text-sm font-medium">Tidak ada data penerima</p>
                                        </div>
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <script>
        function switchTab(tabName) {
            // Hide all tab contents
            document.querySelectorAll('.custom-tab-content').forEach(content => {
                content.classList.remove('active');
            });
            
            // Deactivate all tab buttons
            document.querySelectorAll('.custom-tab-button').forEach(button => {
                button.classList.remove('active');
                button.setAttribute('aria-selected', 'false');
            });
            
            // Show selected tab content
            document.getElementById('content-' + tabName).classList.add('active');
            
            // Activate selected tab button
            document.getElementById('tab-' + tabName).classList.add('active');
            document.getElementById('tab-' + tabName).setAttribute('aria-selected', 'true');
        }
        
        // Auto-refresh every 3 seconds when campaign is processing
        @if($record->status === 'processing')
        setInterval(function() {
            // Reload the page to get updated data
            window.location.reload();
        }, 3000);
        @endif
    </script>
</x-filament-panels::page>

