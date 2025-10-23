# 📘 Panduan Integrasi MikroTik - Lengkap & Detail

## 🎯 Overview

Fitur integrasi MikroTik yang telah diimplementasikan ke dalam aplikasi FastBiz Internet Management System. Integrasi ini mengadopsi logic dan fitur dari aplikasi **customer-management-2.0.5** dengan tampilan menggunakan **Filament**.

---

## ✨ Fitur yang Telah Dibuat

### 1. **Management Perangkat MikroTik**
- ✅ CRUD Perangkat MikroTik
- ✅ Test Koneksi ke perangkat
- ✅ Monitoring status real-time

### 2. **IP Binding Management**
- ✅ CRUD IP Binding untuk customer
- ✅ Auto-sync dengan MikroTik
- ✅ Enable/Disable IP Binding
- ✅ Suspend via IP Binding

### 3. **Netwatch Management**
- ✅ Monitor customer online/offline status
- ✅ Auto-sync netwatch dari MikroTik
- ✅ Real-time status updates

### 4. **Auto Isolir System**
- ✅ Konfigurasi auto isolir per device
- ✅ Grace period management
- ✅ Auto restore saat bayar
- ✅ Warning notification system
- ⚠️ Note: Actual suspension now handled via IP Binding

### 5. **Monitoring System**
- ✅ Real-time device monitoring
- ✅ CPU, Memory, HDD monitoring
- ✅ Active users count
- ✅ Monitoring history logs

---

## 📁 Struktur File yang Dibuat

### **Config**
```
config/mikrotik.php
```

### **Migrations**
```
database/migrations/
├── 2025_10_22_000001_create_mikrotik_profiles_table.php
├── 2025_10_22_000002_create_mikrotik_ppp_secrets_table.php
├── 2025_10_22_000003_create_mikrotik_queues_table.php
├── 2025_10_22_000004_create_mikrotik_monitoring_logs_table.php
├── 2025_10_22_000005_create_auto_isolir_configs_table.php
├── 2025_10_22_000006_update_customers_table_add_mikrotik_fields.php
└── 2025_10_22_000007_update_internet_packages_table_add_mikrotik_fields.php
```

### **Models**
```
app/Models/
├── MikrotikIpBinding.php
├── MikrotikNetwatch.php
├── MikrotikQueue.php
├── MikrotikMonitoringLog.php
├── AutoIsolirConfig.php
├── MikrotikDevice.php (updated)
├── Customer.php (updated)
└── InternetPackage.php (updated)
```

### **Services**
```
app/Services/
├── MikrotikApiService.php
├── MikrotikIpBindingService.php
├── MikrotikNetwatchService.php
├── MikrotikMonitoringService.php
├── SuspendViaIpBindingService.php
└── AutoIsolirService.php
```

### **Console Commands**
```
app/Console/Commands/
├── MikrotikMonitorCommand.php
├── MikrotikSyncIpBindingsCommand.php
├── MikrotikSyncNetwatchCommand.php
├── AutoIsolirCommand.php
├── AutoSuspendViaIpBindingCommand.php
└── MikrotikCleanLogsCommand.php
```

### **Filament Resources**
```
app/Filament/Resources/
├── MikrotikDeviceResource.php (updated)
├── MikrotikIpBindingResource.php
├── MikrotikNetwatchResource.php
└── AutoIsolirConfigResource.php
```

---

## 🚀 Cara Penggunaan

### **Step 1: Jalankan Migration**
```bash
php artisan migrate
```

### **Step 2: Tambah Perangkat MikroTik**
1. Buka menu **Konfigurasi Sistem → Perangkat MikroTik**
2. Klik **Buat Baru**
3. Isi informasi:
   - Nama Perangkat
   - IP Address
   - Port (default: 8728)
   - Username
   - Password
   - Enable SSL (jika diperlukan)
4. **Tes Koneksi** untuk memastikan koneksi berhasil

### **Step 3: Sinkronisasi Profil**
1. Pada list Perangkat MikroTik
2. Klik **Aksi → Sinkronisasi Profil**
3. Sistem akan mengimpor semua profil PPP dari MikroTik

### **Step 4: Konfigurasi Auto Isolir**
1. Buka menu **MikroTik → Auto Isolir**
2. Klik **Buat Baru**
3. Pilih Perangkat MikroTik
4. Set profil isolir
5. Atur grace period dan warning days
6. Enable notifikasi jika perlu

### **Step 5: Manage PPP Secret**
1. Buka menu **MikroTik → PPP Secret**
2. Buat secret baru atau link dengan pelanggan existing
3. Secret akan otomatis di-sync ke MikroTik

### **Step 6: Setup Cron Jobs**
Tambahkan ke crontab atau Task Scheduler:

```bash
# Monitoring setiap 5 menit
*/5 * * * * php /path/to/artisan mikrotik:monitor

# Auto isolir setiap jam 1 pagi
0 1 * * * php /path/to/artisan mikrotik:auto-isolir

# Clean old logs setiap minggu
0 0 * * 0 php /path/to/artisan mikrotik:clean-logs
```

---

## ⚙️ Konfigurasi (config/mikrotik.php)

### **Connection Settings**
```php
'connection' => [
    'timeout' => 5,      // Timeout koneksi (detik)
    'attempts' => 3,     // Jumlah percobaan
    'delay' => 1,        // Delay antar percobaan (detik)
],
```

### **Auto Isolir Settings**
```php
'auto_isolir' => [
    'enabled' => true,
    'profile_name' => 'ISOLIR',
    'queue_name' => 'ISOLIR',
    'check_interval' => 60,  // Interval cek (menit)
],
```

### **Monitoring Settings**
```php
'monitoring' => [
    'enabled' => true,
    'interval' => 5,         // Interval monitoring (menit)
    'store_days' => 30,      // Simpan data X hari
],
```

---

## 🔗 Relasi Database

### **Customer ↔ MikroTik**
```
Customer
├── mikrotik_device_id → MikrotikDevice
├── ppp_secret_id → MikrotikPppSecret
├── mikrotik_queue_id → MikrotikQueue
└── internet_package_id → InternetPackage
```

### **InternetPackage ↔ MikroTik**
```
InternetPackage
└── mikrotik_profile_id → MikrotikProfile
```

---

## 📊 Fitur-Fitur Detail

### **1. MikrotikApiService**
Service dasar untuk koneksi ke RouterOS API:
- `getClient()` - Buat koneksi client
- `testConnection()` - Test koneksi
- `getSystemResource()` - Get resource info
- `executeQuery()` - Execute custom query

### **2. MikrotikPppService**
Service untuk manage PPPoE users:
- `createSecret()` - Buat PPP secret
- `updateSecret()` - Update secret
- `deleteSecret()` - Hapus secret
- `enableSecret()` / `disableSecret()` - Enable/disable
- `disconnectActiveSession()` - Putus koneksi aktif
- `changeProfile()` - Ganti profil
- `getActiveSessions()` - Get active sessions

### **3. MikrotikProfileService**
Service untuk manage profil:
- `createProfile()` - Buat profil baru
- `updateProfile()` - Update profil
- `deleteProfile()` - Hapus profil
- `getAllProfiles()` - Get semua profil
- `syncAllProfiles()` - Sync dari MikroTik

### **4. MikrotikMonitoringService**
Service untuk monitoring:
- `checkDeviceStatus()` - Cek status device
- `getDeviceStatistics()` - Get statistik
- `getMonitoringHistory()` - Get history
- `cleanOldLogs()` - Bersihkan log lama

### **5. AutoIsolirService**
Service untuk auto isolir:
- `processAllDevices()` - Process semua device
- `processDevice()` - Process satu device
- `isolateCustomer()` - Isolir pelanggan
- `restoreCustomer()` - Restore pelanggan
- `getCustomersNeedingWarning()` - Get pelanggan perlu warning

---

## 🔧 Console Commands

### **Monitoring Command**
```bash
# Monitor semua device
php artisan mikrotik:monitor

# Monitor device tertentu
php artisan mikrotik:monitor 1
```

### **Auto Isolir Command**
```bash
# Process semua device
php artisan mikrotik:auto-isolir

# Process device tertentu
php artisan mikrotik:auto-isolir 1
```

### **Clean Logs Command**
```bash
# Hapus log lebih dari 30 hari
php artisan mikrotik:clean-logs

# Custom jumlah hari
php artisan mikrotik:clean-logs --days=60
```

---

## 🎨 UI Features (Filament)

### **Actions pada MikrotikDeviceResource:**
- **Tes Koneksi** - Test koneksi ke MikroTik
- **Sinkronisasi Profil** - Import profil dari MikroTik
- **Cek Status** - Lihat status real-time

### **Actions pada MikrotikPppSecretResource:**
- **Aktifkan/Non-aktifkan** - Enable/disable secret
- **Putuskan Koneksi** - Disconnect active session
- **Auto-sync** - Otomatis sync saat create/update

### **Filter & Search:**
Semua resource dilengkapi dengan:
- Search by name, username, dll
- Filter by device, profile, status
- Sorting columns

---

## 🔐 Security Notes

1. **Password Storage**: Password MikroTik disimpan di database. Pertimbangkan untuk encrypt.
2. **API Access**: Pastikan port 8728/8729 hanya accessible dari server aplikasi
3. **User Permissions**: Buat user MikroTik dengan permission minimal yang diperlukan

---

## 📝 Catatan Penting

1. **RouterOS API Package** sudah terinstall (`evilfreelancer/routeros-api-php`)
2. **Relasi Models** sudah dibuat antar Customer, InternetPackage, dan MikroTik
3. **Activity Logging** sudah terintegrasi dengan Spatie Activity Log
4. **Cache** digunakan untuk performa (5 menit)
5. **Error Handling** lengkap dengan logging

---

## 🐛 Troubleshooting

### **Koneksi Gagal**
- Cek IP address dan port
- Pastikan API service enabled di MikroTik: `/ip service enable api`
- Cek firewall rules

### **Secret Tidak Sync**
- Cek status is_synced di database
- Manual sync dengan action "Sync ke MikroTik"
- Lihat log error di `storage/logs`

### **Auto Isolir Tidak Jalan**
- Pastikan cron job berjalan
- Cek config `enabled = true`
- Cek grace_period_days sudah sesuai
- Lihat log untuk error messages

---

## 📞 Support

Jika ada pertanyaan atau issues, cek:
1. Laravel logs: `storage/logs/laravel.log`
2. Activity logs melalui Filament
3. Monitoring logs di database

---

## ✅ Checklist Implementation

- [x] Config file
- [x] Migrations (7 files)
- [x] Models (5 new + 3 updated)
- [x] Services (5 files)
- [x] Console Commands (3 files)
- [x] Filament Resources (3 new + 1 updated)
- [x] Auto-sync features
- [x] Monitoring system
- [x] Auto isolir system
- [x] Documentation

---

**Status: ✅ IMPLEMENTATION COMPLETE**

Semua fitur telah diimplementasikan dengan lengkap dan siap digunakan!

