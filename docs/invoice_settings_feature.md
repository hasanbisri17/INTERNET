# Fitur Pengaturan Invoice

## Overview
Fitur ini memungkinkan Anda untuk mengcustomize informasi yang ditampilkan di invoice PDF, termasuk informasi perusahaan, informasi pembayaran/rekening bank, dan footer invoice.

## Cara Mengakses

1. Login ke panel admin Filament
2. Buka menu **Pengaturan** → **Pengaturan Invoice**
3. Anda akan melihat form dengan beberapa section

## Section yang Tersedia

### 1. Informasi Perusahaan
Informasi ini ditampilkan di bagian **header/atas invoice**:

- **Nama Perusahaan**: Nama perusahaan yang akan ditampilkan di invoice (misalnya: "PT. Internet Provider Indonesia")
- **Alamat Perusahaan**: Alamat lengkap perusahaan
- **Nomor Telepon**: Nomor telepon yang bisa dihubungi pelanggan
- **Email Perusahaan**: Email untuk korespondensi

**Contoh di Invoice:**
```
PT. Internet Provider Indonesia
Jl. Contoh No. 123, Kota, Provinsi 12345
Telp: 021-12345678
Email: info@company.com
```

### 2. Informasi Pembayaran
Informasi ini ditampilkan di bagian **tengah-bawah invoice**:

- **Nama Bank**: Nama bank untuk transfer (misalnya: "Bank BCA", "Bank Mandiri")
- **Nomor Rekening**: Nomor rekening bank untuk pembayaran
- **Nama Pemilik Rekening**: Atas nama rekening (biasanya nama perusahaan)
- **Catatan Pembayaran** (opsional): Catatan tambahan mengenai cara pembayaran

**Contoh di Invoice:**
```
INFORMASI PEMBAYARAN
Bank: Bank BCA              No. Rekening: 1234567890
Atas Nama: PT. Internet Provider Indonesia

Silakan transfer ke rekening di atas atau hubungi kami 
untuk metode pembayaran lainnya.
```

### 3. Footer Invoice
Pesan yang ditampilkan di bagian **paling bawah invoice**:

- **Pesan Footer**: Ucapan terima kasih atau informasi tambahan

**Contoh di Invoice:**
```
Terima kasih atas kepercayaan Anda menggunakan layanan kami.
Invoice ini dicetak secara otomatis dan sah tanpa tanda tangan.

PT. Internet Provider Indonesia
```

## Cara Menggunakan

1. **Isi semua field yang diperlukan**
   - Field yang bertanda * wajib diisi
   - Field lainnya bersifat opsional

2. **Klik tombol "Simpan Pengaturan"**
   - Sistem akan menyimpan semua perubahan
   - Anda akan melihat notifikasi sukses

3. **Test Invoice**
   - Buka halaman Pembayaran/Tagihan
   - Pilih salah satu invoice
   - Klik tombol "Download Invoice"
   - Invoice PDF akan menampilkan informasi yang baru Anda set

## Catatan Penting

### Cache Settings
- Settings di-cache selama 1 jam untuk performa
- Jika perubahan tidak tampil, clear cache dengan:
  ```bash
  php artisan cache:clear
  ```

### Invoice yang Sudah Dikirim
- Invoice yang sudah digenerate/dikirim via WhatsApp akan tetap menggunakan setting lama
- Perubahan hanya berlaku untuk invoice yang baru digenerate

### Default Values
- Jika field dikosongkan, sistem akan menggunakan nilai default
- Pastikan mengisi semua field untuk hasil terbaik

## Integrasi dengan Fitur Lain

### 1. Generate Tagihan Bulanan
Ketika tagihan bulanan digenerate (manual atau via command), invoice PDF yang dikirim via WhatsApp akan menggunakan setting terbaru.

### 2. Download Invoice
Saat customer/admin download invoice dari dashboard, PDF yang digenerate akan menggunakan setting terbaru.

### 3. Riwayat Pesan Sistem
Invoice yang ditampilkan di detail "Riwayat Pesan Sistem" menggunakan file PDF yang sudah digenerate sebelumnya, jadi tidak akan berubah jika setting diupdate.

## Troubleshooting

### Invoice masih menampilkan data lama
**Solusi:**
1. Clear cache: `php artisan optimize:clear`
2. Regenerate invoice yang baru
3. Pastikan Anda sudah klik "Simpan Pengaturan"

### PDF tidak bisa digenerate
**Solusi:**
1. Cek file `storage/logs/laravel.log` untuk error detail
2. Pastikan folder `storage/app/public/invoices` ada dan writable
3. Pastikan semua field required terisi

### Tampilan invoice berantakan
**Solusi:**
1. Jangan gunakan karakter special yang berlebihan
2. Alamat sebaiknya tidak terlalu panjang (max 2-3 baris)
3. Gunakan format nomor telepon yang standard

## Technical Details

### Files Affected
- `app/Filament/Pages/InvoiceSettings.php` - Halaman pengaturan
- `resources/views/filament/pages/invoice-settings.blade.php` - View halaman
- `resources/views/invoice-modern.blade.php` - Template invoice
- `database/seeders/SettingSeeder.php` - Default settings
- `app/Models/Setting.php` - Model untuk settings

### Database
Settings disimpan di table `settings` dengan struktur:
- `key`: nama setting (misalnya: `company_name`)
- `value`: nilai setting

### Setting Keys
```php
// Company Info
'company_name'         // Nama perusahaan
'company_address'      // Alamat perusahaan
'company_phone'        // Nomor telepon
'company_email'        // Email perusahaan

// Bank Info
'bank_name'            // Nama bank
'bank_account'         // Nomor rekening
'bank_account_name'    // Nama pemilik rekening
'payment_notes'        // Catatan pembayaran

// Invoice
'invoice_footer'       // Footer message
```

## Screenshots

### Halaman Pengaturan
![Pengaturan Invoice](../screenshots/invoice-settings.png)

### Invoice dengan Custom Settings
![Invoice PDF](../screenshots/invoice-custom.png)

---

**Dibuat:** 13 Oktober 2025
**Update Terakhir:** 13 Oktober 2025

