# Pengaturan Zona Waktu (Timezone)

## 📋 Overview

Fitur pengaturan zona waktu memungkinkan Anda untuk mengatur waktu yang ditampilkan di seluruh aplikasi sesuai dengan lokasi Anda. Semua tanggal dan waktu akan ditampilkan berdasarkan zona waktu yang dipilih.

## ✨ Fitur

✅ **Pilih Zona Waktu** - Pilih dari zona waktu Indonesia (WIB, WITA, WIT) dan negara Asia lainnya  
✅ **Preview Real-time** - Lihat preview waktu saat ini ketika memilih zona waktu  
✅ **Otomatis Diterapkan** - Semua tanggal/waktu di aplikasi akan menggunakan zona waktu yang dipilih  
✅ **Zona Waktu Indonesia Lengkap** - WIB, WITA, WIT tersedia

## 🌏 Zona Waktu yang Tersedia

### Indonesia
- **WIB (GMT+7)** - Jakarta, Sumatera, Kalimantan Barat & Tengah
- **WITA (GMT+8)** - Makassar, Bali, Kalimantan Selatan & Timur, Sulawesi, NTB, NTT
- **WIT (GMT+9)** - Jayapura, Papua, Maluku

### Negara Asia Lainnya
- **Singapore (GMT+8)**
- **Kuala Lumpur (GMT+8)**
- **Bangkok (GMT+7)**
- **Manila (GMT+8)**
- **Tokyo (GMT+9)**
- **Seoul (GMT+9)**
- **Hong Kong (GMT+8)**
- **Taipei (GMT+8)**
- **UTC (GMT+0)** - Koordinat Universal

## 🎯 Cara Mengatur Zona Waktu

### 1. Buka Menu Pengaturan

1. Login ke aplikasi
2. Klik menu **Pengaturan** di sidebar
3. Pilih **Pengaturan Sistem**

### 2. Masuk ke Tab Invoice & Tagihan

1. Klik tab **Invoice & Tagihan**
2. Scroll ke bagian **Pengaturan Aplikasi**

### 3. Pilih Zona Waktu

1. Klik dropdown **Zona Waktu**
2. Pilih zona waktu yang sesuai dengan lokasi Anda
3. **Preview otomatis** akan muncul menampilkan waktu saat ini di zona tersebut

### 4. Simpan Pengaturan

1. Klik tombol **Simpan Pengaturan** di bagian bawah form
2. Refresh halaman untuk melihat perubahan

## 🔧 Implementasi Teknis

### 1. SystemSettings.php

**Form Field:**
```php
Forms\Components\Select::make('app_timezone')
    ->label('Zona Waktu')
    ->required()
    ->options([
        'Asia/Jakarta' => 'WIB - Jakarta, Sumatera (GMT+7)',
        'Asia/Makassar' => 'WITA - Makassar, Bali, Kalimantan (GMT+8)',
        'Asia/Jayapura' => 'WIT - Jayapura, Papua, Maluku (GMT+9)',
        // ... dan lainnya
    ])
    ->default('Asia/Jakarta')
    ->searchable()
    ->live()
    ->afterStateUpdated(function ($state) {
        // Preview waktu saat ini
        $currentTime = now()->timezone($state)->format('d F Y H:i:s');
        Notification::make()
            ->title('Preview Waktu')
            ->body("Waktu saat ini di zona {$state}: {$currentTime}")
            ->info()
            ->send();
    }),
```

**Save Method:**
```php
// Save Application Settings (Timezone)
if (isset($data['app_timezone'])) {
    Setting::set('app_timezone', $data['app_timezone']);
    // Set timezone immediately for current request
    config(['app.timezone' => $data['app_timezone']]);
    date_default_timezone_set($data['app_timezone']);
    Cache::forget('setting_app_timezone');
}
```

### 2. AppServiceProvider.php

**Boot Method:**
```php
public function boot(): void
{
    if (Schema::hasTable('settings')) {
        // Set app name
        $appName = \App\Models\Setting::get('app_name');
        if ($appName) {
            config(['app.name' => $appName]);
        }
        
        // Set timezone
        $timezone = \App\Models\Setting::get('app_timezone', 'Asia/Jakarta');
        if ($timezone) {
            config(['app.timezone' => $timezone]);
            date_default_timezone_set($timezone);
        }
    }
    
    // ... rest of code
}
```

### 3. Database Storage

Zona waktu disimpan di tabel `settings`:
```sql
INSERT INTO settings (key, value) VALUES ('app_timezone', 'Asia/Jakarta');
```

## 📊 Dampak Zona Waktu

Setelah zona waktu diatur, semua tampilan waktu akan berubah:

### 1. **Dashboard & Widget**
- Tanggal dan waktu di semua widget analytics
- Timeline chart
- Last update timestamps

### 2. **Tagihan (Payments)**
- Tanggal pembuatan invoice
- Tanggal jatuh tempo
- Tanggal pembayaran
- Created_at / Updated_at

### 3. **WhatsApp Messages**
- Waktu pengiriman (sent_at)
- Waktu penjadwalan (scheduled_at)
- Riwayat pesan

### 4. **Customer Portal**
- Riwayat pembayaran
- Status tagihan

### 5. **Log & Activity**
- Activity log timestamps
- Laravel log timestamps
- Error log timestamps

### 6. **Invoice PDF**
- Tanggal pembuatan invoice
- Tanggal jatuh tempo
- Tanggal pembayaran (jika sudah dibayar)

## 🧪 Testing

### 1. Test Perubahan Timezone

```php
php artisan tinker
```

```php
// Cek timezone saat ini
echo config('app.timezone');
// Output: Asia/Jakarta

// Cek waktu sekarang
echo now()->format('d F Y H:i:s');
// Output: 13 Oktober 2025 18:30:00

// Set timezone berbeda dan cek
\App\Models\Setting::set('app_timezone', 'Asia/Tokyo');
config(['app.timezone' => 'Asia/Tokyo']);
date_default_timezone_set('Asia/Tokyo');

echo now()->format('d F Y H:i:s');
// Output: 13 Oktober 2025 20:30:00 (GMT+9, +2 jam dari WIB)
```

### 2. Verifikasi di Database

```sql
-- Cek setting timezone
SELECT * FROM settings WHERE key = 'app_timezone';

-- Output:
-- | id | key          | value        | created_at | updated_at |
-- |----|--------------|--------------|------------|------------|
-- | 1  | app_timezone | Asia/Jakarta | ...        | ...        |
```

### 3. Test di UI

1. **Set ke WIB** (GMT+7)
   - Catat waktu yang ditampilkan
   
2. **Ganti ke WIT** (GMT+9)
   - Refresh halaman
   - Waktu seharusnya +2 jam dari sebelumnya

3. **Ganti ke UTC** (GMT+0)
   - Refresh halaman
   - Waktu seharusnya -7 jam dari WIB

## ⚠️ Catatan Penting

### 1. Database Timestamps Tetap UTC

**Database timestamps (created_at, updated_at) tetap disimpan dalam UTC**, tapi ditampilkan sesuai timezone yang dipilih.

Contoh:
```
Database: 2025-10-13 11:30:00 (UTC)
Display (WIB): 13 Oktober 2025 18:30:00 (UTC+7)
Display (WIT): 13 Oktober 2025 20:30:00 (UTC+9)
```

### 2. Zona Waktu Per User

Saat ini zona waktu adalah **global** untuk semua user. Jika ingin zona waktu per user, perlu:
- Tambah kolom `timezone` di tabel `users`
- Set timezone di middleware berdasarkan user yang login
- Update timezone preference di user profile

### 3. Refresh Diperlukan

Setelah mengubah zona waktu:
- **Refresh halaman** untuk melihat perubahan
- Cache akan dibersihkan otomatis
- Tidak perlu restart server

### 4. Kompatibilitas dengan Scheduler

Cron jobs dan scheduled tasks tetap menggunakan **server timezone** atau **UTC**. Pastikan:
- Set timezone di server yang sesuai
- Atau gunakan UTC untuk konsistensi
- Convert timezone saat display hasil

## 🔄 Migration Guide

Jika aplikasi sudah berjalan tanpa setting timezone:

### 1. Default Timezone

Jika belum ada setting `app_timezone`, sistem akan default ke:
- **Asia/Jakarta (WIB)** 

### 2. Set Initial Timezone

```sql
-- Set timezone default untuk existing installation
INSERT INTO settings (key, value, created_at, updated_at) 
VALUES ('app_timezone', 'Asia/Jakarta', NOW(), NOW())
ON DUPLICATE KEY UPDATE value = 'Asia/Jakarta';
```

### 3. Adjust Existing Data

Tidak perlu adjust data yang sudah ada karena:
- Database timestamps tetap UTC
- Hanya tampilan yang berubah sesuai timezone

## 💡 Tips & Best Practices

### 1. Pilih Zona Waktu yang Tepat

- Untuk Indonesia Barat: **Asia/Jakarta (WIB)**
- Untuk Indonesia Tengah: **Asia/Makassar (WITA)**
- Untuk Indonesia Timur: **Asia/Jayapura (WIT)**

### 2. Konsisten dengan Timezone Server

Jika server berada di Singapore, pertimbangkan:
- Option 1: Set app timezone ke **Asia/Jakarta** untuk user Indonesia
- Option 2: Set server timezone juga ke WIB untuk konsistensi

### 3. Komunikasi dengan Customer

Pastikan timezone yang dipilih sesuai dengan lokasi mayoritas customer Anda agar:
- Reminder tidak dikirim di waktu yang salah
- Due date sesuai dengan waktu lokal mereka
- Invoice timestamp mudah dipahami

### 4. Testing Reminder

Setelah set timezone, test reminder:
```bash
# Dry run untuk preview
php artisan whatsapp:payment-reminders --dry-run

# Cek waktu yang ditampilkan
# Pastikan sesuai dengan timezone yang dipilih
```

## 🐛 Troubleshooting

### Waktu Masih Tidak Sesuai

**Solusi:**
1. Clear cache: `php artisan config:clear`
2. Refresh halaman browser (Ctrl+F5)
3. Check setting: `SELECT * FROM settings WHERE key = 'app_timezone'`
4. Restart PHP-FPM/Apache jika diperlukan

### Timezone Tidak Tersimpan

**Solusi:**
1. Pastikan tabel `settings` ada
2. Check permissions write ke database
3. Lihat log error: `tail -f storage/logs/laravel.log`

### Scheduler Tidak Sesuai Timezone

**Solusi:**
1. Set timezone server: `sudo timedatectl set-timezone Asia/Jakarta`
2. Atau gunakan UTC di scheduler dan convert saat display
3. Check cron timezone: `cat /etc/timezone`

## 📚 Referensi

- [PHP Timezone List](https://www.php.net/manual/en/timezones.php)
- [Laravel Carbon Documentation](https://carbon.nesbot.com/docs/)
- [Indonesia Timezone Info](https://en.wikipedia.org/wiki/Time_in_Indonesia)

## 📊 Summary

| Aspek | Keterangan |
|-------|-----------|
| **Lokasi Setting** | Pengaturan → Pengaturan Sistem → Tab Invoice & Tagihan → Pengaturan Aplikasi |
| **Default** | Asia/Jakarta (WIB) |
| **Storage** | Database tabel `settings` dengan key `app_timezone` |
| **Scope** | Global (semua user) |
| **Refresh Required** | Ya, setelah save |
| **Preview** | Real-time saat memilih |
| **Database** | Timestamps tetap UTC |

---

**Terakhir diperbarui:** 2025-10-13  
**Status:** Implemented ✅  
**Version:** 1.0

