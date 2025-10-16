# Sistem Payment Reminder WhatsApp

## Overview
Sistem reminder otomatis untuk mengingatkan customer tentang tagihan yang akan jatuh tempo atau sudah terlambat melalui WhatsApp. Sistem ini terintegrasi penuh dengan template modular dan tracking yang lengkap.

## Fitur Utama

### ✅ 4 Jenis Reminder:
1. **H-3 (3 Hari Sebelum Jatuh Tempo)**
   - Reminder pertama, warning lembut
   - Template: `TYPE_BILLING_REMINDER_1`

2. **H-1 (1 Hari Sebelum Jatuh Tempo)**
   - Reminder lebih urgent
   - Template: `TYPE_BILLING_REMINDER_2`

3. **H+0 (Pada Hari Jatuh Tempo)**
   - Reminder final sebelum overdue
   - Template: `TYPE_BILLING_REMINDER_3`

4. **Overdue (Tagihan Terlambat)**
   - Untuk tagihan yang sudah lewat jatuh tempo
   - Template: `TYPE_BILLING_OVERDUE`

### ✅ Smart Tracking:
- Tidak ada duplicate reminder
- Track status setiap reminder (pending, sent, failed)
- Link ke WhatsApp message yang terkirim

### ✅ Auto-Scheduling:
- Reminder otomatis dijadwalkan saat payment dibuat
- Berjalan 3x sehari (09:00, 14:00, 19:00)

### ✅ Flexible Templates:
- Terintegrasi dengan sistem template modular
- Bisa ganti template via Pengaturan Template
- Fallback system untuk keamanan

## Database Schema

### Table: `payment_reminders`
```sql
id                    bigint (PK)
payment_id            bigint (FK -> payments)
whatsapp_message_id   bigint (nullable, soft reference)
reminder_type         enum('h_minus_3', 'h_minus_1', 'h_zero', 'overdue')
reminder_date         date
status                enum('pending', 'sent', 'failed')
sent_at               timestamp (nullable)
error_message         text (nullable)
created_at            timestamp
updated_at            timestamp
```

### Relationships:
- `payment_reminders.payment_id` → `payments.id` (cascade on delete)
- `payment_reminders.whatsapp_message_id` → `whatsapp_messages.id` (soft reference)

## Cara Kerja Sistem

### 1. Auto-Scheduling (Background)
Ketika command `whatsapp:payment-reminders` dijalankan:

```
1. Cari semua payment dengan status = 'pending'
2. Cek apakah payment sudah punya reminders
3. Jika belum, buat 3 reminders:
   - H-3: reminder_date = due_date - 3 days
   - H-1: reminder_date = due_date - 1 day
   - H+0: reminder_date = due_date
```

### 2. Sending Reminders
Setiap jam yang dijadwalkan (09:00, 14:00, 19:00):

```
1. Cari reminders dengan:
   - reminder_date <= today
   - status = 'pending'
   - payment.status = 'pending'

2. Untuk setiap reminder:
   - Get template dari settings atau fallback
   - Format message dengan variabel
   - Send via WhatsApp
   - Update status menjadi 'sent'
   - Link ke whatsapp_message_id
```

### 3. Overdue Handling
Untuk overdue, tidak di-schedule dulu:

```
1. Setiap run, cari payments dengan:
   - status = 'pending'
   - due_date < today

2. Cek apakah sudah ada overdue reminder hari ini
3. Jika belum, buat dan kirim
```

## Command Usage

### Basic Usage
```bash
# Kirim semua reminder yang due hari ini
php artisan whatsapp:payment-reminders

# Dry run (preview tanpa kirim)
php artisan whatsapp:payment-reminders --dry-run

# Kirim hanya reminder H-3
php artisan whatsapp:payment-reminders --type=h-3

# Kirim hanya reminder H-1
php artisan whatsapp:payment-reminders --type=h-1

# Kirim hanya reminder jatuh tempo
php artisan whatsapp:payment-reminders --type=h-0

# Kirim hanya overdue
php artisan whatsapp:payment-reminders --type=overdue
```

### Command Output
```
=== Payment Reminder System ===
Date: 13 Oktober 2025 10:30:15

📅 Scheduling reminders for pending payments...
  → Scheduled reminders for 25 payments

Processing: Reminder H-3 (3 days before due date)
────────────────────────────────────────────────────────────────
  Found 5 reminders to send
  ✅ Sent to: John Doe - Invoice: INV-202510-0001
  ✅ Sent to: Jane Smith - Invoice: INV-202510-0002
  ...

Processing: Reminder H-1 (1 day before due date)
────────────────────────────────────────────────────────────────
  Found 3 reminders to send
  ✅ Sent to: Mike Johnson - Invoice: INV-202510-0015
  ...

=== Summary ===
✅ Total Sent: 12
Payment reminders completed!
```

## Schedule Configuration

### Default Schedule (di `app/Console/Kernel.php`):
```php
// Run 3x sehari
$schedule->command('whatsapp:payment-reminders')
    ->dailyAt('09:00')  // Pagi
    ->withoutOverlapping();
    
$schedule->command('whatsapp:payment-reminders')
    ->dailyAt('14:00')  // Siang
    ->withoutOverlapping();
    
$schedule->command('whatsapp:payment-reminders')
    ->dailyAt('19:00')  // Malam
    ->withoutOverlapping();
```

### Customize Schedule:
Anda bisa ubah jam atau tambah schedule sesuai kebutuhan:

```php
// Setiap jam dari 09:00 - 21:00
$schedule->command('whatsapp:payment-reminders')
    ->hourlyAt(0)
    ->between('9:00', '21:00')
    ->withoutOverlapping();

// Setiap 4 jam
$schedule->command('whatsapp:payment-reminders')
    ->cron('0 */4 * * *')
    ->withoutOverlapping();
```

## Template Integration

### Template Variables
Setiap reminder type punya variabel yang tersedia:

**H-3, H-1, H+0:**
- `{customer_name}` - Nama pelanggan
- `{invoice_number}` - Nomor invoice
- `{amount}` - Jumlah tagihan
- `{due_date}` - Tanggal jatuh tempo
- `{days_left}` - Sisa hari (untuk H-3, H-1)
- `{period}` - Periode tagihan

**Overdue:**
- `{customer_name}` - Nama pelanggan
- `{invoice_number}` - Nomor invoice
- `{amount}` - Jumlah tagihan
- `{due_date}` - Tanggal jatuh tempo
- `{days_overdue}` - Jumlah hari terlambat

### Konfigurasi Template
Via **WhatsApp** → **Pengaturan Template**:
1. **Pengingat Tagihan (H-3)** → Pilih template untuk H-3
2. **Pengingat Tagihan (H-1)** → Pilih template untuk H-1
3. **Pengingat Tagihan (Jatuh Tempo)** → Pilih template untuk H+0
4. **Tagihan Terlambat** → Pilih template untuk overdue

## Models & Relationships

### PaymentReminder Model
```php
// Get reminder with relations
$reminder = PaymentReminder::with('payment.customer')->find(1);

// Scopes
PaymentReminder::dueToday()->get();
PaymentReminder::ofType('h_minus_3')->get();

// Methods
$reminder->markAsSent($whatsappMessageId);
$reminder->markAsFailed('Error message');

// Attributes
$reminder->reminder_type_label; // "Reminder H-3"
```

### Payment Model
```php
// Get all reminders for a payment
$payment->reminders;

// Check if payment has reminder
$payment->reminders()->where('reminder_type', 'h_minus_3')->exists();
```

## Monitoring & Logs

### Database Query
```sql
-- Count reminders by status
SELECT status, COUNT(*) as count
FROM payment_reminders
GROUP BY status;

-- Reminders yang gagal hari ini
SELECT pr.*, p.invoice_number, c.name
FROM payment_reminders pr
JOIN payments p ON pr.payment_id = p.id
JOIN customers c ON p.customer_id = c.id
WHERE pr.status = 'failed'
AND DATE(pr.created_at) = CURDATE();

-- Success rate per reminder type
SELECT 
    reminder_type,
    COUNT(*) as total,
    SUM(CASE WHEN status = 'sent' THEN 1 ELSE 0 END) as sent,
    ROUND(SUM(CASE WHEN status = 'sent' THEN 1 ELSE 0 END) / COUNT(*) * 100, 2) as success_rate
FROM payment_reminders
GROUP BY reminder_type;
```

### Laravel Log
```php
// Log location
storage/logs/laravel.log

// Search for reminder logs
grep "Payment reminder" storage/logs/laravel.log

// View failed reminders
grep "Failed to send payment reminder" storage/logs/laravel.log
```

## Testing

### Manual Test
```bash
# Dry run untuk lihat apa yang akan dikirim
php artisan whatsapp:payment-reminders --dry-run

# Output:
# 🔍 DRY RUN MODE - No messages will be sent
# 📤 Would send to: John Doe (081234567890) - Invoice: INV-202510-0001
```

### Create Test Data
```php
// Create a payment with due date tomorrow
$payment = Payment::factory()->create([
    'due_date' => now()->addDay(),
    'status' => 'pending',
]);

// Run command
php artisan whatsapp:payment-reminders --dry-run

// Should see H-1 reminder scheduled
```

## Troubleshooting

### Reminder tidak terkirim
**Check:**
1. Payment status = 'pending'?
2. Reminder status = 'pending'?
3. reminder_date sudah lewat atau hari ini?
4. Customer punya nomor WhatsApp?
5. Template sudah dikonfigurasi?

**Solution:**
```bash
# Check database
SELECT * FROM payment_reminders WHERE status = 'failed';

# Check logs
tail -f storage/logs/laravel.log

# Re-send failed reminders
UPDATE payment_reminders SET status = 'pending' WHERE status = 'failed';
php artisan whatsapp:payment-reminders
```

### Duplicate reminders
**Check:**
```sql
SELECT payment_id, reminder_type, COUNT(*)
FROM payment_reminders
GROUP BY payment_id, reminder_type
HAVING COUNT(*) > 1;
```

**Solution:**
Sistem seharusnya tidak create duplicate karena menggunakan `firstOrCreate()`.

### Template tidak ditemukan
**Check:**
1. Template dengan type yang sesuai ada dan aktif?
2. Template sudah dikonfigurasi di Pengaturan Template?

**Solution:**
```bash
# Check templates
SELECT * FROM whatsapp_templates WHERE template_type LIKE 'billing_reminder%';

# Check settings
SELECT * FROM settings WHERE key LIKE 'whatsapp_template_billing%';
```

## Best Practices

### 1. Schedule Timing
- **Pagi (09:00)**: Reminder fresh di pagi hari
- **Siang (14:00)**: Coverage untuk yang lewat pagi
- **Malam (19:00)**: Final reminder hari itu

### 2. Template Content
**H-3:**
- Tone: Informative, friendly
- Action: Memberi tahu tagihan akan jatuh tempo

**H-1:**
- Tone: Lebih urgent, tapi masih sopan
- Action: Mengingatkan untuk segera bayar

**H+0:**
- Tone: Urgent
- Action: Bayar hari ini untuk avoid denda

**Overdue:**
- Tone: Formal, serious
- Action: Warning tentang konsekuensi

### 3. Monitoring
- Check failed reminders daily
- Monitor success rate per type
- Adjust schedule jika perlu

### 4. Customer Experience
- Jangan spam: max 1 reminder per type per payment
- Gunakan tone yang appropriate per type
- Provide clear payment instructions

## API / Programmatic Usage

### Send Reminder Manually
```php
use App\Models\Payment;
use App\Services\WhatsAppService;

$payment = Payment::find(1);
$whatsapp = new WhatsAppService();

// Send H-3 reminder
$whatsapp->sendBillingNotification($payment, 'reminder_h3');

// Send H-1 reminder
$whatsapp->sendBillingNotification($payment, 'reminder_h1');

// Send H+0 reminder
$whatsapp->sendBillingNotification($payment, 'reminder_h0');

// Send overdue
$whatsapp->sendBillingNotification($payment, 'overdue');
```

### Check Reminder Status
```php
$payment = Payment::with('reminders')->find(1);

foreach ($payment->reminders as $reminder) {
    echo "{$reminder->reminder_type_label}: {$reminder->status}\n";
}
```

### Schedule Custom Reminder
```php
use App\Models\PaymentReminder;

$reminder = PaymentReminder::create([
    'payment_id' => $payment->id,
    'reminder_type' => PaymentReminder::TYPE_H_MINUS_3,
    'reminder_date' => now()->addDays(2),
    'status' => 'pending',
]);
```

## Performance Considerations

### Database Indexes
Sudah ada indexes untuk:
- `payment_id, reminder_type` (composite)
- `reminder_date, status` (composite)
- `status`

### Rate Limiting
Command menggunakan delay 0.5 detik antar message untuk avoid WhatsApp rate limit:
```php
usleep(500000); // 0.5 second delay
```

### Batch Processing
Untuk skala besar, bisa gunakan queue:
```php
// Future enhancement
foreach ($reminders as $reminder) {
    SendPaymentReminderJob::dispatch($reminder)->onQueue('reminders');
}
```

## Related Documentation
- [Modular WhatsApp Template](./modular_whatsapp_template_feature.md)
- [Template Assignment](./whatsapp_template_assignment_feature.md)
- [WhatsApp Integration](./whatsapp_integration.md)

---

**Dibuat:** 13 Oktober 2025
**Update Terakhir:** 13 Oktober 2025

