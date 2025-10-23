# Suspend/Unsuspend via IP Binding Feature

## Overview

Fitur ini memungkinkan sistem untuk melakukan suspend dan unsuspend layanan internet customer secara otomatis melalui MikroTik IP Binding, tanpa perlu n8n sebagai middleware.

## Konsep Dasar

### Flow Lama (via n8n):
```
Customer belum bayar → Trigger n8n → n8n suspend internet → Kirim notifikasi
Customer bayar → Trigger n8n → n8n unsuspend internet → Kirim notifikasi
```

### Flow Baru (via IP Binding):
```
Customer belum bayar → Ubah IP Binding type bypassed→regular → Kirim notifikasi
Customer bayar → Ubah IP Binding type regular→bypassed → Kirim notifikasi
```

## Cara Kerja

### Suspend Process
1. **Tanggal 26** setiap bulan, sistem check customer yang `due_date <= tanggal 25`
2. Sistem ambil semua **IP Binding** milik customer dengan `type = 'bypassed'`
3. **Ubah type** dari `bypassed` → `regular`
4. IP Binding akan **auto-sync ke MikroTik** (via observer)
5. Customer **harus login hotspot** untuk akses internet (tidak bisa bypass)
6. Update status customer: `is_isolated = true`, `status = 'suspended'`
7. **Kirim WhatsApp notification** suspend
8. **Log activity** ke database

### Unsuspend Process  
1. Customer **melakukan pembayaran** (status payment = `paid` atau `confirmed`)
2. PaymentObserver detect payment paid
3. Check: apakah customer dalam status `suspended`?
4. Jika ya, ambil semua **IP Binding** milik customer dengan `type = 'regular'`
5. **Ubah type** dari `regular` → `bypassed`
6. IP Binding akan **auto-sync ke MikroTik** (via observer)
7. Customer **bypass hotspot**, langsung akses internet
8. Update status customer: `is_isolated = false`, `status = 'active'`
9. **Kirim WhatsApp notification** unsuspend
10. **Log activity** ke database

## Type IP Binding

| Type | Keterangan | Akses Internet |
|------|------------|----------------|
| **bypassed** | Bypass hotspot authentication | ✅ Langsung internet |
| **regular** | Harus login hotspot | ❌ Harus auth dulu |
| **blocked** | Diblock total | ❌ Tidak bisa akses |

### Untuk Suspend/Unsuspend:
- **Suspend**: `bypassed` → `regular` (customer harus login, tapi login akan gagal karena belum bayar)
- **Unsuspend**: `regular` → `bypassed` (customer langsung bisa internet, no login required)

## Files yang Terlibat

### 1. Service
- `app/Services/SuspendViaIpBindingService.php` - Main service untuk suspend/unsuspend logic

### 2. Command
- `app/Console/Commands/AutoSuspendViaIpBindingCommand.php` - Command untuk auto-suspend via cron

### 3. Observer
- `app/Observers/PaymentObserver.php` - Auto-unsuspend saat payment paid

### 4. Kernel
- `app/Console/Kernel.php` - Register command dan schedule cron

## Command Usage

### 1. Auto Suspend (Scheduled)
Berjalan otomatis setiap tanggal 26 jam 00:01 AM via cron:

```bash
php artisan suspend:auto-ip-binding
```

### 2. Manual Suspend (Specific Customer)
```bash
php artisan suspend:auto-ip-binding --customer=1
```

### 3. Dry Run (Test Mode)
```bash
php artisan suspend:auto-ip-binding --dry-run
```

Output:
```
+----+------------------+-------------+-------------+-------------------+
| ID | Name             | Phone       | Due Date    | IP Bindings Count |
+----+------------------+-------------+-------------+-------------------+
| 1  | John Doe         | 08123456789 | 20 Oct 2025 | 2                 |
| 2  | Jane Smith       | 08198765432 | 22 Oct 2025 | 1                 |
+----+------------------+-------------+-------------+-------------------+

Total customers to be suspended: 2
```

## Cron Schedule

Di `app/Console/Kernel.php`:

```php
// Auto suspend customers via IP Binding on 26th of each month at 00:01 AM
$schedule->command('suspend:auto-ip-binding')
    ->monthlyOn(26, '00:01')
    ->withoutOverlapping();
```

## WhatsApp Notifications

> **⚡ Update:** WhatsApp notifications sekarang menggunakan **template system yang configurable**!
> 
> Anda bisa membuat dan mengatur template custom di:
> 1. Menu **"WhatsApp → Template Pesan"** (buat/edit template)
> 2. Menu **"Pengaturan Sistem → Tab Template WhatsApp"** (pilih template default)
>
> 📚 Detail lengkap: [Suspend/Unsuspend WhatsApp Notification Guide](./suspend_unsuspend_whatsapp_notification.md)

### Suspend Notification (Default Template)
```
Yth. {customer_name},

⛔ Layanan internet Anda telah dinonaktifkan karena pembayaran belum diterima hingga tanggal 25.

📅 Due Date: {due_date}
💰 Total Tagihan: Rp {amount}

Silakan segera melakukan pembayaran untuk mengaktifkan kembali layanan Anda.

Terima kasih.
```

**Variabel yang tersedia:** `{customer_name}`, `{due_date}`, `{amount}`

### Unsuspend Notification (Default Template)
```
Yth. {customer_name},

✅ Layanan internet Anda telah diaktifkan kembali.

Terima kasih atas pembayaran Anda. Selamat menikmati layanan internet kami.

Jika ada kendala, silakan hubungi kami.

Terima kasih.
```

**Variabel yang tersedia:** `{customer_name}`

> 💡 **Tip:** Template di atas adalah default. Anda bisa membuat template custom dengan gaya bahasa yang lebih sesuai dengan brand Anda!

## Syarat Customer Bisa Di-Suspend

Customer akan di-suspend jika memenuhi semua kriteria:

```php
✅ status = 'active'
✅ is_isolated = false  
✅ due_date <= tanggal 25 (jika dijalankan tanggal 26)
✅ Memiliki minimal 1 IP Binding dengan type = 'bypassed'
```

## Logging & Monitoring

### Log Suspend
```php
Log::info("IP Binding suspended for customer", [
    'customer_id' => 1,
    'customer_name' => 'John Doe',
    'ip_address' => '192.168.4.10',
    'from_type' => 'bypassed',
    'to_type' => 'regular',
]);
```

### Log Unsuspend
```php
Log::info("IP Binding unsuspended for customer", [
    'customer_id' => 1,
    'customer_name' => 'John Doe',
    'ip_address' => '192.168.4.10',
    'from_type' => 'regular',
    'to_type' => 'bypassed',
]);
```

### Activity Log
```php
activity('suspend')
    ->performedOn($customer)
    ->withProperties([
        'ip_bindings_suspended' => 2,
        'method' => 'ip_binding',
        'reason' => 'payment_overdue',
    ])
    ->log("Customer {$customer->name} suspended via IP Binding");
```

## Testing

### 1. Test Auto Suspend (Dry Run)
```bash
php artisan suspend:auto-ip-binding --dry-run
```

### 2. Test Manual Suspend
```bash
# Suspend customer ID 1
php artisan suspend:auto-ip-binding --customer=1

# Check di MikroTik:
# IP => Hotspot => IP Bindings
# Cek apakah type sudah berubah dari bypassed ke regular
```

### 3. Test Auto Unsuspend
```bash
# Ubah status payment ke 'paid'
php artisan tinker

$payment = \App\Models\Payment::find(1);
$payment->update(['status' => 'paid']);

# PaymentObserver akan auto-trigger unsuspend
# Check log: storage/logs/laravel.log
```

### 4. Check Logs
```bash
# Windows PowerShell
Get-Content storage/logs/laravel.log -Tail 50 | Select-String "suspend"

# Linux/Mac
tail -f storage/logs/laravel.log | grep suspend
```

## Troubleshooting

### 1. Customer Tidak Ter-Suspend

**Problem:** Command jalan tapi customer tidak ter-suspend

**Check:**
```bash
php artisan suspend:auto-ip-binding --dry-run
```

**Possible Issues:**
- ❌ Customer tidak punya IP Binding dengan type `bypassed`
- ❌ `due_date` belum lewat
- ❌ Customer sudah dalam status `suspended`

**Solution:**
1. Check IP Bindings customer:
   ```php
   $customer->ipBindings()->where('type', 'bypassed')->get();
   ```
2. Pastikan customer punya IP Binding dengan type `bypassed`
3. Check `due_date` customer

### 2. Auto Unsuspend Tidak Jalan

**Problem:** Customer bayar tapi tidak auto-unsuspend

**Check Log:**
```bash
Get-Content storage/logs/laravel.log -Tail 50 | Select-String "unsuspend"
```

**Possible Issues:**
- ❌ Customer tidak dalam status `suspended`
- ❌ PaymentObserver tidak trigger
- ❌ IP Binding tidak ada atau type bukan `regular`

**Solution:**
1. Pastikan `is_isolated = true` dan `status = 'suspended'`
2. Check PaymentObserver registered di `AppServiceProvider`
3. Check IP Bindings customer ada yang type `regular`

### 3. IP Binding Tidak Sync ke MikroTik

**Problem:** Type berubah di database tapi tidak sync ke MikroTik

**Check:**
- Observer `MikrotikIpBindingObserver` aktif?
- Check di `AppServiceProvider.php`
- Check log error

**Solution:**
```php
// Cek observer registered
// app/Providers/AppServiceProvider.php
\App\Models\MikrotikIpBinding::observe(\App\Observers\MikrotikIpBindingObserver::class);
```

### 4. WhatsApp Notification Tidak Terkirim

**Check:**
- Customer punya nomor phone?
- WhatsApp service configured?
- Check log error

**Test Manual:**
```bash
php artisan whatsapp:test 08123456789 "Test message"
```

## Comment Marker

Service menambahkan marker `[SUSPENDED]` di comment IP Binding untuk tracking:

```
Original comment: "Laptop kantor"
After suspend: "[SUSPENDED] Laptop kantor"
After unsuspend: "Laptop kantor" (marker removed)
```

## Perbandingan dengan Method Lain

| Aspek | Via IP Binding | Via PPP Profile | Via n8n |
|-------|----------------|-----------------|---------|
| **Speed** | ⚡ Fast | ⚡ Fast | 🐢 Slower (webhook) |
| **Complexity** | ✅ Simple | ⚠️ Medium | ❌ Complex |
| **Dependency** | ✅ MikroTik only | ✅ MikroTik only | ❌ Need n8n |
| **Auto-Sync** | ✅ Yes (observer) | ⚠️ Manual | ⚠️ Webhook |
| **Suitable For** | Hotspot | PPPoE | Any |
| **Maintenance** | ✅ Easy | ⚠️ Medium | ❌ Hard |

## Best Practices

### 1. Setup IP Binding dengan Benar
```
Type default untuk customer normal: bypassed
Comment: Include nama device atau keterangan
Example: "Laptop kerja - John Doe"
```

### 2. Monitor Log Secara Berkala
```bash
# Daily check
tail -f storage/logs/laravel.log | grep suspend
```

### 3. Test di Staging Dulu
```bash
# Test dengan dry-run
php artisan suspend:auto-ip-binding --dry-run

# Test 1 customer
php artisan suspend:auto-ip-binding --customer=1
```

### 4. Backup Before Suspend
```bash
# Backup IP Bindings di MikroTik
# System => Backup => Save
```

### 5. Setup Monitoring Alert
- Alert jika suspend/unsuspend failed
- Alert jika WhatsApp notification failed
- Daily report jumlah customer suspended

## Migration dari n8n

Jika saat ini menggunakan n8n dan ingin migrasi:

### Step 1: Disable n8n (Optional)
Di `PaymentObserver.php`, n8n method sudah di-comment:
```php
// 2. Trigger unsuspend webhook via n8n (OLD METHOD - OPTIONAL)
// Comment out atau hapus jika tidak digunakan
```

### Step 2: Enable Cron
Pastikan cron schedule aktif:
```bash
php artisan schedule:list
```

### Step 3: Test Parallel
Jalankan kedua method secara parallel untuk beberapa minggu:
- n8n tetap jalan (backup)
- IP Binding method jalan (primary)
- Monitor hasilnya

### Step 4: Full Migration
Setelah confident, disable n8n completely:
```php
// Delete or keep commented di PaymentObserver.php
```

## FAQ

### Q: Apakah IP Binding harus dari sync MikroTik?
**A:** Ya, sebaiknya IP Binding di-sync dari MikroTik terlebih dahulu agar data konsisten.

### Q: Bagaimana jika customer punya multiple IP?
**A:** Semua IP Binding milik customer akan di-suspend/unsuspend semuanya.

### Q: Apakah bisa suspend sebagian IP saja?
**A:** Bisa, gunakan method manual:
```php
$ipBinding->update(['type' => 'regular']);
```

### Q: Bagaimana dengan customer yang tidak punya IP Binding?
**A:** Customer tersebut tidak akan ter-suspend via method ini. Gunakan method lain (PPP Profile atau n8n).

### Q: Apakah aman untuk production?
**A:** Ya, sudah dilengkapi dengan:
- Error handling
- Logging
- Activity tracking
- WhatsApp notification
- Dry-run mode untuk testing

## Changelog

### Version 1.0.0 (22 Oktober 2025)
- ✅ Initial release
- ✅ Auto suspend via IP Binding
- ✅ Auto unsuspend saat payment paid
- ✅ WhatsApp notification
- ✅ Command dengan dry-run mode
- ✅ Activity logging
- ✅ Cron schedule tanggal 26
- ✅ Integration dengan PaymentObserver

## Support

Untuk pertanyaan atau issue:
1. Check log: `storage/logs/laravel.log`
2. Test dengan dry-run mode
3. Check dokumentasi ini
4. Check MikroTik IP Bindings status

---

**Last Updated:** 22 Oktober 2025  
**Version:** 1.0.0

