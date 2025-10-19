<div class="fi-section rounded-xl bg-white shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10">
    <div class="fi-section-header flex flex-col gap-3 p-6">
        <div class="flex items-center justify-between gap-3">
            <h3 class="text-base font-semibold leading-6 text-gray-950 dark:text-white">
                Informasi Auto Refresh
            </h3>
        </div>
    </div>
    
    <div class="fi-section-content p-6 pt-0">
        <p class="text-sm text-gray-500 dark:text-gray-400">
            Fitur auto refresh akan memperbarui data monitoring secara otomatis setiap {{ $this->getPollingInterval() }} detik.
        </p>
        
        <div class="mt-4">
            <div class="text-sm text-gray-500 dark:text-gray-400">
                <strong>Status:</strong> <span class="text-green-500">Aktif</span>
            </div>
            
            <div class="mt-2 text-sm text-gray-500 dark:text-gray-400">
                <strong>Terakhir diperbarui:</strong> <span data-last-refresh-time>{{ date('H:i:s') }}</span>
            </div>
        </div>
    </div>
</div>