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
            // App settings
            'app_name' => config('app.name'),
            
            // Company information
            'company_name' => config('app.name', 'Internet Provider'),
            'company_address' => 'Jl. Contoh No. 123, Kota, Provinsi 12345',
            'company_phone' => '021-12345678',
            'company_email' => 'info@company.com',
            
            // Bank information
            'bank_name' => 'Bank BCA',
            'bank_account' => '1234567890',
            'bank_account_name' => 'PT. Internet Provider Indonesia',
            
            // Invoice settings
            'invoice_footer' => 'Terima kasih atas kepercayaan Anda menggunakan layanan kami.',
            'invoice_notes' => 'Catatan: Pembayaran harus dilakukan sebelum tanggal jatuh tempo.',
            'payment_notes' => 'Silakan transfer ke rekening di atas atau hubungi kami untuk metode pembayaran lainnya.',
            
            // Billing settings
            'billing_due_day' => '25', // Tanggal jatuh tempo default (1-31)
            'billing_due_days_offset' => '7', // Berapa hari dari tanggal generate (alternatif untuk billing_due_day)
        ];

        foreach ($settings as $key => $value) {
            Setting::updateOrCreate(
                ['key' => $key],
                ['value' => $value]
            );
        }
    }
}