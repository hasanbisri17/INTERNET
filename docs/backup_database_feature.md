# Fitur Backup Database

## 📋 Overview

Fitur backup database memungkinkan Anda untuk membuat salinan lengkap database aplikasi dengan sekali klik. Backup ini penting untuk keamanan data dan recovery jika terjadi masalah.

## ✨ Fitur

✅ **Backup dengan 1 Klik** - Cukup klik tombol, database otomatis di-backup  
✅ **Download Otomatis** - File SQL langsung terdownload setelah backup selesai  
✅ **Info Backup Terakhir** - Lihat kapan terakhir kali backup dilakukan  
✅ **Ukuran Database** - Monitor ukuran database secara real-time  
✅ **Dual Method** - Menggunakan mysqldump (cepat) atau PHP fallback (kompatibel)  
✅ **Auto Timestamp** - Nama file otomatis berisi tanggal dan waktu backup  

## 💾 Data yang Di-backup

Backup akan menyimpan SEMUA data termasuk:

- ✅ Data customer dan paket internet
- ✅ Riwayat tagihan dan pembayaran
- ✅ Template dan pengaturan WhatsApp
- ✅ Log aktivitas dan pesan
- ✅ Semua pengaturan sistem
- ✅ User accounts dan permissions
- ✅ Analytics data dan widgets

## 🎯 Cara Menggunakan

### 1. Akses Menu Backup

1. Login ke aplikasi
2. Klik menu **Pengaturan** → **Pengaturan Sistem**
3. Pilih tab **Pengaturan Aplikasi** (tab pertama)
4. Scroll ke bagian **Backup & Restore**

### 2. Informasi yang Ditampilkan

Anda akan melihat:
- **Backup Terakhir**: Tanggal dan waktu backup terakhir (relatif, misal: "2 jam yang lalu")
- **Ukuran Database**: Total ukuran database dalam MB

### 3. Melakukan Backup

1. Klik tombol **Backup Database Sekarang** (hijau dengan icon download)
2. Konfirmasi dengan klik **Ya, Backup Sekarang**
3. Tunggu proses backup (biasanya 5-30 detik tergantung ukuran database)
4. File SQL akan otomatis terdownload
5. Notifikasi sukses akan muncul

### 4. Hasil Backup

File yang terdownload:
- **Format nama**: `backup_database_[nama_db]_[timestamp].sql`
- **Contoh**: `backup_database_fastbiz_2025-10-13_123045.sql`
- **Lokasi**: Di folder Downloads browser Anda

## 🔧 Implementasi Teknis

### Dual Backup Method

#### Method 1: mysqldump (Preferensi)
```bash
mysqldump --user=root --password=xxx --host=localhost database_name > backup.sql
```

**Kelebihan:**
- ✅ Sangat cepat
- ✅ Standard MySQL tool
- ✅ Reliable dan efficient

**Deteksi Path Otomatis:**
- XAMPP Windows: `C:\xampp\mysql\bin\mysqldump.exe`
- Laragon: `C:\laragon\bin\mysql\...\mysqldump.exe`
- Linux: `/usr/bin/mysqldump`
- macOS: `/usr/local/bin/mysqldump`

#### Method 2: PHP Fallback

Jika mysqldump tidak ditemukan, sistem otomatis menggunakan PHP:

```php
// Loop semua table
foreach ($tables as $table) {
    // Get structure
    SHOW CREATE TABLE `table_name`
    
    // Get data
    SELECT * FROM `table_name`
    
    // Export to SQL
}
```

**Kelebihan:**
- ✅ Tidak perlu mysqldump
- ✅ Bekerja di semua environment
- ✅ Portable

**Kekurangan:**
- ⚠️ Lebih lambat untuk database besar
- ⚠️ Butuh memory lebih banyak

### File Storage

```
storage/
  └── app/
      └── backups/
          ├── backup_database_fastbiz_2025-10-13_080000.sql
          ├── backup_database_fastbiz_2025-10-13_143000.sql
          └── ...
```

**Note:** File backup akan otomatis dihapus setelah didownload untuk menghemat space.

## 📊 Format File Backup

File SQL yang dihasilkan berisi:

```sql
-- Database Backup
-- Generated: 2025-10-13 12:30:45
-- Database: fastbiz

SET FOREIGN_KEY_CHECKS=0;

DROP TABLE IF EXISTS `users`;
CREATE TABLE `users` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  ...
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT INTO `users` VALUES
(1, 'Admin', 'admin@example.com', ...),
(2, 'User 1', 'user1@example.com', ...);

-- ... semua table lainnya ...

SET FOREIGN_KEY_CHECKS=1;
```

## 🔄 Restore Backup

### Via phpMyAdmin

1. Buka phpMyAdmin (`http://localhost/phpmyadmin`)
2. Pilih database yang ingin di-restore
3. Klik tab **Import**
4. Pilih **Choose File** → pilih file backup `.sql`
5. Klik **Go**

### Via Command Line

```bash
mysql -u root -p database_name < backup_database_fastbiz_2025-10-13_123045.sql
```

### Via MySQL Workbench

1. Buka MySQL Workbench
2. Connect ke database server
3. Klik **Server** → **Data Import**
4. Pilih **Import from Self-Contained File**
5. Browse file backup `.sql`
6. Klik **Start Import**

## ⚠️ Catatan Penting

### 1. Backup Berkala

**Rekomendasi:**
- 🔴 **Critical**: Backup setiap hari (untuk data production)
- 🟡 **Normal**: Backup 1-2x per minggu
- 🟢 **Testing**: Backup sebelum update/perubahan besar

### 2. Simpan di Tempat Aman

✅ **Lakukan:**
- Simpan di cloud storage (Google Drive, Dropbox, OneDrive)
- Simpan di hard drive eksternal
- Simpan di server backup terpisah
- Enkripsi jika berisi data sensitif

❌ **Jangan:**
- Hanya simpan di komputer yang sama
- Hanya simpan di server yang sama
- Mengabaikan backup

### 3. Test Restore

Secara berkala (misal 1x bulan):
1. Download backup
2. Test restore di database testing
3. Verifikasi data lengkap dan berfungsi

### 4. Monitoring Ukuran

Jika ukuran database terus membesar:
- Review data lama yang bisa di-archive
- Hapus log yang sudah tidak diperlukan
- Compress backup jika perlu

## 🐛 Troubleshooting

### Backup Gagal: "mysqldump not found"

**Solusi:**
Sistem otomatis menggunakan PHP fallback. Backup tetap jalan tapi mungkin lebih lambat.

Untuk performance optimal, install MySQL client tools:
- **XAMPP**: Sudah include mysqldump
- **Laragon**: Sudah include mysqldump
- **Linux**: `sudo apt-get install mysql-client`
- **macOS**: `brew install mysql-client`

### Backup Lambat

**Penyebab:**
- Database terlalu besar (>500MB)
- Menggunakan PHP method (bukan mysqldump)
- Server load tinggi

**Solusi:**
1. Pastikan mysqldump terdeteksi
2. Lakukan backup saat traffic rendah
3. Consider automated backup via cron

### File Tidak Terdownload

**Solusi:**
1. Check browser download settings
2. Check popup blocker
3. Check disk space
4. Try different browser

### Error saat Restore

**Solusi:**
1. Pastikan database kosong atau backup database lama dulu
2. Check MySQL version compatibility
3. Increase `max_allowed_packet` di MySQL config jika file besar
4. Import via command line untuk file >50MB

## 🔒 Keamanan

### 1. File Backup

**⚠️ File backup berisi:**
- Password (hashed)
- Data customer
- Data keuangan
- Setting API

**Proteksi:**
```bash
# Encrypt backup (opsional)
openssl enc -aes-256-cbc -salt -in backup.sql -out backup.sql.enc

# Decrypt saat restore
openssl enc -d -aes-256-cbc -in backup.sql.enc -out backup.sql
```

### 2. Access Control

Hanya admin yang bisa akses fitur backup:
- Check di `SystemSettings.php`
- Pastikan hanya authorized users

## 📈 Best Practices

### 1. Automated Backup

Buat command untuk auto backup:

```php
// app/Console/Commands/BackupDatabase.php
php artisan make:command BackupDatabase
```

Schedule di `Kernel.php`:
```php
$schedule->command('backup:database')->daily();
```

### 2. Rotation Policy

Simpan backup dengan rotasi:
- **Daily**: 7 hari terakhir
- **Weekly**: 4 minggu terakhir
- **Monthly**: 12 bulan terakhir

### 3. Notification

Setup notifikasi untuk:
- ✅ Backup sukses
- ❌ Backup gagal
- ⚠️ Reminder jika >7 hari tidak backup

### 4. Verification

Auto verify backup:
```php
// Check file size > 0
// Check SQL syntax valid
// Test import ke temp database
```

## 💡 Tips

1. **Backup Sebelum Update**
   - Selalu backup sebelum update aplikasi
   - Backup sebelum migrasi database
   - Backup sebelum perubahan besar

2. **Multiple Copies**
   - Simpan 3 copy di tempat berbeda
   - 1 copy lokal, 1 cloud, 1 eksternal

3. **Naming Convention**
   - File otomatis pakai timestamp
   - Tambah note manual jika perlu
   - Contoh: `backup_before_migration_2025-10-13.sql`

4. **Documentation**
   - Catat kapan backup
   - Catat alasan backup
   - Catat hasil restore test

## 📚 Referensi

- [MySQL Backup Methods](https://dev.mysql.com/doc/refman/8.0/en/backup-methods.html)
- [mysqldump Documentation](https://dev.mysql.com/doc/refman/8.0/en/mysqldump.html)
- [Laravel Database Backup](https://spatie.be/docs/laravel-backup/v8/introduction)

## 📊 Summary

| Aspek | Detail |
|-------|--------|
| **Lokasi Menu** | Pengaturan → Pengaturan Sistem → Tab Pengaturan Aplikasi → Backup & Restore |
| **Method** | mysqldump (primary) atau PHP (fallback) |
| **Format** | SQL file dengan timestamp |
| **Auto Download** | Ya |
| **Ukuran Typical** | 5-50 MB (tergantung data) |
| **Waktu Typical** | 5-30 detik |
| **Rekomendasi** | Backup minimal 1x seminggu |

---

**Terakhir diperbarui:** 2025-10-13  
**Status:** Implemented ✅  
**Version:** 1.0

