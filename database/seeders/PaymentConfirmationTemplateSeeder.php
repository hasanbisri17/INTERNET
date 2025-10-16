<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\WhatsAppTemplate;

class PaymentConfirmationTemplateSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Check if template already exists
        $existing = WhatsAppTemplate::where('template_type', WhatsAppTemplate::TYPE_BILLING_PAID)->first();
        
        if ($existing) {
            $this->command->info('✅ Template Konfirmasi Pembayaran sudah ada, skip...');
            return;
        }

        // Create default payment confirmation template
        WhatsAppTemplate::create([
            'name' => 'Konfirmasi Pembayaran - Default',
            'code' => 'billing.paid',
            'template_type' => WhatsAppTemplate::TYPE_BILLING_PAID,
            'order' => 1,
            'is_active' => true,
            'description' => 'Template default untuk notifikasi pembayaran berhasil diterima',
            'variables' => [
                'customer_name' => 'Nama customer',
                'invoice_number' => 'Nomor invoice',
                'amount' => 'Jumlah pembayaran',
                'payment_date' => 'Tanggal pembayaran',
                'period' => 'Periode tagihan',
            ],
            'content' => "✅ *Pembayaran Diterima*\n\nHalo {customer_name},\n\nTerima kasih! Pembayaran Anda telah kami terima. 💚\n\n📋 *Detail Pembayaran:*\nInvoice: {invoice_number}\nJumlah: Rp {amount}\nPeriode: {period}\nTanggal: {payment_date}\n\n✅ Status: *LUNAS*\n🌐 Internet Anda *SUDAH AKTIF* kembali!\n\nTerlampir bukti pembayaran.\n\nTerima kasih atas kepercayaan Anda! 🙏",
        ]);

        $this->command->info('✅ Template Konfirmasi Pembayaran berhasil dibuat!');
    }
}
