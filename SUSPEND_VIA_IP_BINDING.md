# Suspend/Unsuspend via IP Binding - Quick Start

## ✨ Fitur Baru!

Sistem sekarang bisa **suspend dan unsuspend** layanan internet customer secara otomatis melalui **MikroTik IP Binding**, tanpa perlu n8n!

## 🚀 How It Works

### Suspend (Auto - Tanggal 26)
```
Customer belum bayar → IP Binding type: bypassed → regular → Customer harus login hotspot
```

### Unsuspend (Auto - Saat Bayar)
```
Customer bayar → IP Binding type: regular → bypassed → Customer langsung internet
```

## 📅 Schedule

**Auto Suspend:** Setiap tanggal **26** jam **00:01 AM**
- Cek customer yang `due_date <= tanggal 25`
- Suspend otomatis via IP Binding
- Kirim WhatsApp notification

**Auto Unsuspend:** Saat customer **bayar** (payment status = `paid`)
- Unsuspend otomatis via IP Binding  
- Kirim WhatsApp notification

## 🎯 Commands

```bash
# Auto suspend all (scheduled via cron)
php artisan suspend:auto-ip-binding

# Test mode (dry-run)
php artisan suspend:auto-ip-binding --dry-run

# Suspend specific customer
php artisan suspend:auto-ip-binding --customer=1
```

## 📋 Syarat

Customer akan di-suspend jika:
- ✅ Status = `active`
- ✅ Belum isolated (`is_isolated = false`)
- ✅ `due_date <= tanggal 25` (jika dijalankan tanggal 26)
- ✅ Punya IP Binding dengan type = `bypassed`

## 🔄 Full Flow

### Flow Suspend
1. **Tanggal 26 00:01** → Cron trigger command
2. Get customers yang `due_date <= 25`
3. Untuk setiap customer:
   - Ambil IP Binding dengan type `bypassed`
   - Ubah type → `regular`
   - Auto-sync ke MikroTik (via observer)
   - Update customer status → `suspended`
   - Kirim WhatsApp notification
   - Log activity

### Flow Unsuspend
1. **Customer bayar** → Payment status = `paid`
2. PaymentObserver detect payment paid
3. Check: customer suspended?
4. Jika ya:
   - Ambil IP Binding dengan type `regular`
   - Ubah type → `bypassed`
   - Auto-sync ke MikroTik (via observer)
   - Update customer status → `active`
   - Kirim WhatsApp notification
   - Log activity

## 📊 Monitoring

### Check Scheduled Command
```bash
php artisan schedule:list
```

Output:
```
0 0 26 * * * suspend:auto-ip-binding ............ Next Due: 26 Nov 2025 00:00:01
```

### Check Logs
```bash
# Windows PowerShell
Get-Content storage/logs/laravel.log -Tail 50 | Select-String "suspend"

# Linux/Mac
tail -f storage/logs/laravel.log | grep suspend
```

### Test Dry Run
```bash
php artisan suspend:auto-ip-binding --dry-run
```

## ⚙️ Configuration

### Cron Schedule
File: `app/Console/Kernel.php`

```php
$schedule->command('suspend:auto-ip-binding')
    ->monthlyOn(26, '00:01')  // Tanggal 26 jam 00:01 AM
    ->withoutOverlapping();
```

### Payment Observer
File: `app/Observers/PaymentObserver.php`

Auto-unsuspend saat payment status = `paid` atau `confirmed`

## 💡 Tips

### 1. Test Sebelum Production
```bash
# Test dengan dry-run
php artisan suspend:auto-ip-binding --dry-run

# Test 1 customer
php artisan suspend:auto-ip-binding --customer=1
```

### 2. Setup IP Binding dengan Benar
- Type default: `bypassed` (untuk customer normal)
- Comment: Isi dengan keterangan jelas
- Sync dari MikroTik terlebih dahulu

### 3. Monitor Setelah Tanggal 26
```bash
# Check log
tail -f storage/logs/laravel.log

# Check activity log di admin panel
Menu → Log Aktivitas
```

## 🆚 Perbandingan Method

| Method | Speed | Complexity | Dependency |
|--------|-------|------------|------------|
| **IP Binding** | ⚡ Fast | ✅ Simple | MikroTik only |
| **PPP Profile** | ⚡ Fast | ⚠️ Medium | MikroTik only |
| **n8n** | 🐢 Slower | ❌ Complex | n8n + MikroTik |

## 📱 WhatsApp Notifications

### Suspend Message
```
Yth. John Doe,

Layanan internet Anda telah dinonaktifkan karena 
pembayaran belum diterima hingga tanggal 25.

📅 Due Date: 20 Oct 2025
💰 Total Tagihan: Rp 350,000

Silakan segera melakukan pembayaran untuk 
mengaktifkan kembali layanan Anda.

Terima kasih.
```

### Unsuspend Message
```
Yth. John Doe,

✅ Layanan internet Anda telah diaktifkan kembali.

Terima kasih atas pembayaran Anda. 
Selamat menikmati layanan internet kami.

Jika ada kendala, silakan hubungi kami.

Terima kasih.
```

## 📚 Documentation

Dokumentasi lengkap: `docs/suspend_via_ip_binding_feature.md`

## 🎉 Ready to Use!

Fitur sudah aktif dan siap digunakan. Cron akan otomatis jalan setiap tanggal 26.

---

**Version:** 1.0.0  
**Last Updated:** 22 Oktober 2025

