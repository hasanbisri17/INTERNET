# WhatsApp Reminder & Overdue dengan PDF Invoice

## 📋 Overview

Sistem reminder dan overdue sekarang **OTOMATIS mengirim PDF invoice** bersama dengan pesan WhatsApp kepada customer yang belum membayar tagihan.

Setiap kali sistem mengirim pesan reminder atau overdue, customer akan menerima:
✅ **Pesan Text** dengan informasi reminder  
✅ **PDF Invoice** yang menampilkan detail tagihan

## 🔧 Perubahan Teknis

### 1. Update SendPaymentReminders.php

**SEBELUM:**
```php
// Line 167 - Parameter salah posisi
$this->whatsapp->sendBillingNotification($payment, $serviceType, $rule->whatsappTemplate);
// Parameter 3: $rule->whatsappTemplate (SALAH - seharusnya boolean $sendPDF)
// Parameter 4: (kosong) - seharusnya untuk custom template
```

**SESUDAH:**
```php
// Line 167 - Parameter sudah benar
$this->whatsapp->sendBillingNotification($payment, $serviceType, true, $rule->whatsappTemplate);
// Parameter 3: true (BENAR - kirim PDF)
// Parameter 4: $rule->whatsappTemplate (BENAR - custom template dari rule)
```

### 2. Update WhatsAppService.php

**SEBELUM:**
```php
// Line 451 - Hanya untuk 'new' dan 'paid'
if ($sendPDF && in_array($type, ['new', 'paid'])) {
    $pdfPath = $this->generateInvoicePDF($payment);
}
```

**SESUDAH:**
```php
// Line 451-453 - Termasuk semua type reminder dan overdue
$typesWithPDF = ['new', 'paid', 'reminder', 'reminder_h3', 'reminder_h1', 'reminder_h0', 'overdue'];

if ($sendPDF && in_array($type, $typesWithPDF)) {
    $pdfPath = $this->generateInvoicePDF($payment);
}
```

## 📝 Jenis Reminder yang Mengirim PDF

Semua jenis reminder sekarang otomatis mengirim PDF invoice:

| Timing | Service Type | Deskripsi | PDF Invoice |
|--------|-------------|-----------|-------------|
| H-7 atau lebih | `reminder` | Reminder umum sebelum jatuh tempo | ✅ Ya |
| H-3 | `reminder_h3` | Reminder 3 hari sebelum jatuh tempo | ✅ Ya |
| H-1 | `reminder_h1` | Reminder 1 hari sebelum jatuh tempo | ✅ Ya |
| H-0 | `reminder_h0` | Reminder di hari jatuh tempo | ✅ Ya |
| H+1, H+2, dst | `overdue` | Reminder setelah jatuh tempo (overdue) | ✅ Ya |

## 🎨 Tampilan PDF Invoice untuk Reminder

PDF invoice yang dikirim bersama reminder akan menampilkan:

### Status Belum Lunas (Pending)
- Badge **"BELUM LUNAS"** (kuning)
- Informasi periode tagihan
- Tanggal jatuh tempo
- Jumlah tagihan
- Informasi pembayaran (bank, rekening)

### Status Terlambat (Overdue)
- Badge **"TERLAMBAT"** (merah)
- Highlight bahwa tagihan sudah melewati jatuh tempo
- Informasi periode tagihan
- Tanggal jatuh tempo yang sudah lewat
- Jumlah tagihan
- Informasi pembayaran (bank, rekening)

## 🚀 Cara Kerja

### 1. Otomatis via Cron Job

Command scheduler akan menjalankan reminder setiap hari:

```bash
# Di Kernel.php sudah terjadwal
$schedule->command('whatsapp:payment-reminders')->daily();
```

**Proses:**
1. Sistem mengecek semua **Reminder Rules** yang aktif
2. Mencari pembayaran yang sesuai dengan timing rule
3. Generate PDF invoice untuk setiap pembayaran
4. Kirim WhatsApp dengan **text message + PDF invoice**
5. Catat di database (payment_reminders & whats_app_messages)

### 2. Manual via Command

Jalankan manual untuk testing:

```bash
# Send reminders (production mode)
php artisan whatsapp:payment-reminders

# Dry run (preview only, tidak kirim)
php artisan whatsapp:payment-reminders --dry-run
```

**Output:**
```
=== Payment Reminder System (Dynamic Rules) ===
Date: 13 October 2025 12:00:00
📋 Found 4 active reminder rules

Processing: Reminder H-3
  Timing: 3 hari sebelum jatuh tempo
──────────────────────────────────────────────────────────
  Found 5 payments to remind
  ✅ Sent to: John Doe (6281234567890) - INV-202510-0001
  ✅ Sent to: Jane Smith (6289876543210) - INV-202510-0002
  ✅ Sent: 5

Processing: Reminder Overdue H+1
  Timing: 1 hari setelah jatuh tempo
──────────────────────────────────────────────────────────
  Found 2 payments to remind
  ✅ Sent to: Bob Johnson (6281111111111) - INV-202509-0015
  ✅ Sent: 2

=== Summary ===
✅ Total Sent: 7
Payment reminders completed!
```

## 📊 Database Records

### 1. PaymentReminder Table
Setiap reminder dicatat:
```sql
SELECT 
    pr.id,
    p.invoice_number,
    c.name as customer,
    prr.name as rule_name,
    pr.reminder_type,
    pr.status,
    pr.sent_at
FROM payment_reminders pr
JOIN payments p ON pr.payment_id = p.id
JOIN customers c ON p.customer_id = c.id
JOIN payment_reminder_rules prr ON pr.reminder_rule_id = prr.id
ORDER BY pr.created_at DESC;
```

### 2. WhatsAppMessage Table
Pesan WhatsApp dicatat dengan media info:
```sql
SELECT 
    wm.id,
    c.name as customer,
    p.invoice_number,
    wm.message_type,
    wm.media_type,
    wm.media_path,
    wm.status,
    wm.sent_at
FROM whats_app_messages wm
JOIN customers c ON wm.customer_id = c.id
JOIN payments p ON wm.payment_id = p.id
WHERE wm.message_type LIKE 'billing.reminder%' 
   OR wm.message_type = 'billing.overdue'
ORDER BY wm.sent_at DESC;
```

Expected result:
```
| id | customer   | invoice_number | message_type         | media_type | status | sent_at             |
|----|-----------|----------------|---------------------|-----------|--------|---------------------|
| 10 | John Doe  | INV-202510-001 | billing.reminder_h3 | document  | sent   | 2025-10-13 12:00:00 |
| 11 | Jane Doe  | INV-202509-099 | billing.overdue     | document  | sent   | 2025-10-13 12:00:05 |
```

## 🎯 Pengaturan Reminder Rules

Untuk mengatur reminder rules:

1. **Login ke aplikasi**
2. **Masuk ke menu WhatsApp → Pengaturan Reminder**
3. **Buat atau edit rule**
4. **Pilih template WhatsApp** untuk setiap rule
5. **Aktifkan rule**

### Contoh Rule Configuration

| Rule Name | Days Before Due | Template | Active |
|-----------|----------------|----------|--------|
| Reminder H-7 | -7 | Reminder 7 Hari | ✅ |
| Reminder H-3 | -3 | Reminder 3 Hari | ✅ |
| Reminder H-1 | -1 | Reminder 1 Hari | ✅ |
| Reminder Jatuh Tempo | 0 | Reminder Jatuh Tempo | ✅ |
| Overdue H+1 | 1 | Tagihan Terlambat | ✅ |
| Overdue H+3 | 3 | Tagihan Terlambat | ✅ |
| Overdue H+7 | 7 | Tagihan Sangat Terlambat | ✅ |

## ✅ Verifikasi

### 1. Cek Log
```bash
tail -f storage/logs/laravel.log | grep -i "reminder\|invoice pdf"
```

Expected:
```
[2025-10-13 12:00:00] local.INFO: Invoice PDF generated {"invoice":"INV-202510-0001","type":"reminder_h3","status":"pending","path":"..."}
[2025-10-13 12:00:01] local.INFO: Billing notification sent {"customer":"John Doe","invoice":"INV-202510-0001","with_pdf":true,"success":true}
```

### 2. Cek WhatsApp Customer
Customer akan menerima:
- 📧 **Pesan text** dengan informasi reminder
- 📄 **PDF invoice** sebagai attachment

### 3. Cek Riwayat Pesan di Admin
Menu: **WhatsApp → Riwayat Pesan**

Filter by message type:
- `billing.reminder` (reminder umum)
- `billing.reminder_h3` (H-3)
- `billing.reminder_h1` (H-1)
- `billing.reminder_h0` (jatuh tempo)
- `billing.overdue` (terlambat)

Semua harus menampilkan:
- Status: **sent** (hijau)
- Media Type: **document**
- Media Path: `invoices/INV-XXXXXX-XXXX.pdf`

## 🐛 Troubleshooting

### PDF Tidak Terkirim

**Kemungkinan Masalah:**

1. **API Token WhatsApp kosong**
   - Solusi: Isi API Token di menu WhatsApp → Pengaturan WhatsApp

2. **Template tidak dikonfigurasi**
   - Solusi: Pastikan setiap reminder rule punya template yang dipilih

3. **Error generate PDF**
   - Cek log: `storage/logs/laravel.log`
   - Pastikan DomPDF package ter-install
   - Pastikan direktori `storage/app/public/invoices/` writable

4. **File PDF tidak ditemukan**
   - Pastikan direktori `storage/app/public/invoices/` ada
   - Create link: `php artisan storage:link`
   - Set permission: `chmod -R 775 storage/`

### Command Gagal

```bash
# Test dry-run dulu
php artisan whatsapp:payment-reminders --dry-run

# Cek error detail di log
tail -f storage/logs/laravel.log
```

## 💡 Tips

1. **Test dengan Dry Run**
   ```bash
   php artisan whatsapp:payment-reminders --dry-run
   ```
   Ini akan menampilkan preview tanpa benar-benar mengirim pesan.

2. **Monitor Pengiriman**
   - Cek menu WhatsApp → Riwayat Pesan secara berkala
   - Filter by status "failed" untuk melihat yang gagal
   - Gunakan action "Kirim Ulang" untuk retry

3. **Optimasi Template**
   - Buat template yang jelas dan informatif
   - Sertakan info periode, jumlah tagihan, dan due date
   - Tambahkan call-to-action yang jelas

4. **Backup PDF**
   - PDF otomatis tersimpan di `storage/app/public/invoices/`
   - Backup folder ini secara berkala
   - Set retention policy untuk menghapus PDF lama

## 📚 Referensi

- [Payment Reminder System Documentation](./payment_reminder_system.md)
- [WhatsApp Template System](./modular_whatsapp_template_feature.md)
- [WhatsApp PDF Invoice Feature](./whatsapp_pdf_invoice_feature.md)
- [Troubleshooting WhatsApp 401 Error](./troubleshooting_whatsapp_401_error.md)

## 📊 Summary of PDF Invoice Sending

Ringkasan: **Kapan PDF Invoice Dikirim?**

| Skenario | PDF Invoice | Keterangan |
|----------|------------|-----------|
| Generate tagihan baru (bulanan) | ✅ Ya | `type: 'new'` |
| Proses bayar tagihan | ✅ Ya | `type: 'paid'` |
| Reminder sebelum jatuh tempo | ✅ Ya | `type: 'reminder_h3', 'reminder_h1'` |
| Reminder di hari jatuh tempo | ✅ Ya | `type: 'reminder_h0'` |
| Reminder overdue (terlambat) | ✅ Ya | `type: 'overdue'` |
| Broadcast manual | ❌ Tidak | User bisa attach file manual |

---

**Terakhir diperbarui:** 2025-10-13  
**Status:** Implemented ✅  
**Version:** 2.0

