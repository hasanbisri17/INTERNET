# Fitur MikroTik IP Bindings Management

## Deskripsi
Fitur ini memungkinkan Anda untuk mengelola IP Bindings dari MikroTik Hotspot langsung dari sistem. IP Bindings digunakan untuk mengontrol akses hotspot berdasarkan MAC Address dan IP Address.

## Fitur Utama

### 1. **🔄 Auto-Sync ke MikroTik** ⭐ NEW!
- **Otomatis sync setiap kali ada perubahan data**
- Tidak perlu klik tombol "Sync" manual lagi
- Perubahan langsung ter-apply ke MikroTik secara real-time
- Activity log tercatat di `storage/logs/laravel.log`

### 2. **Sync IP Bindings dari MikroTik**
- Mengambil semua IP Bindings dari perangkat MikroTik
- Menyimpan ke database lokal untuk management
- Otomatis update data yang sudah ada

### 3. **Ubah Type IP Binding**
Tiga jenis type yang tersedia:
- **🟢 Regular**: User harus melakukan autentikasi hotspot normal
- **🟡 Bypassed**: User bypass autentikasi (langsung terkoneksi)
- **🔴 Blocked**: User diblokir dan tidak bisa akses hotspot

### 4. **CRUD IP Bindings**
- **Create**: Buat IP Binding baru (auto-sync ke MikroTik)
- **Read**: Lihat daftar IP Bindings dengan kolom comment
- **Update**: Edit konfigurasi IP Binding (auto-sync ke MikroTik)
- **Delete**: Hapus IP Binding (auto-sync ke MikroTik)

### 5. **Filter & Search**
- Filter berdasarkan perangkat MikroTik
- Filter berdasarkan type (Regular/Bypassed/Blocked)
- Filter berdasarkan status (Enabled/Disabled)
- Search berdasarkan MAC Address, IP Address, dan Comment

## Cara Menggunakan

### A. Sync IP Bindings dari MikroTik

#### Melalui Web Interface:
1. Buka menu **MikroTik** → **IP Bindings**
2. Klik tombol **"Sync dari MikroTik"** di bagian atas tabel
3. Pilih perangkat MikroTik yang ingin di-sync
4. Klik **"Submit"**
5. Sistem akan mengambil semua IP Bindings dari MikroTik

#### Melalui Command Line:
```bash
# Sync semua perangkat aktif
php artisan mikrotik:sync-ip-bindings --all

# Sync perangkat tertentu (dengan ID)
php artisan mikrotik:sync-ip-bindings 1

# Interactive mode (pilih dari menu)
php artisan mikrotik:sync-ip-bindings
```

### B. Mengubah Type IP Binding (Auto-Sync Aktif) 🔄

#### Skenario: Mengubah dari Bypassed ke Regular

1. Buka menu **MikroTik** → **IP Bindings**
2. Cari IP Binding yang ingin diubah (misalnya yang type-nya **Bypassed**)
3. Klik tombol **"Aksi"** pada row tersebut
4. Pilih **"Change to Regular"**
5. Konfirmasi perubahan
6. ✅ **Otomatis langsung sync ke MikroTik** tanpa perlu klik tombol sync!
7. Notifikasi sukses akan muncul: "Type berhasil diubah ke Regular dan otomatis di-sync ke MikroTik"

**Catatan**: Fitur auto-sync membuat perubahan langsung ter-apply ke MikroTik secara real-time!

#### Skenario: Mengubah ke Bypassed (Skip Autentikasi)

Berguna untuk:
- Perangkat yang tidak perlu login (printer, CCTV, dll)
- VIP user yang ingin auto-connect
- Perangkat IoT

Langkah:
1. Klik **"Aksi"** pada IP Binding
2. Pilih **"Change to Bypassed"**
3. Konfirmasi
4. User dengan MAC/IP tersebut akan otomatis bypass autentikasi

#### Skenario: Block User

Untuk memblokir user tertentu:
1. Klik **"Aksi"** pada IP Binding
2. Pilih **"Change to Blocked"**
3. Konfirmasi
4. User tersebut tidak akan bisa akses hotspot

### C. Membuat IP Binding Baru

1. Klik **"New mikrotik ip binding"**
2. Isi form:
   - **Perangkat MikroTik**: Pilih perangkat
   - **MAC Address**: Masukkan MAC address (format: XX:XX:XX:XX:XX:XX)
   - **IP Address**: Masukkan IP address yang akan di-bind
   - **To Address**: (Opsional) IP tujuan
   - **Hotspot Server**: Default "all"
   - **Type**: Pilih Regular/Bypassed/Blocked
   - **Comment**: Keterangan (opsional)
3. Klik **"Create"**
4. Klik **"Sync ke MikroTik"** untuk apply ke MikroTik

### D. Edit IP Binding

1. Klik **"Aksi"** → **"Edit"**
2. Ubah field yang diperlukan
3. Klik **"Save"**
4. Klik **"Aksi"** → **"Sync ke MikroTik"** untuk update ke MikroTik

### E. Hapus IP Binding

1. Klik **"Aksi"** → **"Delete"**
2. Konfirmasi penghapusan
3. Data akan dihapus dari database dan MikroTik

## Field-field Penting

### MAC Address
- Format: `00:0C:29:12:34:56`
- MAC address dari perangkat yang akan di-bind
- Opsional (bisa diisi MAC atau IP atau keduanya)

### IP Address (address)
- Format: `192.168.1.100`
- IP address yang akan di-bind
- Opsional (bisa diisi MAC atau IP atau keduanya)

### To Address
- IP address tujuan setelah binding
- Biasanya dikosongkan (optional)

### Server
- Nama hotspot server di MikroTik
- Default: `all` (berlaku untuk semua server)

### Type
| Type | Deskripsi | Use Case |
|------|-----------|----------|
| **Regular** | User harus login hotspot | User normal |
| **Bypassed** | Skip autentikasi, langsung connect | Printer, CCTV, VIP user |
| **Blocked** | Diblokir, tidak bisa akses | User yang di-ban |

## Use Cases

### 1. Bypass Autentikasi untuk Printer
**Masalah**: Printer WiFi perlu koneksi tanpa login hotspot

**Solusi**:
1. Dapatkan MAC address printer (cek di sticker printer atau setting)
2. Buat IP Binding baru
3. Isi MAC address printer
4. Set type = **Bypassed**
5. Sync ke MikroTik
6. Printer akan otomatis terkoneksi tanpa login

### 2. Block User yang Bermasalah
**Masalah**: Ada user yang melanggar aturan dan perlu diblokir

**Solusi**:
1. Cari MAC address user dari active users MikroTik
2. Buat IP Binding dengan MAC tersebut
3. Set type = **Blocked**
4. User tidak bisa akses hotspot meskipun punya voucher

### 3. VIP User Auto-Connect
**Masalah**: User VIP tidak mau repot login setiap kali

**Solusi**:
1. Dapatkan MAC address perangkat VIP
2. Buat IP Binding
3. Set type = **Bypassed**
4. VIP user langsung terkoneksi otomatis

### 4. Ubah Bypassed ke Regular
**Masalah**: User yang sebelumnya bypass sekarang harus login

**Solusi**:
1. Cari IP Binding user tersebut (filter type = Bypassed)
2. Klik **"Aksi"** → **"Change to Regular"**
3. User sekarang harus login hotspot

## Struktur Database

### Tabel: `mikrotik_ip_bindings`

| Field | Type | Deskripsi |
|-------|------|-----------|
| `id` | bigint | Primary key |
| `mikrotik_device_id` | bigint | Foreign key ke mikrotik_devices |
| `binding_id` | string | ID binding dari MikroTik (.id) |
| `mac_address` | string | MAC Address |
| `address` | string | IP Address |
| `to_address` | string | To Address (opsional) |
| `server` | string | Nama hotspot server |
| `type` | enum | regular/bypassed/blocked |
| `comment` | string | Komentar |
| `is_disabled` | boolean | Status aktif/nonaktif |
| `is_synced` | boolean | Sudah sync dengan MikroTik? |
| `last_synced_at` | timestamp | Waktu terakhir sync |

## API Endpoints (MikroTik RouterOS)

Fitur ini menggunakan RouterOS API:

### Get IP Bindings
```
/ip/hotspot/ip-binding/print
```

### Update Type
```
/ip/hotspot/ip-binding/set
    .id=*1
    type=bypassed
```

### Create IP Binding
```
/ip/hotspot/ip-binding/add
    mac-address=00:0C:29:12:34:56
    address=192.168.1.100
    type=bypassed
```

### Delete IP Binding
```
/ip/hotspot/ip-binding/remove
    .id=*1
```

## Troubleshooting

### IP Bindings tidak muncul setelah sync
**Penyebab**: Belum ada IP Binding di MikroTik atau koneksi gagal

**Solusi**:
1. Cek koneksi ke MikroTik: Menu **Perangkat MikroTik** → **Test Connection**
2. Login ke MikroTik via Winbox: IP → Hotspot → IP Bindings
3. Pastikan ada data di MikroTik
4. Coba sync ulang

### Error "unknown parameter" saat sync
**Penyebab**: Ada parameter yang tidak didukung

**Solusi**:
1. Cek versi RouterOS MikroTik (min. v6.x)
2. Cek log error di `storage/logs/laravel.log`
3. Update RouterOS jika terlalu lama

### Perubahan type tidak apply
**Penyebab**: Binding belum di-sync atau `binding_id` kosong

**Solusi**:
1. Pastikan kolom `binding_id` terisi (cek di database)
2. Jika kosong, hapus record dan sync ulang dari MikroTik
3. Atau klik **"Sync ke MikroTik"** untuk create binding baru

### User masih bisa akses padahal sudah di-block
**Penyebab**: User pakai perangkat/MAC address lain

**Solusi**:
1. Block berdasarkan IP address juga, bukan hanya MAC
2. Atau gunakan fitur User Manager di MikroTik untuk block user account

## Tips & Best Practices

### 1. Regular Sync
Jalankan sync secara berkala (misal via cron):
```bash
# Tambahkan ke crontab
0 */6 * * * cd /path/to/app && php artisan mikrotik:sync-ip-bindings --all
```

### 2. Dokumentasi MAC Address
Selalu isi field **Comment** dengan keterangan:
- Nama user
- Perangkat (Printer, CCTV, Laptop, HP)
- Alasan bypass/block

### 3. Backup Sebelum Mass Change
Sebelum mengubah banyak IP Bindings:
1. Export data dari MikroTik
2. Atau backup database aplikasi

### 4. Monitoring
Monitor IP Bindings yang type-nya **Bypassed**:
- Pastikan hanya perangkat yang authorized
- Review berkala untuk keamanan

## File yang Terkait

1. **Migration**: `database/migrations/2025_10_22_100001_create_mikrotik_ip_bindings_table.php`
2. **Model**: `app/Models/MikrotikIpBinding.php`
3. **Service**: `app/Services/MikrotikIpBindingService.php`
4. **API Service**: `app/Services/MikrotikApiService.php`
5. **Resource**: `app/Filament/Resources/MikrotikIpBindingResource.php`
6. **Command**: `app/Console/Commands/MikrotikSyncIpBindingsCommand.php`

## Cara Kerja Auto-Sync 🔄

### Observer Pattern
Sistem menggunakan **Laravel Observer** untuk mendeteksi perubahan data dan otomatis sync ke MikroTik:

```
User melakukan perubahan
    ↓
Observer mendeteksi event (created/updated/deleted)
    ↓
Observer memanggil MikrotikIpBindingService
    ↓
Service mengirim request ke MikroTik API
    ↓
MikroTik diupdate secara real-time
    ↓
Log activity tercatat
```

### Event yang Ter-Trigger Auto-Sync

| Event | Action | Sync ke MikroTik |
|-------|--------|------------------|
| **Create** | Buat IP Binding baru | ✅ Create di MikroTik |
| **Update Type** | Ubah type binding | ✅ Update type di MikroTik |
| **Update Disabled** | Enable/Disable binding | ✅ Enable/Disable di MikroTik |
| **Delete** | Hapus IP Binding | ✅ Delete dari MikroTik |

### Monitoring Auto-Sync

Semua aktivitas auto-sync tercatat di log file:

```bash
# Lihat log auto-sync
tail -f storage/logs/laravel.log | grep "Auto-sync"
```

Log entry contoh:
```
[2025-10-22 16:06:08] local.INFO: Auto-sync: Updating IP Binding to MikroTik {"binding_id":1,"mikrotik_binding_id":"*5","changed_fields":["type"]}
[2025-10-22 16:06:09] local.INFO: Auto-sync: Type updated successfully {"binding_id":1,"new_type":"regular"}
```

### Keuntungan Auto-Sync

✅ **Lebih Cepat**: Tidak perlu klik tombol sync manual  
✅ **Real-time**: Perubahan langsung apply ke MikroTik  
✅ **Konsisten**: Data selalu sinkron antara aplikasi dan MikroTik  
✅ **User-friendly**: Workflow lebih sederhana  
✅ **Traceable**: Semua sync tercatat di log  

## Changelog

### v1.1.0 (2025-10-22) - Auto-Sync Update
- 🔄 **NEW**: Auto-sync ke MikroTik untuk semua perubahan data
- 🔄 **NEW**: Observer pattern untuk mendeteksi perubahan
- 🔄 **NEW**: Kolom Comment ditampilkan di tabel
- ✅ Activity logging untuk auto-sync
- ✅ Hapus tombol sync manual (sudah otomatis)

### v1.0.0 (2025-10-22)
- ✅ Initial release
- ✅ Sync IP Bindings dari MikroTik
- ✅ Ubah type (Regular/Bypassed/Blocked)
- ✅ CRUD IP Bindings
- ✅ Filter & Search
- ✅ Command line sync
- ✅ Activity logging

