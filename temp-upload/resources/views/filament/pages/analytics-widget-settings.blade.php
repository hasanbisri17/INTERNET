<x-filament-panels::page>
    <div class="space-y-6">
        <div class="bg-white dark:bg-gray-800 shadow rounded-lg p-6">
            <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-4">
                Konfigurasi Widget Analisis
            </h3>
            <p class="text-sm text-gray-600 dark:text-gray-400 mb-6">
                Atur widget mana saja yang ingin ditampilkan di halaman analisis. Anda dapat mengatur urutan, mengaktifkan/menonaktifkan, dan memberikan judul kustom untuk setiap widget.
            </p>
            
            {{ $this->form }}
        </div>

        <div class="bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-800 rounded-lg p-4">
            <div class="flex">
                <div class="flex-shrink-0">
                    <svg class="h-5 w-5 text-blue-400" viewBox="0 0 20 20" fill="currentColor">
                        <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd" />
                    </svg>
                </div>
                <div class="ml-3">
                    <h3 class="text-sm font-medium text-blue-800 dark:text-blue-200">
                        Tips Penggunaan
                    </h3>
                    <div class="mt-2 text-sm text-blue-700 dark:text-blue-300">
                        <ul class="list-disc list-inside space-y-1">
                            <li>Widget Header akan ditampilkan di bagian atas halaman analisis</li>
                            <li>Widget Footer akan ditampilkan di bagian bawah halaman analisis</li>
                            <li>Urutan widget ditentukan oleh angka pada kolom "Urutan" (1, 2, 3, dst.)</li>
                            <li>Widget yang dinonaktifkan tidak akan ditampilkan di halaman analisis</li>
                            <li>Judul kustom akan menggantikan judul default widget</li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-filament-panels::page>
