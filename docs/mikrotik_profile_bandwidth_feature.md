# Fitur Konfigurasi Bandwidth Detail pada Profil PPP MikroTik

## Deskripsi
Fitur ini menambahkan kemampuan untuk mengkonfigurasi bandwidth secara detail pada Profil PPP MikroTik, termasuk Max Limit, Burst Limit, Burst Threshold, dan Burst Time. Selain itu, fitur ini juga menyediakan dropdown untuk memilih IP Pool dan Parent Queue langsung dari MikroTik.

## Fitur Baru

### 1. Konfigurasi Bandwidth Detail

#### Max Limit
- **Max Limit Upload**: Batas maksimum kecepatan upload
- **Max Limit Download**: Batas maksimum kecepatan download
- Format: `10M`, `512k`, `1G`, dll.

#### Burst Limit
- **Burst Limit Upload**: Kecepatan burst maksimum untuk upload
- **Burst Limit Download**: Kecepatan burst maksimum untuk download
- Nilai ini biasanya lebih tinggi dari Max Limit
- Format: `20M`, `1G`, dll.

#### Burst Threshold
- **Burst Threshold Upload**: Threshold untuk mengaktifkan burst upload
- **Burst Threshold Download**: Threshold untuk mengaktifkan burst download
- Nilai ini biasanya lebih rendah dari Max Limit
- Format: `8M`, `512k`, dll.

#### Burst Time
- **Burst Time Upload**: Durasi burst untuk upload
- **Burst Time Download**: Durasi burst untuk download
- Format: `8s`, `10s`, `15s` (dalam detik)

### 2. Dropdown IP Pool
Field **Remote Address** sekarang memiliki dropdown yang menampilkan daftar IP Pool yang tersedia di MikroTik:
- Pilih IP Pool dari dropdown untuk menggunakan range IP yang sudah terkonfigurasi di MikroTik
- Atau pilih "Input Manual" untuk memasukkan range IP secara manual
- Format manual: `10.10.10.2-10.10.10.254`

### 3. Dropdown Parent Queue
Field **Parent Queue** sekarang memiliki dropdown yang menampilkan daftar Queue yang tersedia di MikroTik:
- Menampilkan Queue Tree dengan icon 🌳
- Menampilkan Queue Simple dengan icon 📊
- Default value: `none` (tanpa parent queue)

## Cara Kerja

### Auto-Generate Rate Limit
Sistem akan secara otomatis men-generate string `rate_limit` untuk MikroTik berdasarkan field-field yang diisi:

**Format Rate Limit MikroTik:**
```
rx-rate[/tx-rate] [rx-burst-rate[/tx-burst-rate] [rx-burst-threshold[/tx-burst-threshold] [rx-burst-time[/tx-burst-time]]]]
```

**Contoh:**
- Max Limit: 10M/10M
- Burst Limit: 20M/20M
- Burst Threshold: 8M/8M
- Burst Time: 8s/8s

**Hasil Rate Limit:**
```
10M/10M 20M/20M 8M/8M 8s/8s
```

### Sinkronisasi dengan MikroTik
Ketika profil di-sync ke MikroTik:
1. Observer `MikrotikProfileObserver` akan otomatis men-generate string `rate_limit` dari field bandwidth
2. Service `MikrotikProfileService` akan mengirim konfigurasi lengkap ke MikroTik
3. Field `rate_limit` di database akan diupdate dengan string yang di-generate

## Penggunaan

### Membuat Profil Baru
1. Pilih **Perangkat MikroTik** dari dropdown
2. Masukkan **Nama Profil**
3. Isi **Konfigurasi Bandwidth**:
   - Max Limit Upload/Download (wajib jika ingin menggunakan fitur bandwidth detail)
   - Burst Limit Upload/Download (opsional)
   - Burst Threshold Upload/Download (opsional, hanya jika Burst Limit diisi)
   - Burst Time Upload/Download (opsional, hanya jika Burst Threshold diisi)
4. Isi **Konfigurasi Alamat**:
   - Local Address (opsional)
   - Pilih IP Pool dari dropdown atau input manual
   - Pilih Parent Queue dari dropdown (default: none)
   - Shared Users (default: 1)
5. Klik **Simpan**
6. Klik **Sync ke MikroTik** untuk menerapkan konfigurasi

### Catatan Penting
- Jika **Max Limit Upload/Download** diisi, maka sistem akan menggunakan field bandwidth detail untuk generate rate limit
- Jika **Max Limit Upload/Download** kosong, maka sistem akan menggunakan field `rate_limit` lama (jika ada)
- Burst Limit harus lebih tinggi dari Max Limit
- Burst Threshold harus lebih rendah dari Max Limit
- Burst Time dalam format detik (contoh: 8s, 10s)

## File yang Dimodifikasi

1. **Migration**: `database/migrations/2025_10_22_000008_update_mikrotik_profiles_add_bandwidth_fields.php`
   - Menambahkan 8 field baru untuk konfigurasi bandwidth

2. **Model**: `app/Models/MikrotikProfile.php`
   - Menambahkan field baru ke `$fillable`

3. **Observer**: `app/Observers/MikrotikProfileObserver.php`
   - Auto-generate rate_limit dari field bandwidth saat creating/updating

4. **Service**: `app/Services/MikrotikApiService.php`
   - Method `getIpPools()`: Mengambil daftar IP Pool dari MikroTik
   - Method `getQueueTrees()`: Mengambil daftar Queue Tree dari MikroTik
   - Method `getQueueSimple()`: Mengambil daftar Queue Simple dari MikroTik

5. **Service**: `app/Services/MikrotikProfileService.php`
   - Method `buildRateLimitString()`: Men-generate string rate limit dari field bandwidth
   - Update method `createProfile()` dan `updateProfile()` untuk menggunakan rate limit yang di-generate

6. **Resource**: `app/Filament/Resources/MikrotikProfileResource.php`
   - Update form dengan field bandwidth detail
   - Dropdown untuk IP Pool dan Parent Queue

7. **Provider**: `app/Providers/AppServiceProvider.php`
   - Register observer `MikrotikProfileObserver`

## Contoh Konfigurasi

### Paket 10 Mbps dengan Burst
```
Max Limit Upload: 10M
Max Limit Download: 10M
Burst Limit Upload: 15M
Burst Limit Download: 15M
Burst Threshold Upload: 8M
Burst Threshold Download: 8M
Burst Time Upload: 10s
Burst Time Download: 10s
```

**Rate Limit yang di-generate:**
```
10M/10M 15M/15M 8M/8M 10s/10s
```

### Paket 20 Mbps tanpa Burst
```
Max Limit Upload: 20M
Max Limit Download: 20M
```

**Rate Limit yang di-generate:**
```
20M/20M
```

## Catatan Penting

### Parameter Shared Users
Parameter `shared-users` **TIDAK DIDUKUNG** di beberapa versi MikroTik RouterOS. Oleh karena itu, parameter ini tidak akan dikirim ke MikroTik saat sync, meskipun field ini tersedia di form dan database untuk keperluan dokumentasi internal.

Field `shared_users` tetap bisa diisi di form, namun nilai ini hanya disimpan di database aplikasi dan **tidak akan di-sync ke MikroTik**.

## Troubleshooting

### Dropdown IP Pool/Parent Queue kosong
- Pastikan perangkat MikroTik sudah dipilih
- Pastikan koneksi ke MikroTik aktif
- Cek apakah ada IP Pool atau Queue di MikroTik

### Rate Limit tidak sesuai
- Pastikan format bandwidth benar (contoh: 10M, 512k, 1G)
- Pastikan Burst Limit lebih tinggi dari Max Limit
- Pastikan Burst Threshold lebih rendah dari Max Limit

### Error "unknown parameter"
- Error ini biasanya terjadi jika ada parameter yang tidak didukung oleh versi MikroTik Anda
- Parameter `shared-users` sudah dinonaktifkan karena tidak didukung di beberapa versi
- Jika masih terjadi error, cek log aplikasi di `storage/logs/laravel.log`

### Error saat sync
- Cek koneksi ke MikroTik
- Pastikan profil dengan nama yang sama belum ada di MikroTik
- Jika parent queue diisi, pastikan queue tersebut sudah exist di MikroTik
- Cek log aplikasi untuk error detail

