<?php

namespace Database\Seeders;

use App\Models\WhatsAppTemplate;
use Illuminate\Database\Seeder;

class ServiceSuspendTemplateSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $templates = [
            [
                'name' => 'Penangguhan Layanan',
                'code' => 'service.suspended',
                'template_type' => WhatsAppTemplate::TYPE_SERVICE_SUSPENDED,
                'order' => 1,
                'content' => "Yth. {customer_name},\n\n".
                            "⛔ Layanan internet Anda telah dinonaktifkan karena pembayaran belum diterima hingga tanggal 25.\n\n".
                            "📅 Due Date: {due_date}\n".
                            "💰 Total Tagihan: Rp {amount}\n\n".
                            "Silakan segera melakukan pembayaran untuk mengaktifkan kembali layanan Anda.\n\n".
                            "Terima kasih.",
                'description' => 'Template notifikasi saat layanan customer di-suspend',
                'variables' => ['customer_name', 'due_date', 'amount'],
                'is_active' => true,
            ],
            [
                'name' => 'Pengaktifan Kembali Layanan',
                'code' => 'service.reactivated',
                'template_type' => WhatsAppTemplate::TYPE_SERVICE_REACTIVATED,
                'order' => 1,
                'content' => "Yth. {customer_name},\n\n".
                            "✅ Layanan internet Anda telah diaktifkan kembali.\n\n".
                            "Terima kasih atas pembayaran Anda. Selamat menikmati layanan internet kami.\n\n".
                            "Jika ada kendala, silakan hubungi kami.\n\n".
                            "Terima kasih.",
                'description' => 'Template notifikasi saat layanan customer diaktifkan kembali setelah suspend',
                'variables' => ['customer_name'],
                'is_active' => true,
            ],
        ];

        foreach ($templates as $templateData) {
            WhatsAppTemplate::updateOrCreate(
                [
                    'code' => $templateData['code'],
                ],
                $templateData
            );
        }

        $this->command->info('✅ Service suspend/reactivate templates created successfully!');
    }
}

