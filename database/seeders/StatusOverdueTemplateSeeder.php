<?php

namespace Database\Seeders;

use App\Models\WhatsAppTemplate;
use Illuminate\Database\Seeder;

class StatusOverdueTemplateSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Check if template already exists
        $existingTemplate = WhatsAppTemplate::where('template_type', WhatsAppTemplate::TYPE_STATUS_OVERDUE)->first();
        
        if ($existingTemplate) {
            $this->command->info('Status Overdue template already exists. Skipping...');
            return;
        }

        // Create new template
        WhatsAppTemplate::create([
            'name' => 'Notifikasi Status Payment Overdue',
            'code' => 'status.overdue',
            'template_type' => WhatsAppTemplate::TYPE_STATUS_OVERDUE,
            'order' => 1,
            'content' => "Yth. {customer_name},\n\n".
                        "⚠️ Tagihan Anda telah melewati jatuh tempo.\n\n".
                        "📅 Due Date: {due_date}\n".
                        "💰 Total Tagihan: Rp {amount}\n".
                        "📆 Terlambat: {days_overdue} hari\n\n".
                        "Layanan akan dinonaktifkan jika pembayaran belum diterima hari ini.\n\n".
                        "Silakan segera melakukan pembayaran untuk menghindari pemutusan layanan.\n\n".
                        "Terima kasih.",
            'description' => 'Template notifikasi saat status payment berubah menjadi overdue',
            'variables' => ['customer_name', 'invoice_number', 'amount', 'due_date', 'days_overdue'],
            'is_active' => true,
        ]);

        $this->command->info('Status Overdue template created successfully!');
    }
}

