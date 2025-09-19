<x-filament-panels::page>
    <form wire:submit="save">
        {{ $this->form }}

        <div class="mt-6 flex justify-end">
            <x-filament::button type="submit">
                Simpan
            </x-filament::button>
        </div>
    </form>

    <div class="mt-8">
        <div class="text-xl font-bold mb-4">Pratinjau Invoice</div>
        <div class="bg-white p-6 rounded-lg shadow-md">
            <div class="flex justify-between items-center mb-6">
                <div>
                    @if(\App\Models\Setting::get('invoice_logo'))
                        <img src="{{ \Illuminate\Support\Facades\Storage::disk('public')->url(\App\Models\Setting::get('invoice_logo')) }}" 
                             alt="Logo Perusahaan" 
                             class="max-h-24 max-w-xs object-contain" />
                    @else
                        <div class="text-2xl font-bold">{{ config('app.name') }}</div>
                    @endif
                </div>
                <div class="text-3xl font-bold">INVOICE</div>
            </div>
            
            <div class="border-t border-b border-gray-200 py-4 my-4">
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <div class="font-semibold">Kepada:</div>
                        <div>Nama Pelanggan</div>
                        <div>Alamat Pelanggan</div>
                    </div>
                    <div class="text-right">
                        <div><span class="font-semibold">No. Invoice:</span> INV-001</div>
                        <div><span class="font-semibold">Tanggal:</span> {{ date('d/m/Y') }}</div>
                        <div><span class="font-semibold">Jatuh Tempo:</span> {{ date('d/m/Y', strtotime('+7 days')) }}</div>
                    </div>
                </div>
            </div>
            
            <table class="w-full mb-6">
                <thead>
                    <tr class="border-b-2 border-gray-200">
                        <th class="py-2 text-left">Deskripsi</th>
                        <th class="py-2 text-right">Jumlah</th>
                        <th class="py-2 text-right">Harga</th>
                        <th class="py-2 text-right">Total</th>
                    </tr>
                </thead>
                <tbody>
                    <tr class="border-b border-gray-200">
                        <td class="py-2">Layanan Internet</td>
                        <td class="py-2 text-right">1</td>
                        <td class="py-2 text-right">Rp 300.000</td>
                        <td class="py-2 text-right">Rp 300.000</td>
                    </tr>
                    <tr class="border-b border-gray-200">
                        <td class="py-2">Biaya Instalasi</td>
                        <td class="py-2 text-right">1</td>
                        <td class="py-2 text-right">Rp 100.000</td>
                        <td class="py-2 text-right">Rp 100.000</td>
                    </tr>
                </tbody>
                <tfoot>
                    <tr>
                        <td colspan="3" class="py-2 text-right font-semibold">Subtotal:</td>
                        <td class="py-2 text-right">Rp 400.000</td>
                    </tr>
                    <tr>
                        <td colspan="3" class="py-2 text-right font-semibold">PPN (11%):</td>
                        <td class="py-2 text-right">Rp 44.000</td>
                    </tr>
                    <tr class="font-bold">
                        <td colspan="3" class="py-2 text-right">Total:</td>
                        <td class="py-2 text-right">Rp 444.000</td>
                    </tr>
                </tfoot>
            </table>
            
            <div class="mt-6">
                <div class="font-semibold mb-2">Catatan:</div>
                <div class="text-sm">{!! \App\Models\Setting::get('invoice_notes') !!}</div>
            </div>
            
            <div class="mt-8 text-center text-sm">
                {!! \App\Models\Setting::get('invoice_footer') !!}
            </div>
        </div>
    </div>
</x-filament-panels::page>