# Fitur Template Pesan WhatsApp Modular

## Overview
Fitur ini memungkinkan Anda untuk membuat dan mengelola template pesan WhatsApp yang modular dan flexible. Setiap template dapat dikaitkan dengan jenis/service tertentu (Tagihan Baru, Pengingat, Konfirmasi Pembayaran, dll) sehingga sistem dapat menggunakan template yang tepat secara otomatis.

## Cara Mengakses

1. Login ke panel admin Filament
2. Buka menu **WhatsApp** → **Template Pesan**
3. Anda akan melihat daftar template yang tersedia

## Jenis-Jenis Template

Template pesan dibagi berdasarkan fungsi/service:

### 1. **Tagihan Baru** (`billing_new`)
- Dikirim otomatis saat tagihan bulanan dibuat
- Biasanya dikirim di awal bulan
- Mencakup invoice PDF

**Variabel:**
- `{customer_name}` - Nama pelanggan
- `{period}` - Periode tagihan (contoh: "Oktober 2025")
- `{invoice_number}` - Nomor invoice
- `{amount}` - Jumlah tagihan
- `{due_date}` - Tanggal jatuh tempo
- `{invoice_pdf}` - File PDF invoice (otomatis)

### 2. **Pengingat Tagihan (H-3)** (`billing_reminder_1`)
- Dikirim 3 hari sebelum jatuh tempo
- Mengingatkan customer untuk segera bayar

**Variabel:**
- `{customer_name}` - Nama pelanggan
- `{invoice_number}` - Nomor invoice
- `{amount}` - Jumlah tagihan
- `{due_date}` - Tanggal jatuh tempo
- `{days_left}` - Sisa hari sebelum jatuh tempo

### 3. **Pengingat Tagihan (H-1)** (`billing_reminder_2`)
- Dikirim 1 hari sebelum jatuh tempo
- Peringatan lebih urgent

**Variabel:** (sama dengan reminder 1)

### 4. **Pengingat Tagihan (Jatuh Tempo)** (`billing_reminder_3`)
- Dikirim pada hari jatuh tempo
- Peringatan paling urgent

**Variabel:**
- `{customer_name}` - Nama pelanggan
- `{invoice_number}` - Nomor invoice
- `{amount}` - Jumlah tagihan
- `{due_date}` - Tanggal jatuh tempo

### 5. **Tagihan Terlambat** (`billing_overdue`)
- Dikirim setelah melewati jatuh tempo
- Warning tentang potensi pemutusan layanan

**Variabel:**
- `{customer_name}` - Nama pelanggan
- `{invoice_number}` - Nomor invoice
- `{amount}` - Jumlah tagihan
- `{due_date}` - Tanggal jatuh tempo
- `{days_overdue}` - Jumlah hari keterlambatan

### 6. **Konfirmasi Pembayaran** (`billing_paid`)
- Dikirim otomatis saat pembayaran diterima
- Mengkonfirmasi pembayaran berhasil

**Variabel:**
- `{customer_name}` - Nama pelanggan
- `{invoice_number}` - Nomor invoice
- `{amount}` - Jumlah yang dibayar
- `{payment_date}` - Tanggal pembayaran

### 7. **Penangguhan Layanan** (`service_suspended`)
- Dikirim saat layanan ditangguhkan karena belum bayar
- (Future feature)

### 8. **Pengaktifan Kembali Layanan** (`service_reactivated`)
- Dikirim saat layanan diaktifkan kembali setelah pembayaran
- (Future feature)

### 9. **Custom / Lainnya** (`custom`)
- Template untuk keperluan custom/manual
- Tidak digunakan otomatis oleh sistem

## Cara Membuat Template Baru

### 1. Klik "Buat Baru"

### 2. Isi Form:

**Section "Informasi Template":**

- **Jenis Template** (Required)
  - Pilih dari dropdown
  - Menentukan kapan template akan digunakan
  - Contoh: "Tagihan Baru", "Pengingat Tagihan (H-3)"

- **Nama Template** (Required)
  - Nama deskriptif untuk template
  - Contoh: "Tagihan Baru - Formal", "Tagihan Baru - Friendly"

- **Kode Template** (Required)
  - Kode unik untuk identifikasi
  - Contoh: "billing.new.v2", "billing.new.friendly"
  - Harus unik di seluruh sistem

- **Urutan** (Optional)
  - Prioritas template jika ada multiple template dengan jenis yang sama
  - Angka kecil = prioritas tinggi (1, 2, 3...)
  - Default: 0

- **Deskripsi** (Optional)
  - Jelaskan kapan template ini digunakan
  - Contoh: "Template formal untuk corporate customers"

**Section "Konten Pesan":**

- **Isi Pesan** (Required)
  - Tulis isi pesan dengan variabel
  - Gunakan `{variable}` untuk data dinamis
  - Contoh:
    ```
    Yth. {customer_name},

    Tagihan internet Anda untuk periode {period} telah dibuat:
    No. Invoice: {invoice_number}
    Jumlah: Rp {amount}
    Jatuh Tempo: {due_date}

    Mohon melakukan pembayaran sebelum jatuh tempo.
    Terima kasih.
    ```

- **Variabel yang Tersedia** (Optional)
  - Daftar variabel yang dapat digunakan
  - Pisahkan dengan koma atau Tab
  - Contoh: `customer_name, amount, due_date`
  - Untuk referensi saja

**Section "Status":**

- **Aktif** (Toggle)
  - Hanya template aktif yang akan digunakan sistem
  - Toggle OFF untuk disable template tanpa menghapus

### 3. Klik "Simpan"

## Cara Kerja Sistem

### Priority System

Jika ada multiple template dengan jenis yang sama:
1. Sistem akan mencari template dengan `is_active = true`
2. Urutkan berdasarkan field `order` (ASC)
3. Pilih template pertama (urutan terkecil)

**Contoh:**
```
Template 1: Jenis = "Tagihan Baru", Urutan = 1, Aktif = ✓
Template 2: Jenis = "Tagihan Baru", Urutan = 2, Aktif = ✓
Template 3: Jenis = "Tagihan Baru", Urutan = 0, Aktif = ✗

→ Sistem akan gunakan Template 1 (urutan 1, aktif)
```

### Kapan Template Digunakan

| Jenis Template | Kapan Digunakan | Triggered By |
|---|---|---|
| Tagihan Baru | Saat generate tagihan bulanan | Command `bills:generate-monthly` atau manual dari admin |
| Pengingat H-3 | 3 hari sebelum jatuh tempo | Scheduled job (future feature) |
| Pengingat H-1 | 1 hari sebelum jatuh tempo | Scheduled job (future feature) |
| Pengingat Jatuh Tempo | Pada hari jatuh tempo | Scheduled job (future feature) |
| Tagihan Terlambat | Setelah melewati jatuh tempo | Scheduled job (future feature) |
| Konfirmasi Pembayaran | Saat payment status berubah jadi 'paid' | Payment observer |
| Custom | Manual dari admin | Broadcast atau manual send |

## Best Practices

### 1. Penamaan Template
- Gunakan nama yang jelas dan deskriptif
- Tambahkan version atau style jika ada variasi
- Contoh:
  - ✅ "Tagihan Baru - Formal v2"
  - ✅ "Pengingat H-3 - Friendly Tone"
  - ❌ "Template 1"
  - ❌ "Test"

### 2. Kode Template
- Ikuti convention: `[kategori].[jenis].[variant]`
- Contoh:
  - ✅ `billing.new.formal`
  - ✅ `billing.reminder.friendly`
  - ❌ `template1`
  - ❌ `new_template`

### 3. Urutan Priority
- Gunakan kelipatan 10 untuk flexible future insertion
- Contoh: 10, 20, 30... (bukan 1, 2, 3...)
- Jika perlu insert di tengah, bisa gunakan 15, 25, etc

### 4. Variabel
- Gunakan variabel yang sesuai dengan jenis template
- Check available variables untuk setiap jenis
- Test dengan data real sebelum deploy

### 5. Testing
- Buat template baru dengan status "Tidak Aktif" dulu
- Test dengan mengirim manual
- Aktifkan setelah yakin formatnya benar

## Multiple Template Strategy

Anda bisa membuat multiple versi template untuk satu jenis:

### Use Case 1: A/B Testing
```
Template A: "Tagihan Baru - Short" (order: 10, aktif: ✓)
Template B: "Tagihan Baru - Detailed" (order: 20, aktif: ✗)
```
- Gunakan Template A terlebih dahulu
- Monitor response rate
- Switch ke Template B dengan mengubah status

### Use Case 2: Seasonal / Event
```
Template Normal: "Tagihan Baru - Standard" (order: 20, aktif: ✓)
Template Promo: "Tagihan Baru - Ramadan Promo" (order: 10, aktif: ✗)
```
- Saat bulan Ramadan: Aktifkan Template Promo
- Template Promo akan digunakan karena order-nya lebih kecil
- Setelah Ramadan: Non-aktifkan Template Promo

### Use Case 3: Customer Segmentation (Future)
```
Template VIP: Untuk customer premium
Template Regular: Untuk customer standard
Template Past Due: Untuk customer yang sering telat
```

## Tips & Tricks

### 1. Formatting Pesan
WhatsApp mendukung formatting berikut:
- *Bold*: gunakan `*teks*`
- _Italic_: gunakan `_teks_`
- ~Strikethrough~: gunakan `~teks~`
- ```Monospace```: gunakan ` ```teks``` `

### 2. Emoji
Bisa gunakan emoji untuk membuat pesan lebih menarik:
```
🔔 Pengingat: Tagihan Anda akan jatuh tempo besok!
✅ Pembayaran Berhasil
⚠️ Tagihan Terlambat
```

### 3. Line Breaks
- Gunakan enter/newline untuk spasi antar paragraf
- Jangan terlalu banyak line breaks (max 2-3 baris)

### 4. Professional Tone
- Selalu gunakan "Yth." untuk sapaan formal
- Tutup dengan "Terima kasih"
- Hindari bahasa yang terlalu casual untuk billing

### 5. Call to Action
- Selalu sertakan aksi yang jelas
- Contoh: "Mohon segera melakukan pembayaran"
- Atau: "Hubungi kami di [nomor] jika ada pertanyaan"

## Troubleshooting

### Template tidak digunakan oleh sistem
**Solusi:**
1. Pastikan template `is_active = true`
2. Check `template_type` sudah sesuai
3. Jika ada multiple template, check field `order`
4. Clear cache: `php artisan cache:clear`

### Variabel tidak ter-replace
**Solusi:**
1. Pastikan format variabel benar: `{variable}` bukan `{{variable}}`
2. Check nama variabel sesuai dengan available variables
3. Case sensitive: `{customer_name}` bukan `{Customer_Name}`

### PDF tidak terkirim
**Solusi:**
1. Check setting WhatsApp API sudah benar
2. Check file invoice ter-generate dengan benar
3. Check logs di `storage/logs/laravel.log`

## Technical Details

### Database Structure
```sql
Table: whatsapp_templates
- id
- name (string)
- code (string, unique)
- template_type (string) // NEW!
- order (integer) // NEW!
- content (text)
- description (text)
- variables (json)
- is_active (boolean)
- created_at
- updated_at
```

### Model Methods
```php
// Get template by type
WhatsAppTemplate::findByType('billing_new');

// Get all templates by type
WhatsAppTemplate::getByType('billing_reminder_1');

// Get template types
WhatsAppTemplate::getTemplateTypes();
```

### Constants
```php
WhatsAppTemplate::TYPE_BILLING_NEW
WhatsAppTemplate::TYPE_BILLING_REMINDER_1
WhatsAppTemplate::TYPE_BILLING_REMINDER_2
WhatsAppTemplate::TYPE_BILLING_REMINDER_3
WhatsAppTemplate::TYPE_BILLING_OVERDUE
WhatsAppTemplate::TYPE_BILLING_PAID
WhatsAppTemplate::TYPE_SERVICE_SUSPENDED
WhatsAppTemplate::TYPE_SERVICE_REACTIVATED
WhatsAppTemplate::TYPE_CUSTOM
```

## Future Enhancements

- [ ] Scheduled reminders (H-3, H-1, H+0)
- [ ] Customer segmentation per template
- [ ] Template analytics (open rate, response rate)
- [ ] Rich media support (images, videos)
- [ ] Template preview dengan data sample
- [ ] Template versioning & rollback
- [ ] Bulk template import/export

---

**Dibuat:** 13 Oktober 2025
**Update Terakhir:** 13 Oktober 2025

