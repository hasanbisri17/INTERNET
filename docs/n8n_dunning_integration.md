# Integrasi n8n dengan Sistem Penagihan Otomatis

## 📋 Overview

Sistem "Penagihan Otomatis" (Dunning) sekarang dapat terintegrasi dengan n8n untuk otomatis suspend/unsuspend layanan internet customer melalui Mikrotik atau router lainnya saat customer terlambat bayar.

## 🎯 Tujuan

- **Otomatis suspend** layanan customer yang terlambat bayar melewati batas hari yang ditentukan
- **Otomatis unsuspend** layanan customer saat pembayaran diterima
- **Fleksibel** - bisa custom webhook URL, headers, payload sesuai kebutuhan n8n workflow
- **Termonitor** - semua trigger tercatat di log untuk audit

## 🔧 Cara Kerja Sistem

### Flow Suspend (Customer Overdue)

```
1. Customer terlambat bayar X hari (sesuai konfigurasi)
   ↓
2. Cron job 'dunning:process' berjalan setiap hari jam 10:00
   ↓
3. Sistem cek semua payment overdue
   ↓
4. Jika overdue >= n8n_trigger_after_days
   ↓
5. Kirim HTTP request ke n8n webhook URL dengan data customer
   ↓
6. n8n terima webhook → eksekusi workflow suspend Mikrotik
   ↓
7. Customer internet tersuspend
   ↓
8. Log tersimpan untuk audit
```

### Flow Unsuspend (Customer Bayar)

```
1. Customer melakukan pembayaran
   ↓
2. Staff update status payment jadi 'paid' atau 'confirmed'
   ↓
3. PaymentObserver mendeteksi perubahan status
   ↓
4. Otomatis trigger webhook unsuspend ke n8n
   ↓
5. n8n terima webhook → eksekusi workflow unsuspend Mikrotik
   ↓
6. Customer internet aktif kembali
   ↓
7. Log tersimpan untuk audit
```

## 🛠️ Setup & Konfigurasi

### 1. Setup di n8n

Buat workflow di n8n dengan struktur berikut:

**Webhook Node:**
- Method: POST (atau sesuai preferensi)
- Path: `/webhook/dunning-action` (atau custom)

**Workflow Logic:**
```
1. Webhook Trigger
   ↓
2. IF node: cek action = 'suspend' atau 'unsuspend'
   ↓
3a. Jika suspend:
    → HTTP Request ke Mikrotik API (disable user)
    → Kirim notifikasi ke admin
    
3b. Jika unsuspend:
    → HTTP Request ke Mikrotik API (enable user)
    → Kirim notifikasi ke admin
   ↓
4. Response ke Laravel dengan status sukses/gagal
```

### 2. Setup di Laravel (Menu Penagihan Otomatis)

1. **Buka Filament Admin Panel**
2. **Navigasi ke:** `Konfigurasi Sistem > Penagihan Otomatis`
3. **Klik:** `Create`

#### Field-field yang Perlu Diisi:

**Pengaturan Dasar:**
- **Nama Konfigurasi**: Contoh: "Dunning n8n Auto Suspend"
- **Deskripsi**: Penjelasan singkat konfigurasi ini
- **Aktif**: Toggle ON

**Pengaturan Penagihan:**
- **Masa Tenggang (hari)**: 0 (atau sesuai kebijakan)
- **Kirim Pengingat (hari sebelum jatuh tempo)**: 3

**Pengaturan Penangguhan:**
- **Tangguhkan Otomatis**: Toggle ON/OFF (untuk sistem lama)
- **Tangguhkan Setelah (hari)**: 7
- **Aktifkan Kembali Otomatis Setelah Pembayaran**: Toggle ON

**Saluran Notifikasi:**
- ✅ WhatsApp (optional, bisa dikombinasikan)

**Integrasi n8n:** ⭐ (Bagian Baru)
- **Aktifkan Integrasi n8n**: Toggle ON
- **URL Webhook n8n**: `https://your-n8n-instance.com/webhook/dunning-action`
- **HTTP Method**: POST (default)
- **Custom Headers** (Optional):
  ```json
  {
    "Authorization": "Bearer your-secret-token",
    "X-Custom-Header": "value"
  }
  ```
- **Trigger Setelah (hari keterlambatan)**: 7
  - Artinya: webhook ke n8n baru dikirim setelah customer telat 7 hari
- **Auto Unsuspend saat Customer Bayar**: Toggle ON
  - Jika ON: otomatis kirim webhook unsuspend saat payment lunas
- **Custom Payload Template** (Optional):
  ```json
  {
    "mikrotik_ip": "192.168.1.1",
    "mikrotik_port": 8728,
    "notification_channel": "telegram"
  }
  ```

4. **Klik:** `Create`

### 3. Testing Integrasi

#### Testing via UI (Recommended) ⭐

1. Buka menu **Konfigurasi Sistem → Penagihan Otomatis**
2. Di list table, klik button **"Test Webhook"** ⚡ (icon petir)
3. Konfirmasi "Kirim Test"
4. Tunggu notifikasi:
   - ✅ **Sukses**: "Webhook Berhasil! Status Code: 200"
   - ❌ **Gagal**: "Webhook Gagal! [error message]"

**Keuntungan test via UI:**
- Tidak perlu command line
- Langsung dapat feedback visual
- Cepat & mudah untuk non-technical users

#### Manual Testing via Artisan

```bash
# Dry run - preview tanpa trigger webhook
php artisan dunning:process --dry-run

# Jalankan proses dunning sesungguhnya
php artisan dunning:process
```

Output yang diharapkan:
```
=== Dunning Process (n8n Integration) ===
Date: 13 Oktober 2025 10:30:15

Processing overdue payments...
────────────────────────────────────────────────────────────────

=== Summary ===
📋 Total Payments Processed: 15
🚀 Total n8n Webhooks Triggered: 3
✅ Dunning process completed successfully!
```

#### Cek Log

```bash
# Cek log aplikasi
tail -f storage/logs/laravel.log

# Cari log n8n webhook
grep "n8n webhook" storage/logs/laravel.log
```

Log yang diharapkan:
```
[2025-10-13 10:30:15] local.INFO: n8n webhook triggered successfully {"action":"suspend","customer":"John Doe","invoice":"INV-202510-001","status_code":200}
```

## 📡 Payload yang Dikirim ke n8n

### Payload Suspend

```json
{
  "action": "suspend",
  "customer_id": 123,
  "customer_name": "John Doe",
  "customer_phone": "628123456789",
  "customer_email": "john@example.com",
  "customer_address": "Jl. Contoh No. 123",
  "invoice_number": "INV-202510-001",
  "invoice_amount": 250000,
  "due_date": "2025-10-05",
  "days_overdue": 8,
  "payment_id": 456,
  "triggered_at": "2025-10-13T10:30:15+07:00",
  
  // Custom payload (jika diisi di config)
  "mikrotik_ip": "192.168.1.1",
  "mikrotik_port": 8728
}
```

### Payload Unsuspend

```json
{
  "action": "unsuspend",
  "customer_id": 123,
  "customer_name": "John Doe",
  "customer_phone": "628123456789",
  "customer_email": "john@example.com",
  "customer_address": "Jl. Contoh No. 123",
  "invoice_number": "INV-202510-001",
  "invoice_amount": 250000,
  "due_date": "2025-10-05",
  "days_overdue": 0,
  "payment_id": 456,
  "triggered_at": "2025-10-13T14:30:00+07:00",
  
  // Custom payload (jika diisi di config)
  "mikrotik_ip": "192.168.1.1",
  "mikrotik_port": 8728
}
```

## ⏰ Schedule & Automation

### Cron Schedule

Command `dunning:process` otomatis berjalan **3x sehari** (sudah terdaftar di `app/Console/Kernel.php`):
- **09:00** - Pagi (morning check)
- **14:00** - Siang (afternoon check)
- **18:00** - Sore (evening check)

Untuk mengubah jadwal (jika perlu lebih sering):

```php
// File: app/Console/Kernel.php

// Contoh: Ubah jadi setiap jam (dari 8 pagi - 8 malam)
$schedule->command('dunning:process')
    ->hourlyAt(0)
    ->between('8:00', '20:00')
    ->withoutOverlapping();
```

### Laravel Scheduler

Pastikan cron Laravel sudah berjalan di server:

```bash
# Tambahkan ke crontab
* * * * * cd /path-to-your-project && php artisan schedule:run >> /dev/null 2>&1
```

## 🔐 Security Best Practices

### 1. Gunakan HTTPS untuk Webhook
```
✅ https://your-n8n.com/webhook/dunning
❌ http://your-n8n.com/webhook/dunning
```

### 2. Tambahkan Authentication Header
```json
{
  "Authorization": "Bearer your-very-secret-token-12345",
  "X-Api-Key": "another-secret-key"
}
```

### 3. Validasi di n8n

Di n8n workflow, tambahkan node untuk validasi token:

```javascript
// Function node di n8n
if ($request.headers['authorization'] !== 'Bearer your-very-secret-token-12345') {
  return { error: 'Unauthorized' };
}
return $request.body;
```

### 4. Whitelist IP Laravel di n8n

Jika n8n Anda punya firewall, whitelist IP server Laravel.

## 📊 Monitoring & Troubleshooting

### 1. Cek Log Webhook

```bash
# Filter log n8n
tail -f storage/logs/laravel.log | grep "n8n"

# Cek webhook yang gagal
grep "n8n webhook failed" storage/logs/laravel.log

# Cek webhook yang sukses
grep "n8n webhook triggered successfully" storage/logs/laravel.log
```

### 2. Common Issues

#### ❌ "n8n integration not enabled"
**Solusi:** Pastikan toggle "Aktifkan Integrasi n8n" ON di konfigurasi

#### ❌ "Webhook URL not configured"
**Solusi:** Isi field "URL Webhook n8n" dengan URL yang valid

#### ❌ Connection timeout
**Solusi:** 
- Cek apakah n8n instance bisa diakses dari server Laravel
- Pastikan tidak ada firewall yang memblokir
- Test dengan: `curl https://your-n8n.com/webhook/dunning`

#### ❌ Status code 401/403
**Solusi:**
- Cek authentication headers
- Pastikan token di Laravel sama dengan yang di validasi n8n

#### ❌ No webhooks triggered
**Solusi:**
- Cek apakah ada payment yang overdue >= trigger days
- Jalankan dengan `--dry-run` untuk debug
- Cek log: `grep "Total n8n Webhooks Triggered" storage/logs/laravel.log`

### 3. Debug Mode

```bash
# Jalankan dengan verbose output
php artisan dunning:process -v

# Dry run untuk testing
php artisan dunning:process --dry-run
```

## 🎨 Contoh n8n Workflow

### Basic Workflow (Suspend/Unsuspend Mikrotik)

```
1. Webhook Node
   - HTTP Method: POST
   - Path: /webhook/dunning-action
   - Response: Return Workflow Data

2. Switch Node
   - Mode: Expression
   - Expression: {{ $json.action }}
   - Routes:
     - suspend
     - unsuspend

3a. HTTP Request (Suspend)
   - Method: POST
   - URL: http://{{ $json.mikrotik_ip }}:8728/api/user/disable
   - Body:
     {
       "username": "{{ $json.customer_phone }}",
       "reason": "Overdue {{ $json.days_overdue }} days"
     }

3b. HTTP Request (Unsuspend)
   - Method: POST
   - URL: http://{{ $json.mikrotik_ip }}:8728/api/user/enable
   - Body:
     {
       "username": "{{ $json.customer_phone }}"
     }

4. Telegram/WhatsApp Node (Optional)
   - Send notification ke admin
   - Message: "Customer {{ $json.customer_name }} telah di-{{ $json.action }}"
```

## 📞 Support & Updates

### Log Activity

Semua perubahan konfigurasi tercatat di Activity Log (Spatie):
- Bisa dilihat di menu admin
- Track siapa yang ubah konfigurasi
- Track kapan perubahan dilakukan

### Upgrade Guide

Jika ada update sistem:

```bash
# Pull update terbaru
git pull

# Run migration baru
php artisan migrate

# Clear cache
php artisan config:clear
php artisan cache:clear
```

## 🚀 Advanced Usage

### Custom Payload dengan Data Tambahan

Anda bisa menambahkan data custom di field "Custom Payload Template":

```json
{
  "mikrotik_ip": "192.168.1.1",
  "mikrotik_port": 8728,
  "mikrotik_username": "admin",
  "notification_telegram_chat_id": "-1001234567890",
  "notification_wa_group": "628123456789-admin",
  "priority": "high",
  "suspend_method": "disable_user",
  "custom_message": "Pelanggan ini prioritas VIP"
}
```

Data ini akan di-merge dengan payload default dan dikirim ke n8n.

### Multiple Mikrotik

Jika punya banyak Mikrotik, bisa tambahkan logic di n8n untuk routing berdasarkan area/region:

```javascript
// Function node di n8n
const mikrotikMap = {
  'Jakarta': '192.168.1.1',
  'Bandung': '192.168.2.1',
  'Surabaya': '192.168.3.1'
};

const customerCity = $json.customer_address.includes('Jakarta') ? 'Jakarta' : 'Bandung';
return {
  ...json,
  mikrotik_target_ip: mikrotikMap[customerCity]
};
```

## 📝 Checklist Implementasi

- [ ] Setup n8n instance (cloud/self-hosted)
- [ ] Buat workflow di n8n untuk suspend/unsuspend
- [ ] Test webhook n8n dengan curl/Postman
- [ ] Buat konfigurasi di menu "Penagihan Otomatis"
- [ ] Isi URL webhook n8n
- [ ] Set trigger days sesuai kebijakan bisnis
- [ ] Test dengan `php artisan dunning:process --dry-run`
- [ ] Jalankan test suspend untuk 1 customer
- [ ] Verifikasi customer tersuspend di Mikrotik
- [ ] Test unsuspend dengan update payment jadi paid
- [ ] Verifikasi customer teraktivasi kembali
- [ ] Setup monitoring & alert
- [ ] Dokumentasi workflow internal
- [ ] Training staff untuk monitoring

## 🎓 Best Practices

1. **Jangan set trigger days terlalu cepat** - beri grace period yang reasonable (7-14 hari)
2. **Test di staging dulu** - jangan langsung production
3. **Monitor log rutin** - setup alert jika ada failure
4. **Backup konfigurasi** - catat semua setting penting
5. **Dokumentasi custom payload** - jika pakai custom data
6. **Setup notification** - kirim alert ke admin saat ada suspend/unsuspend
7. **Regular audit** - cek apakah suspend/unsuspend berjalan sesuai harapan

---

**Dibuat:** 13 Oktober 2025  
**Versi:** 1.0  
**Author:** System Administrator

