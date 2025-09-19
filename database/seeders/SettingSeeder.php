<?php

namespace Database\Seeders;

use App\Models\Setting;
use Illuminate\Database\Seeder;

class SettingSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Default settings
        $settings = [
            'app_name' => config('app.name'),
            'invoice_footer' => 'Terima kasih atas pembayaran Anda.',
            'invoice_notes' => 'Catatan: Pembayaran harus dilakukan sebelum tanggal jatuh tempo.',
        ];

        foreach ($settings as $key => $value) {
            Setting::updateOrCreate(
                ['key' => $key],
                ['value' => $value]
            );
        }
    }
}