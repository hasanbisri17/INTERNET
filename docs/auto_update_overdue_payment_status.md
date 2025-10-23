# Auto Update Overdue Payment Status

## 📌 Overview
Fitur ini secara otomatis mengubah status payment dari **"pending"** menjadi **"overdue"** setelah melewati tanggal jatuh tempo (due date).

## 🎯 Purpose
- ✅ **Status payment lebih akurat** - Bedakan antara pending (belum jatuh tempo) vs overdue (sudah lewat jatuh tempo)
- ✅ **Reporting lebih jelas** - Filter dan lihat payment overdue dengan mudah
- ✅ **Automation** - Tidak perlu manual update status
- ✅ **Notification** - Admin mendapat notifikasi tentang payment overdue

## 🔄 Flow Auto Update

### **Daily Process:**
```
Scheduler (Daily 00:01 AM) → php artisan payments:update-overdue
↓
Check payment dengan status = "pending" dan due_date < today
↓
Update status dari "pending" → "overdue"
↓
Send WhatsApp notification ke customer (Status Overdue)
↓
Log activity untuk setiap payment
↓
Send notification ke admin users
```

## 📊 Status Payment

| Status | Keterangan | Warna Badge |
|--------|------------|-------------|
| **pending** | Belum bayar, belum jatuh tempo | 🟡 Warning |
| **overdue** | Belum bayar, sudah lewat jatuh tempo | 🔴 Danger |
| **paid** | Sudah lunas | 🟢 Success |
| **failed** | Pembayaran gagal | 🔴 Danger |
| **expired** | Kedaluwarsa | 🔴 Danger |
| **refunded** | Dikembalikan | 🟢 Success |
| **canceled** | Dibatalkan | ⚪ Gray |

## 🗂️ Files

### 1. Command
- **File:** `app/Console/Commands/UpdateOverduePayments.php`
- **Command:** `php artisan payments:update-overdue`

### 2. Scheduler
- **File:** `app/Console/Kernel.php`
- **Schedule:** Daily at 00:01 AM

## 🎮 Command Usage

### 1. **Dry Run (Preview Only)**
Preview payment yang akan diupdate tanpa benar-benar mengubah status:

```bash
php artisan payments:update-overdue --dry-run
```

**Output Example:**
```
=== Update Overdue Payments ===
Date: 23 October 2025 09:36:21
🔍 DRY RUN MODE - No updates will be made

Found 5 overdue payment(s):

+--------------------+------------------+----------------+-------------+---------------+
| Invoice            | Customer         | Amount         | Due Date    | Days Overdue  |
+--------------------+------------------+----------------+-------------+---------------+
| INV-202510-0001    | John Doe         | Rp 300.000     | 20 Oct 2025 | 3 hari        |
| INV-202510-0002    | Jane Smith       | Rp 250.000     | 21 Oct 2025 | 2 hari        |
| INV-202510-0003    | Mike Johnson     | Rp 400.000     | 22 Oct 2025 | 1 hari        |
+--------------------+------------------+----------------+-------------+---------------+

🔍 DRY RUN: 3 payment(s) would be updated to 'overdue' status
```

### 2. **Actual Run (Update Status)**
Jalankan command untuk benar-benar update status:

```bash
php artisan payments:update-overdue
```

**Output Example:**
```
=== Update Overdue Payments ===
Date: 23 October 2025 09:36:21

Found 3 overdue payment(s):

+--------------------+------------------+----------------+-------------+---------------+
| Invoice            | Customer         | Amount         | Due Date    | Days Overdue  |
+--------------------+------------------+----------------+-------------+---------------+
| INV-202510-0001    | John Doe         | Rp 300.000     | 20 Oct 2025 | 3 hari        |
| INV-202510-0002    | Jane Smith       | Rp 250.000     | 21 Oct 2025 | 2 hari        |
| INV-202510-0003    | Mike Johnson     | Rp 400.000     | 22 Oct 2025 | 1 hari        |
+--------------------+------------------+----------------+-------------+---------------+

Updating payment statuses...
  ✅ INV-202510-0001 - John Doe
  ✅ INV-202510-0002 - Jane Smith
  ✅ INV-202510-0003 - Mike Johnson

=== Summary ===
✅ Updated: 3
📧 Notification sent to admin users
```

### 3. **Manual Run (Specific Time)**
Jika ingin run manual di waktu tertentu:

```bash
php artisan payments:update-overdue
```

## 📅 Schedule Configuration

### Default Schedule (app/Console/Kernel.php):
```php
// Update payment status to overdue - Run daily at 00:01 AM
$schedule->command('payments:update-overdue')
    ->dailyAt('00:01')
    ->withoutOverlapping();
```

### Customize Schedule:
Jika ingin ubah waktu atau frekuensi:

```php
// Run multiple times per day
$schedule->command('payments:update-overdue')
    ->dailyAt('00:01')  // Midnight
    ->withoutOverlapping();
    
$schedule->command('payments:update-overdue')
    ->dailyAt('12:00')  // Noon
    ->withoutOverlapping();

// OR run every hour
$schedule->command('payments:update-overdue')
    ->hourly()
    ->withoutOverlapping();
```

## 📱 WhatsApp Notification ke Customer

Setiap kali status payment berubah ke overdue, customer akan menerima **WhatsApp notification**:

**Template Default:**
```
Yth. {Customer Name},

⚠️ Tagihan Anda telah melewati jatuh tempo.

📅 Due Date: {Due Date}
💰 Total Tagihan: Rp {Amount}
📆 Terlambat: {Days Overdue} hari

Layanan akan dinonaktifkan jika pembayaran belum diterima hari ini.

Silakan segera melakukan pembayaran untuk menghindari pemutusan layanan.

Terima kasih.
```

**Konfigurasi Template:**
1. Buka menu **WhatsApp → Template Pesan**
2. Buat/edit template dengan type **"Status Payment Overdue"**
3. Buka menu **Pengaturan Sistem → Tab "Template WhatsApp"**
4. Pilih template yang ingin digunakan untuk "Status Payment Overdue"
5. Simpan

**Variables Available:**
- `{customer_name}` - Nama customer
- `{invoice_number}` - Nomor invoice
- `{amount}` - Jumlah tagihan (format: Rp XXX)
- `{due_date}` - Tanggal jatuh tempo (format: d M Y)
- `{days_overdue}` - Jumlah hari terlambat

## 🔔 Admin Notification

Setiap kali command berhasil update payment ke overdue, admin akan menerima **database notification** di panel Filament:

**Notification Content:**
- **Title:** 🔴 Status Payment Diupdate ke Overdue
- **Body:** "X payment telah diupdate statusnya menjadi 'Terlambat' (Total: Rp XXX)"
- **Action Button:** "Lihat Payment Overdue" → Langsung filter payment dengan status overdue

## 📝 Activity Log

Setiap update status akan dicatat di activity log:

**Log Details:**
- **Event:** `payment_status_update`
- **Properties:**
  - `old_status`: "pending"
  - `new_status`: "overdue"
  - `invoice_number`: Invoice number
  - `customer`: Customer name
  - `days_overdue`: Jumlah hari terlambat
- **Description:** "Payment INV-XXX status updated to overdue"

**View Activity Log:**
```
Admin Panel → Activity Log → Filter by event "payment_status_update"
```

## 🔍 Filter Payment Overdue

Setelah status diupdate, Anda bisa filter payment overdue di admin panel:

**Steps:**
1. Buka menu **Payments**
2. Klik **Filter** icon
3. Pilih **Status** → **Terlambat (overdue)**
4. Lihat semua payment yang overdue

## 🎨 Badge Color

Payment dengan status overdue akan ditampilkan dengan **badge merah (danger)**:

- **pending** → 🟡 Yellow badge
- **overdue** → 🔴 Red badge
- **paid** → 🟢 Green badge

## ⚙️ Technical Details

### Query untuk Get Overdue Payments:
```php
Payment::where('status', 'pending')
    ->whereDate('due_date', '<', Carbon::now()->startOfDay())
    ->with(['customer', 'internetPackage'])
    ->get();
```

### Update Process:
```php
$payment->update([
    'status' => 'overdue',
]);
```

### Activity Logging:
```php
activity('payment_status_update')
    ->performedOn($payment)
    ->withProperties([
        'old_status' => 'pending',
        'new_status' => 'overdue',
        'invoice_number' => $payment->invoice_number,
        'customer' => $payment->customer?->name,
        'days_overdue' => Carbon::parse($payment->due_date)->diffInDays(now()),
    ])
    ->log("Payment {$payment->invoice_number} status updated to overdue");
```

## 🔗 Integration dengan Features Lain

### 1. **Payment Reminder System**
- Command `whatsapp:payment-reminders` masih berjalan independen
- Reminder H-3, H-1, H+0 tetap jalan untuk payment **pending**
- Reminder **overdue** jalan untuk payment dengan status **overdue**

### 2. **Auto Suspend via IP Binding**
- Berjalan tanggal 26 setiap bulan
- Suspend customer yang `due_date <= tanggal 25`
- **Tidak peduli status** pending atau overdue, yang penting due date sudah lewat

### 3. **Check Payment Due Dates**
- Command `payments:check-due-dates` tetap jalan jam 08:00
- Send notification tentang upcoming dan overdue payments
- Independent dari auto-update status

## 📊 Timeline Example

### **Skenario Payment:**

| Date | Event | Status | Notes |
|------|-------|--------|-------|
| **1 Oct** | Generate monthly bill | `pending` | Invoice INV-202510-0001 created |
| **22 Oct** | H-3 Reminder sent | `pending` | WhatsApp reminder H-3 |
| **24 Oct** | H-1 Reminder sent | `pending` | WhatsApp reminder H-1 |
| **25 Oct** | H+0 Reminder sent | `pending` | WhatsApp reminder jatuh tempo |
| **26 Oct 00:01** | **Auto update status** | `overdue` | ✅ Status berubah otomatis |
| **26 Oct 00:01** | Auto suspend customer | `overdue` | Customer suspended via IP Binding |
| **27 Oct** | Overdue reminder sent | `overdue` | WhatsApp reminder overdue |
| **28 Oct** | Customer bayar | `paid` | Status updated, customer unsuspended |

## 🚨 Troubleshooting

### Issue: Command tidak jalan otomatis

**Check:**
1. Cron job sudah di-setup?
   ```bash
   * * * * * cd /path-to-project && php artisan schedule:run >> /dev/null 2>&1
   ```

2. Command terdaftar di Kernel?
   ```bash
   php artisan schedule:list
   ```

**Solution:**
- Setup cron job di server
- Atau jalankan manual via command

### Issue: Payment tidak terupdate

**Check:**
1. Payment status = 'pending'?
2. Due date sudah lewat?
3. Check logs:
   ```bash
   tail -f storage/logs/laravel.log
   ```

**Solution:**
- Run command manual dengan `--dry-run` untuk debug
- Check database directly

### Issue: No notification received

**Check:**
1. User is_admin = true?
2. Database notification system working?

**Solution:**
- Check `notifications` table
- Check Filament notification bell icon

## 🎯 Best Practices

1. **Run Dry Run First:**
   - Sebelum production, test dengan `--dry-run`
   - Pastikan data yang diupdate sudah benar

2. **Monitor Logs:**
   - Check activity logs regularly
   - Monitor failed updates

3. **Schedule Time:**
   - Jalankan di jam 00:01 (setelah midnight)
   - Sebelum payment reminder (jam 08:00-09:00)

4. **Testing:**
   - Test dengan payment dummy
   - Verify status berubah dengan benar

## 📖 Related Documentation

- [Payment Reminder System](./payment_reminder_system.md)
- [Suspend via IP Binding](./suspend_via_ip_binding_feature.md)
- [Activity Logging System](./activity_logging_system.md)

---

**Created:** 23 Oktober 2025  
**Version:** 1.0  
**Status:** ✅ Production Ready

