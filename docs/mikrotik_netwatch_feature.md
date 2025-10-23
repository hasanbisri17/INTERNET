# MikroTik Netwatch Feature

## Deskripsi
Fitur Netwatch memungkinkan Anda untuk monitoring host/IP address dari sistem dan melakukan sinkronisasi dengan MikroTik device. Netwatch akan melakukan ping ke host secara berkala dan dapat menjalankan script ketika host up atau down.

## Fitur Utama

### 1. **Sync dari MikroTik**
- Mengambil semua data netwatch dari MikroTik Tools => Netwatch
- Menyimpan ke database lokal untuk tracking
- Mendukung sync dari multiple device MikroTik

### 2. **Create Netwatch**
- Membuat netwatch baru di database
- Otomatis create di MikroTik device
- Validasi parameter sebelum dikirim ke MikroTik

### 3. **Edit Netwatch**
- Update netwatch di database
- Otomatis update di MikroTik device
- Support untuk mengubah host, interval, timeout, script

### 4. **Delete Netwatch**
- Hapus netwatch dari database
- Otomatis hapus dari MikroTik device

### 5. **Enable/Disable Netwatch**
- Toggle status netwatch tanpa menghapus
- Sinkronisasi status dengan MikroTik

## Field yang Tersedia

| Field | Tipe | Deskripsi | Default |
|-------|------|-----------|---------|
| **MikroTik Device** | Select | Device MikroTik yang akan dimonitor | - |
| **Host** | Text | IP address atau hostname yang dimonitor | - |
| **Interval** | Text | Waktu interval checking (format: HH:MM:SS) | 00:01:00 |
| **Timeout** | Text | Timeout untuk ping | 1000ms |
| **Up Script** | Textarea | Script yang dijalankan saat host UP | - |
| **Down Script** | Textarea | Script yang dijalankan saat host DOWN | - |
| **Comment** | Textarea | Catatan/keterangan | - |
| **Disabled** | Toggle | Status aktif/nonaktif | false |

### Field Auto-Generated

| Field | Deskripsi |
|-------|-----------|
| **Status** | Status host saat ini (up/down/unknown) - dari MikroTik |
| **Since** | Sejak kapan dalam status saat ini - dari MikroTik |
| **Netwatch ID** | ID internal MikroTik (.id) |
| **Is Synced** | Status sinkronisasi dengan MikroTik |
| **Last Synced At** | Waktu terakhir sync |

## Cara Menggunakan

### 1. **Sync Data dari MikroTik (Pertama Kali)**

**Via UI:**
1. Buka menu **MikroTik** → **Netwatch**
2. Klik tombol **"Sync dari MikroTik"** di header
3. Konfirmasi sync
4. Sistem akan mengambil semua netwatch dari semua device MikroTik aktif

**Via Command:**
```bash
php artisan mikrotik:sync-netwatch
```

### 2. **Membuat Netwatch Baru**

1. Klik tombol **"New"**
2. Isi form:
   - **MikroTik Device**: Pilih device MikroTik
   - **Host/IP Address**: Masukkan IP atau hostname (contoh: `8.8.8.8` atau `google.com`)
   - **Interval**: Waktu checking (default: `00:01:00` = 1 menit)
   - **Timeout**: Timeout ping (default: `1000ms`)
   - **Up Script** (optional): Script saat host UP
     ```
     :log info "Host $host is UP"
     ```
   - **Down Script** (optional): Script saat host DOWN
     ```
     :log warning "Host $host is DOWN"
     /tool e-mail send to="admin@example.com" subject="Host Down" body="$host is down"
     ```
   - **Comment** (optional): Catatan
3. Klik **"Create"**
4. Netwatch akan otomatis dibuat di MikroTik

### 3. **Edit Netwatch**

1. Klik tombol **"Aksi"** → **"Edit"** pada netwatch yang ingin diubah
2. Ubah data yang diperlukan
3. Klik **"Save"**
4. Perubahan akan otomatis sync ke MikroTik

### 4. **Enable/Disable Netwatch**

1. Klik tombol **"Aksi"** → **"Enable"** atau **"Disable"**
2. Status akan langsung berubah di MikroTik

### 5. **Hapus Netwatch**

1. Klik tombol **"Aksi"** → **"Delete"**
2. Konfirmasi penghapusan
3. Netwatch akan dihapus dari database dan MikroTik

## Status Badge

| Badge | Warna | Keterangan |
|-------|-------|------------|
| 🟢 Up | Hijau | Host dalam kondisi UP (dapat di-ping) |
| 🔴 Down | Merah | Host dalam kondisi DOWN (tidak dapat di-ping) |
| 🟡 Unknown | Kuning | Status belum diketahui atau belum sempat check |

## Use Cases

### 1. **Monitor Gateway/Router**
```
Host: 192.168.1.1
Interval: 00:01:00
Timeout: 1000ms
Comment: Monitor Gateway Utama
Down Script: :log error "Gateway DOWN! Check immediately!"
```

### 2. **Monitor Server Penting**
```
Host: server.example.com
Interval: 00:00:30 (30 detik)
Timeout: 2000ms
Comment: Monitor Web Server Production
Down Script: 
:log error "Web Server DOWN"
/tool e-mail send to="admin@example.com" subject="Server Down Alert" body="Web server is not responding"
```

### 3. **Monitor DNS Server**
```
Host: 8.8.8.8
Interval: 00:02:00
Timeout: 500ms
Comment: Monitor Google DNS
Down Script: :log warning "Google DNS unreachable - check internet connection"
```

### 4. **Monitor Multiple Hosts dengan Script**
```
Host: 192.168.1.10
Up Script:
:log info "Server A is UP"
:global serverAStatus "up"

Down Script:
:log error "Server A is DOWN"
:global serverAStatus "down"
/system script run backup-failover
```

## Kolom Tabel

| Kolom | Deskripsi | Searchable | Sortable | Toggleable |
|-------|-----------|------------|----------|------------|
| Device | Nama device MikroTik | ✅ | ✅ | ✅ |
| Host | IP/hostname yang dimonitor | ✅ | ✅ | ❌ |
| Status | Status host (Up/Down/Unknown) | ❌ | ✅ | ❌ |
| Since | Sejak kapan dalam status ini | ❌ | ✅ | ✅ |
| Interval | Interval checking | ❌ | ❌ | ✅ (Hidden) |
| Timeout | Timeout ping | ❌ | ❌ | ✅ (Hidden) |
| Comment | Keterangan | ✅ | ❌ | ✅ |
| Status Enable/Disable | Icon status aktif | ❌ | ✅ | ✅ |
| Synced | Icon status sync | ❌ | ✅ | ✅ (Hidden) |
| Last Synced | Waktu terakhir sync | ❌ | ✅ | ✅ (Hidden) |

## Filter

1. **Device**: Filter berdasarkan MikroTik device
2. **Status**: Filter berdasarkan status (Up/Down/Unknown)
3. **Disabled**: Filter enabled/disabled only

## Actions

### Table Actions
1. **Enable**: Aktifkan netwatch yang disabled
2. **Disable**: Nonaktifkan netwatch yang enabled
3. **Edit**: Edit netwatch
4. **Delete**: Hapus netwatch

### Header Actions
1. **Sync dari MikroTik**: Sync semua netwatch dari MikroTik
2. **New**: Buat netwatch baru

## Auto-Sync Behavior

**CATATAN PENTING:** Untuk menghindari infinite loop seperti pada IP Bindings, fitur Netwatch menggunakan **manual sync trigger** pada create/edit:

- ✅ **Create**: Otomatis create di MikroTik setelah save di database
- ✅ **Update**: Otomatis update di MikroTik setelah save di database
- ✅ **Delete**: Otomatis delete di MikroTik setelah delete di database
- ✅ **Enable/Disable**: Langsung update status di MikroTik
- ❌ **NO OBSERVER**: Tidak ada observer yang auto-trigger pada model update
- ✅ **Sync FROM MikroTik**: Menggunakan `withoutEvents()` untuk prevent loop

## Command Line Interface

### Sync Netwatch
```bash
php artisan mikrotik:sync-netwatch
```

Output contoh:
```
Starting Netwatch sync from MikroTik...
✅ Berhasil sync 5 netwatch entries

⚠️  Errors:
  - Device Office: Connection timeout
```

## Troubleshooting

### 1. **Netwatch tidak muncul setelah create**
**Solusi:**
- Klik tombol "Sync dari MikroTik" untuk refresh data
- Cek di MikroTik apakah netwatch sudah terbuat
- Cek log Laravel: `storage/logs/laravel.log`

### 2. **Error "Netwatch ID tidak ditemukan"**
**Penyebab:** Netwatch belum di-sync dari MikroTik atau ID hilang

**Solusi:**
```bash
php artisan mikrotik:sync-netwatch
```

### 3. **Status selalu "unknown"**
**Penyebab:** Host belum sempat di-check atau interval terlalu lama

**Solusi:**
- Tunggu beberapa saat sesuai interval
- Sync ulang dari MikroTik untuk update status
- Pastikan host bisa di-ping dari MikroTik

### 4. **Script tidak jalan saat host down**
**Penyebab:** 
- Syntax script salah
- Permission script di MikroTik

**Solusi:**
- Test script di MikroTik Terminal terlebih dahulu
- Pastikan policy script di MikroTik sudah sesuai
- Cek log MikroTik untuk error script

### 5. **Connection timeout saat sync**
**Solusi:**
- Pastikan MikroTik device aktif dan online
- Cek IP, port, username, password di settings device
- Pastikan API service di MikroTik aktif
- Test koneksi manual ke MikroTik

## Best Practices

### 1. **Interval Setting**
- **Critical hosts**: `00:00:30` (30 detik)
- **Important hosts**: `00:01:00` (1 menit)
- **Normal monitoring**: `00:05:00` (5 menit)
- **Low priority**: `00:15:00` (15 menit)

### 2. **Timeout Setting**
- **LAN hosts**: `500ms` - `1000ms`
- **Internet hosts**: `2000ms` - `5000ms`
- **Slow connection**: `5000ms` - `10000ms`

### 3. **Script Guidelines**
- Keep scripts simple dan fast
- Avoid infinite loops dalam script
- Use `:log` untuk debugging
- Test script di Terminal sebelum pakai di Netwatch

### 4. **Monitoring Strategy**
```
Priority 1: Gateway/Router (30s interval)
Priority 2: Critical Servers (1m interval)
Priority 3: Services (5m interval)
Priority 4: External monitoring (10m interval)
```

### 5. **Naming Convention**
```
Comment format: [TYPE] Description
Examples:
- [GATEWAY] Router Utama Kantor
- [SERVER] Web Server Production
- [DNS] Cloudflare DNS
- [INTERNET] Google DNS Check
```

## Changelog

### Version 1.0.0 (22 Oktober 2025)
- ✅ Initial release
- ✅ Sync netwatch dari MikroTik
- ✅ Create/Edit/Delete netwatch
- ✅ Enable/Disable toggle
- ✅ Status monitoring (Up/Down/Unknown)
- ✅ Support up-script dan down-script
- ✅ Multi-device support
- ✅ Activity logging
- ✅ Manual sync trigger (no observer loop)

## Referensi MikroTik

- [MikroTik Netwatch Manual](https://wiki.mikrotik.com/wiki/Manual:Tools/Netwatch)
- [MikroTik Scripting](https://wiki.mikrotik.com/wiki/Manual:Scripting)
- [MikroTik API Documentation](https://wiki.mikrotik.com/wiki/Manual:API)

## Tips & Tricks

### 1. **Email Alert on Host Down**
```routeros
:local emailTo "admin@example.com"
:local hostName "Web Server"
:local hostIP [/tool netwatch get [find host=192.168.1.10] host]

/tool e-mail send \
  to=$emailTo \
  subject="[ALERT] $hostName is DOWN" \
  body="Host: $hostName ($hostIP) is not responding. Please check immediately."
```

### 2. **Telegram Notification**
```routeros
:local botToken "YOUR_BOT_TOKEN"
:local chatId "YOUR_CHAT_ID"
:local message "Host%20192.168.1.10%20is%20DOWN"

/tool fetch url="https://api.telegram.org/bot$botToken/sendMessage?chat_id=$chatId&text=$message" mode=https
```

### 3. **Failover Script**
```routeros
# When main gateway down, activate backup
:log warning "Main gateway DOWN - activating backup"
/ip route set [find comment="main"] disabled=yes
/ip route set [find comment="backup"] disabled=no
```

### 4. **Auto Recovery**
```routeros
# When main gateway up again, switch back
:log info "Main gateway UP - switching back"
/ip route set [find comment="backup"] disabled=yes
/ip route set [find comment="main"] disabled=no
```

## Keamanan

⚠️ **PENTING:**
- Script netwatch berjalan dengan privilege penuh di MikroTik
- Jangan masukkan script dari sumber yang tidak dipercaya
- Test script di environment testing terlebih dahulu
- Backup konfigurasi MikroTik sebelum deploy script kompleks
- Gunakan comment untuk dokumentasi script

## Support

Untuk pertanyaan atau issue terkait fitur Netwatch:
1. Cek dokumentasi ini terlebih dahulu
2. Cek log aplikasi: `storage/logs/laravel.log`
3. Cek log MikroTik untuk error di sisi device
4. Test koneksi API MikroTik manual

---

**Last Updated:** 22 Oktober 2025
**Version:** 1.0.0

