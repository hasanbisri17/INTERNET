# Troubleshooting: WhatsApp Tidak Terkirim (Error 401 Unauthorized)

## 🔍 Gejala Masalah

Setelah generate tagihan atau tambah tagihan baru, pesan WhatsApp **TIDAK terkirim** ke customer. Ketika dicek di database atau log:

- Status pesan: `failed`
- Error message: `401 Unauthorized - {"message":"Unauthorized","statusCode":401}`
- Response: `"All sending methods failed. Check WAHA API configuration."`

## 🎯 Penyebab Masalah

**WAHA API memerlukan API Token untuk autentikasi**, tapi API Token di pengaturan WhatsApp **kosong** atau **tidak valid**.

Tanpa API Token yang valid, setiap request ke WAHA API akan ditolak dengan error `401 Unauthorized`.

## ✅ Solusi

### Langkah 1: Dapatkan API Token dari WAHA

1. **Buka Dashboard WAHA** 
   - Akses URL WAHA Anda (contoh: `https://waha-pj8tw4c4otz1.wax.biz.id`)
   
2. **Login ke Dashboard WAHA**

3. **Masuk ke Menu Security/Settings**
   - Cari menu **Settings** → **Security**
   - Atau langsung ke **API Configuration**

4. **Copy API Token**
   - Akan ada field yang menampilkan **API Token** atau **API Key**
   - Copy token tersebut

### Langkah 2: Masukkan API Token ke Aplikasi

1. **Buka aplikasi Anda**

2. **Masuk ke menu WhatsApp**
   - Klik menu **WhatsApp** di sidebar
   - Pilih **Pengaturan WhatsApp**

3. **Edit Pengaturan WhatsApp**
   - Klik tombol **Edit** pada pengaturan yang aktif
   
4. **Isi API Token**
   - Paste API Token yang sudah dicopy ke field **API Token (X-API-Key)**
   - Field ini **WAJIB diisi** dan tidak boleh kosong

5. **Simpan Pengaturan**
   - Klik tombol **Simpan**

### Langkah 3: Test Koneksi WhatsApp

1. **Test Koneksi**
   - Setelah menyimpan, klik tombol **Test Koneksi**
   - Masukkan nomor WhatsApp yang aktif (format: 621234567890)
   - Klik **Test**

2. **Verifikasi**
   - Jika sukses, Anda akan menerima pesan test di WhatsApp
   - Jika masih error, periksa kembali API Token dan API URL

### Langkah 4: Coba Generate Tagihan Lagi

1. **Generate Tagihan Baru**
   - Masuk ke menu **Tagihan**
   - Klik **Generate Tagihan Bulanan** atau **Buat Tagihan**

2. **Periksa Status Pengiriman**
   - Masuk ke menu **WhatsApp** → **Riwayat Pesan**
   - Periksa status pesan terbaru
   - Status harus `sent` (terkirim), bukan `failed`

## 🔧 Perbaikan Kode yang Sudah Dilakukan

### 1. Validasi API Token di WhatsAppService

File: `app/Services/WhatsAppService.php`

```php
public function __construct(?WhatsAppSetting $settings = null)
{
    $this->settings = $settings ?? WhatsAppSetting::getCurrentSettings();
    
    if (!$this->settings) {
        throw new \Exception('WhatsApp settings not configured. Please configure WhatsApp settings first in WhatsApp → Pengaturan WhatsApp menu.');
    }

    // Validate API token is not empty (WAHA API requires authentication)
    if (empty($this->settings->api_token)) {
        throw new \Exception('WhatsApp API Token is required. Please set your WAHA API token in WhatsApp → Pengaturan WhatsApp menu. Error: API Token tidak boleh kosong.');
    }

    // Add API token to headers (required for WAHA API)
    $headers['X-API-Key'] = $this->settings->api_token;
    
    // ... rest of code
}
```

**Perubahan:**
- ✅ Menambahkan validasi untuk memastikan `api_token` tidak kosong
- ✅ Memberikan error message yang jelas jika token kosong
- ✅ Memastikan token selalu ditambahkan ke header request

### 2. Update Form Pengaturan WhatsApp

File: `app/Filament/Resources/WhatsAppSettingResource.php`

```php
Forms\Components\Section::make('⚠️ Penting: API Token Wajib Diisi')
    ->description('WAHA API memerlukan API Token untuk autentikasi. Tanpa API Token, pengiriman WhatsApp akan gagal dengan error 401 Unauthorized.')
    ->schema([
        Forms\Components\Placeholder::make('token_info')
            ->content(/* Informasi cara mendapatkan API Token */),
        Forms\Components\TextInput::make('api_token')
            ->label('API Token (X-API-Key)')
            ->required()
            ->placeholder('Masukkan API Token dari WAHA')
            ->helperText('Token autentikasi untuk WAHA API. Field ini WAJIB diisi!'),
    ])
```

**Perubahan:**
- ✅ Menambahkan section dengan peringatan tentang pentingnya API Token
- ✅ Menambahkan instruksi cara mendapatkan API Token dari WAHA
- ✅ Menambahkan helper text yang jelas
- ✅ Menambahkan placeholder yang informatif

## 📋 Checklist Verifikasi

Setelah melakukan perbaikan, pastikan:

- [ ] API Token sudah diisi di Pengaturan WhatsApp
- [ ] API Token tidak kosong dan valid
- [ ] API URL benar (contoh: `https://waha-pj8tw4c4otz1.wax.biz.id`)
- [ ] Session name benar (biasanya `default`)
- [ ] Test koneksi berhasil
- [ ] Generate tagihan berhasil mengirim WhatsApp
- [ ] Status pesan di Riwayat Pesan adalah `sent`, bukan `failed`

## 🐛 Debug Jika Masih Gagal

### 1. Cek Log Laravel

```bash
tail -f storage/logs/laravel.log | grep -i "whatsapp\|waha"
```

Cari error seperti:
- `401 Unauthorized` - API Token salah atau kosong
- `404 Not Found` - API URL salah
- `Connection refused` - WAHA server tidak bisa diakses
- `SSL verification failed` - Masalah SSL (sudah di-handle dengan `verify: false`)

### 2. Cek Database

```sql
-- Cek pengaturan WhatsApp
SELECT * FROM whatsapp_settings WHERE is_active = 1;

-- Cek pesan WhatsApp terakhir
SELECT id, customer_id, payment_id, status, response, sent_at 
FROM whats_app_messages 
ORDER BY id DESC 
LIMIT 10;

-- Cek setting template
SELECT * FROM settings WHERE key LIKE 'whatsapp_template%';
```

### 3. Test Manual dengan Tinker

```bash
php artisan tinker
```

```php
// Test WhatsApp settings
$settings = App\Models\WhatsAppSetting::getCurrentSettings();
dd($settings->toArray());

// Test template
$template = App\Models\WhatsAppTemplate::find(1);
dd($template->toArray());

// Test sending (ganti dengan nomor Anda)
$whatsapp = new App\Services\WhatsAppService();
$result = $whatsapp->sendMessage('621234567890', 'Test message');
dd($result);
```

## 💡 Tips

1. **Jangan hapus atau kosongkan API Token** setelah diisi dengan benar
2. **Backup API Token** di tempat yang aman
3. **Test koneksi secara berkala** untuk memastikan API masih aktif
4. **Periksa quota/limit** di WAHA jika tiba-tiba gagal setelah sebelumnya berhasil
5. **Monitor log** untuk mendeteksi masalah lebih awal

## 📚 Referensi

- [WAHA API Documentation](https://waha.devlike.pro/)
- [Dokumentasi WhatsApp Template System](./modular_whatsapp_template_feature.md)
- [Dokumentasi Payment Reminder System](./payment_reminder_system.md)

## ❓ FAQ

**Q: Apakah API Token bisa kosong?**  
A: **TIDAK**. WAHA API memerlukan API Token untuk autentikasi. Jika kosong, semua request akan gagal dengan error 401.

**Q: Dimana mendapatkan API Token?**  
A: Dari dashboard WAHA Anda, menu Settings → Security atau API Configuration.

**Q: Apakah API Token bisa diganti?**  
A: Ya, tapi pastikan update juga di aplikasi ini setelah mengganti token di WAHA.

**Q: Kenapa sebelumnya bisa kirim WhatsApp tanpa API Token?**  
A: Kemungkinan WAHA belum mengaktifkan autentikasi, atau ada perubahan konfigurasi di WAHA.

**Q: Apakah ini bug dari fitur baru?**  
A: **BUKAN**. Ini masalah konfigurasi. Fitur-fitur baru yang ditambahkan tidak mengubah cara kerja autentikasi WhatsApp. Sistem sudah mencoba mengirim WhatsApp dengan benar, tapi ditolak karena tidak ada API Token.

---

**Terakhir diperbarui:** 2025-10-13  
**Penulis:** AI Assistant  
**Status:** Resolved ✅

