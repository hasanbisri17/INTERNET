# Fitur Pengaturan Penerapan Template WhatsApp

## Overview
Fitur ini memungkinkan Anda untuk menentukan template mana yang akan digunakan oleh sistem untuk setiap jenis notifikasi/service tertentu. Misalnya, Anda bisa memilih template "Tagihan Baru - Formal" untuk tagihan baru, atau "Konfirmasi Pembayaran - Friendly" untuk konfirmasi pembayaran.

## Mengapa Fitur Ini Penting?

### Sebelum Fitur Ini:
- Sistem hanya bisa menggunakan 1 template untuk setiap jenis (berdasarkan code)
- Tidak flexible untuk A/B testing
- Sulit untuk switch antar template
- Perlu edit code untuk ganti template

### Setelah Fitur Ini:
✅ **Flexible** - Pilih template mana yang ingin digunakan  
✅ **Easy Switching** - Ganti template dengan 1 klik  
✅ **A/B Testing** - Buat multiple template dan test mana yang lebih efektif  
✅ **Seasonal** - Mudah ganti template untuk event khusus  
✅ **No Code** - Semua via UI admin, tanpa perlu edit code  

## Cara Mengakses

1. Login ke panel admin Filament
2. Buka menu **WhatsApp** → **Pengaturan Template**
3. Anda akan melihat halaman konfigurasi template

## Jenis Service yang Tersedia

### 1. **Template untuk Tagihan**

#### A. Tagihan Baru
- **Kapan digunakan:** Saat generate tagihan bulanan baru
- **Triggered by:** Command `bills:generate-monthly` atau manual dari admin
- **Variabel:** `{customer_name}`, `{period}`, `{invoice_number}`, `{amount}`, `{due_date}`, `{invoice_pdf}`

#### B. Pengingat Tagihan (H-3)
- **Kapan digunakan:** 3 hari sebelum jatuh tempo
- **Triggered by:** Scheduled job (future feature)
- **Variabel:** `{customer_name}`, `{invoice_number}`, `{amount}`, `{due_date}`, `{days_left}`

#### C. Pengingat Tagihan (H-1)
- **Kapan digunakan:** 1 hari sebelum jatuh tempo
- **Triggered by:** Scheduled job (future feature)
- **Variabel:** `{customer_name}`, `{invoice_number}`, `{amount}`, `{due_date}`, `{days_left}`

#### D. Pengingat Tagihan (Jatuh Tempo)
- **Kapan digunakan:** Pada hari jatuh tempo
- **Triggered by:** Scheduled job (future feature)
- **Variabel:** `{customer_name}`, `{invoice_number}`, `{amount}`, `{due_date}`

#### E. Tagihan Terlambat
- **Kapan digunakan:** Setelah melewati jatuh tempo
- **Triggered by:** Scheduled job (future feature)
- **Variabel:** `{customer_name}`, `{invoice_number}`, `{amount}`, `{due_date}`, `{days_overdue}`

### 2. **Template untuk Pembayaran**

#### Konfirmasi Pembayaran
- **Kapan digunakan:** Saat payment status berubah jadi 'paid'
- **Triggered by:** Payment observer/webhook
- **Variabel:** `{customer_name}`, `{invoice_number}`, `{amount}`, `{payment_date}`

### 3. **Template untuk Layanan**

#### A. Penangguhan Layanan
- **Kapan digunakan:** Saat layanan ditangguhkan (future feature)
- **Triggered by:** Manual atau automated job

#### B. Pengaktifan Kembali Layanan
- **Kapan digunakan:** Saat layanan diaktifkan kembali (future feature)
- **Triggered by:** Manual atau automated job

## Cara Konfigurasi Template

### Step 1: Buat Template (Optional)
Jika belum ada template yang sesuai:
1. Buka **WhatsApp** → **Template Pesan**
2. Klik "Buat Baru"
3. Pilih **Jenis Template** (misalnya: "Tagihan Baru")
4. Isi nama, konten, dan variabel
5. Set **Aktif** = ✓
6. Simpan

### Step 2: Konfigurasi Assignment
1. Buka **WhatsApp** → **Pengaturan Template**
2. Untuk setiap jenis service, pilih template dari dropdown
3. Klik **"Simpan Pengaturan"**
4. Done! ✅

### Step 3: Test
1. Trigger action yang menggunakan template tersebut
2. Contoh: Generate tagihan baru via menu Payment
3. Check WhatsApp customer, apakah pesan terkirim dengan template yang benar

## Cara Kerja Sistem

### Priority System (3 Level Fallback):

```
1. CONFIGURED TEMPLATE (dari Pengaturan)
   ↓ (jika tidak ada)
2. TEMPLATE BY TYPE (first active template dengan type yang sesuai)
   ↓ (jika tidak ada)
3. LEGACY CODE (template lama berdasarkan code)
```

### Contoh Flow:

**Scenario:** User generate tagihan baru

```
1. Sistem cek: Apakah ada template yang dikonfigurasi di "Pengaturan Template"?
   → YA: Gunakan template ID #5 "Tagihan Baru - Formal v2"
   → TIDAK: Lanjut ke step 2

2. Sistem cek: Apakah ada template dengan type "billing_new" yang aktif?
   → YA: Gunakan template pertama (urutan terkecil)
   → TIDAK: Lanjut ke step 3

3. Sistem cek: Apakah ada template dengan code "billing.new"?
   → YA: Gunakan template legacy
   → TIDAK: ERROR - No template found
```

## Use Cases

### Use Case 1: A/B Testing Template

**Skenario:** Anda ingin test 2 versi template untuk tagihan baru

**Langkah:**
1. Buat 2 template:
   - Template A: "Tagihan Baru - Short" (pendek & to the point)
   - Template B: "Tagihan Baru - Detailed" (lengkap & detail)

2. Week 1-2: Set Template A di Pengaturan
   - Monitor: Response rate, payment rate, dll

3. Week 3-4: Switch ke Template B di Pengaturan
   - Monitor: Bandingkan hasilnya

4. Pilih template yang lebih efektif sebagai default

**Keuntungan:**
- Ganti template hanya dengan 1 klik
- Tidak perlu edit code
- Data bisa dibandingkan

### Use Case 2: Seasonal/Event Template

**Skenario:** Saat Ramadan, Anda ingin pesan yang lebih friendly

**Langkah:**
1. Buat template khusus:
   - "Tagihan Baru - Ramadan Special"
   - Konten: Tambahkan ucapan Ramadan, tone lebih warm

2. Menjelang Ramadan: Switch ke template Ramadan di Pengaturan

3. Setelah Ramadan: Switch kembali ke template normal

**Keuntungan:**
- Mudah switch seasonal template
- Customer feel more appreciated
- Professional & timely

### Use Case 3: Customer Segment (Future Enhancement)

**Skenario:** Template berbeda untuk customer VIP vs Regular

**Langkah (future feature):**
1. Buat 2 template untuk tagihan baru:
   - "Tagihan Baru - VIP" (lebih exclusive, personal)
   - "Tagihan Baru - Regular" (standard, formal)

2. Set rule: If customer_type = VIP, use VIP template

**Keuntungan:**
- Personalized messaging
- Better customer experience
- Higher engagement

## Dashboard Statistics

Di halaman Pengaturan Template, Anda bisa lihat:

### 1. Total Template
- Jumlah semua template yang ada di sistem

### 2. Template Aktif
- Jumlah template yang status = aktif
- Hanya template aktif yang bisa dipilih

### 3. Template Terkonfigurasi
- Format: "X / 8"
- X = Jumlah service yang sudah dikonfigurasi templatenya
- 8 = Total service yang available

**Contoh:**
- "5 / 8" = 5 service sudah dikonfigurasi, 3 belum

## Best Practices

### 1. Selalu Configure Template
- Jangan rely pada fallback
- Set template secara explicit untuk setiap service
- Fallback hanya sebagai safety net

### 2. Test Setelah Konfigurasi
- Setelah set template, test dengan data real
- Pastikan variabel ter-replace dengan benar
- Check format pesan di WhatsApp

### 3. Backup Template
- Buat backup template sebelum ganti
- Set status "Tidak Aktif" untuk template lama
- Jangan langsung delete

### 4. Monitor Performance
- Track response rate setelah ganti template
- Monitor customer feedback
- Adjust based on data

### 5. Dokumentasi Internal
- Catat reason kenapa ganti template
- Track A/B testing results
- Share insights dengan tim

## Troubleshooting

### Template tidak terkirim
**Solusi:**
1. Check apakah template yang dipilih masih aktif
2. Verify di logs: `storage/logs/laravel.log`
3. Pastikan variabel di template sudah benar
4. Check WhatsApp API connection

### Pesan terkirim tapi pakai template lama
**Solusi:**
1. Clear cache: `php artisan optimize:clear`
2. Check konfigurasi di Pengaturan Template
3. Pastikan template yang dipilih ID-nya benar
4. Refresh halaman admin

### Dropdown template kosong
**Solusi:**
1. Check apakah ada template dengan jenis yang sesuai
2. Pastikan template status = aktif
3. Buat template baru jika belum ada
4. Set jenis template dengan benar

### Fallback ke template lama
**Solusi:**
1. Pastikan template sudah dikonfigurasi di Pengaturan
2. Check template masih aktif
3. Verify template ID di database (table settings)
4. Check logs untuk lihat template mana yang digunakan

## Technical Details

### Database Storage
Template assignment disimpan di table `settings`:

```sql
key: whatsapp_template_billing_new
value: 5  (template ID)

key: whatsapp_template_billing_paid
value: 8  (template ID)
```

### Setting Keys
```
whatsapp_template_billing_new
whatsapp_template_billing_reminder_1
whatsapp_template_billing_reminder_2
whatsapp_template_billing_reminder_3
whatsapp_template_billing_overdue
whatsapp_template_billing_paid
whatsapp_template_service_suspended
whatsapp_template_service_reactivated
```

### Code Flow
```php
// 1. Get template for service
$template = $this->getTemplateForService('new');

// 2. Check configured template (from settings)
$templateId = Setting::get('whatsapp_template_billing_new');

// 3. Fallback to type-based lookup
WhatsAppTemplate::findByType('billing_new');

// 4. Last fallback to legacy code
WhatsAppTemplate::findByCode('billing.new');
```

### Logging
Sistem akan log template mana yang digunakan:

```
[INFO] Using configured template from settings
  service_type: new
  template_id: 5
  template_name: "Tagihan Baru - Formal v2"
```

## API / Integration

Jika Anda ingin set template via code:

```php
use App\Models\Setting;

// Set template for billing new
Setting::set('whatsapp_template_billing_new', 5);

// Remove assignment (use fallback)
Setting::where('key', 'whatsapp_template_billing_new')->delete();

// Get current assignment
$templateId = Setting::get('whatsapp_template_billing_new');
```

## Future Enhancements

- [ ] Preview template before assign
- [ ] Template analytics per service
- [ ] Schedule template switch (auto switch on specific date)
- [ ] Customer segment-based template
- [ ] Template performance comparison
- [ ] Bulk template assignment
- [ ] Template import/export

## Related Documentation

- [Modular WhatsApp Template](./modular_whatsapp_template_feature.md)
- [Invoice Settings](./invoice_settings_feature.md)
- [WhatsApp Integration](./whatsapp_integration.md)

---

**Dibuat:** 13 Oktober 2025
**Update Terakhir:** 13 Oktober 2025

