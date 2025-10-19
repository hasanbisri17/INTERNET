<x-filament-panels::page>
    <form wire:submit="save">
        {{ $this->form }}

        <div class="mt-6 flex justify-end">
            <x-filament::button type="submit" size="lg">
                <x-slot name="icon">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                    </svg>
                </x-slot>
                Simpan Pengaturan
            </x-filament::button>
        </div>
    </form>

    {{-- Info Panel --}}
    <div class="mt-8 p-6 bg-blue-50 dark:bg-blue-900/20 rounded-lg border-2 border-blue-200 dark:border-blue-800">
        <div class="flex items-start gap-4">
            <div class="flex-shrink-0">
                <svg class="w-6 h-6 text-blue-600 dark:text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
            </div>
            <div class="flex-1">
                <h3 class="text-lg font-semibold text-blue-900 dark:text-blue-100 mb-2">
                    Cara Kerja Pengaturan Template
                </h3>
                <div class="text-sm text-blue-800 dark:text-blue-200 space-y-2">
                    <p>
                        <strong>1. Pilih Template:</strong> Untuk setiap jenis notifikasi (Tagihan Baru, Konfirmasi Pembayaran, dll), pilih template yang ingin Anda gunakan dari dropdown.
                    </p>
                    <p>
                        <strong>2. Otomatis Digunakan:</strong> Setelah disimpan, sistem akan otomatis menggunakan template yang dipilih saat mengirim notifikasi.
                    </p>
                    <p>
                        <strong>3. Fallback:</strong> Jika tidak ada template yang dipilih, sistem akan menggunakan template pertama yang aktif (berdasarkan urutan).
                    </p>
                    <p>
                        <strong>4. Kelola Template:</strong> Anda bisa membuat, edit, atau menonaktifkan template di menu <span class="font-semibold text-blue-600 dark:text-blue-400">WhatsApp → Template Pesan</span>.
                    </p>
                </div>
            </div>
        </div>
    </div>

    {{-- Quick Stats --}}
    <div class="mt-6 grid grid-cols-1 md:grid-cols-3 gap-4">
        <div class="p-4 bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700">
            <div class="flex items-center gap-3">
                <div class="flex-shrink-0 w-12 h-12 bg-blue-100 dark:bg-blue-900/30 rounded-lg flex items-center justify-center">
                    <svg class="w-6 h-6 text-blue-600 dark:text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                    </svg>
                </div>
                <div>
                    <p class="text-sm text-gray-500 dark:text-gray-400">Total Template</p>
                    <p class="text-2xl font-bold text-gray-900 dark:text-white">
                        {{ \App\Models\WhatsAppTemplate::count() }}
                    </p>
                </div>
            </div>
        </div>

        <div class="p-4 bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700">
            <div class="flex items-center gap-3">
                <div class="flex-shrink-0 w-12 h-12 bg-green-100 dark:bg-green-900/30 rounded-lg flex items-center justify-center">
                    <svg class="w-6 h-6 text-green-600 dark:text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                </div>
                <div>
                    <p class="text-sm text-gray-500 dark:text-gray-400">Template Aktif</p>
                    <p class="text-2xl font-bold text-gray-900 dark:text-white">
                        {{ \App\Models\WhatsAppTemplate::where('is_active', true)->count() }}
                    </p>
                </div>
            </div>
        </div>

        <div class="p-4 bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700">
            <div class="flex items-center gap-3">
                <div class="flex-shrink-0 w-12 h-12 bg-orange-100 dark:bg-orange-900/30 rounded-lg flex items-center justify-center">
                    <svg class="w-6 h-6 text-orange-600 dark:text-orange-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/>
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                    </svg>
                </div>
                <div>
                    <p class="text-sm text-gray-500 dark:text-gray-400">Template Terkonfigurasi</p>
                    <p class="text-2xl font-bold text-gray-900 dark:text-white">
                        @php
                            $configured = 0;
                            $keys = [
                                'whatsapp_template_billing_new',
                                'whatsapp_template_billing_reminder_1',
                                'whatsapp_template_billing_reminder_2',
                                'whatsapp_template_billing_reminder_3',
                                'whatsapp_template_billing_overdue',
                                'whatsapp_template_billing_paid',
                                'whatsapp_template_service_suspended',
                                'whatsapp_template_service_reactivated',
                            ];
                            foreach ($keys as $key) {
                                if (\App\Models\Setting::get($key)) {
                                    $configured++;
                                }
                            }
                        @endphp
                        {{ $configured }} / {{ count($keys) }}
                    </p>
                </div>
            </div>
        </div>
    </div>
</x-filament-panels::page>

