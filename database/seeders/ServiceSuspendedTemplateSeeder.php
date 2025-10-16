<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\WhatsAppTemplate;

class ServiceSuspendedTemplateSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Check if template already exists
        $existing = WhatsAppTemplate::where('template_type', WhatsAppTemplate::TYPE_SERVICE_SUSPENDED)->first();
        
        if ($existing) {
            $this->command->info('✅ Template Penangguhan Layanan sudah ada, skip...');
            return;
        }

        // Create default service suspended template
        WhatsAppTemplate::create([
            'name' => 'Penangguhan Layanan - Default',
            'code' => 'service.suspended',
            'template_type' => WhatsAppTemplate::TYPE_SERVICE_SUSPENDED,
            'order' => 1,
            'is_active' => true,
            'description' => 'Template notifikasi saat layanan customer ditangguhkan karena tunggakan pembayaran',
            'variables' => [
                'customer_name' => 'Nama customer',
                'invoice_number' => 'Nomor invoice',
                'amount' => 'Jumlah tagihan',
                'days_overdue' => 'Jumlah hari keterlambatan',
                'due_date' => 'Tanggal jatuh tempo',
            ],
            'content' => "⚠️ *Pemberitahuan Penangguhan Layanan*\n\nYth. {customer_name},\n\nLayanan internet Anda telah ditangguhkan sementara karena pembayaran tagihan yang belum kami terima.\n\n📋 *Detail Tagihan:*\nNo. Invoice: {invoice_number}\nJumlah: Rp {amount}\nJatuh Tempo: {due_date}\nTerlambat: {days_overdue} hari\n\n💳 *Segera lakukan pembayaran* untuk mengaktifkan kembali layanan Anda.\n\nSetelah pembayaran, layanan akan aktif kembali secara otomatis.\n\nTerima kasih atas pengertian Anda.",
        ]);

        $this->command->info('✅ Template Penangguhan Layanan berhasil dibuat!');
    }
}

