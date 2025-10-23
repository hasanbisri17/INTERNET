# 📱 WhatsApp Notification untuk Suspend & Unsuspend Customer

## 📋 Deskripsi

Sistem sekarang mengirimkan **WhatsApp notification** ke customer secara otomatis saat:
1. ⛔ **Layanan di-suspend** (pembayaran terlambat)
2. ✅ **Layanan diaktifkan kembali** (setelah pembayaran diterima)

Kedua notifikasi ini menggunakan **template system yang configurable** sehingga Anda dapat:
- ✏️ Membuat dan mengedit template pesan sesuai kebutuhan
- 🎯 Memilih template mana yang ingin digunakan sebagai default
- 🔄 Menggunakan variabel dinamis dalam template

---

## 🎯 Flow Notifikasi

### 1. **Suspend Customer (Auto/Manual)**

**Trigger:**
- ⏰ **Auto Suspend**: Scheduler tanggal 26 setiap bulan (`php artisan suspend:auto-ip-binding`)
- 👤 **Manual Suspend**: Admin suspend customer melalui Filament

**Flow:**
```
Customer overdue payment
↓
Suspend via IP Binding (type: bypassed → regular)
↓
Update customer status to 'suspended'
↓
Send WhatsApp notification (template configurable) ✉️
↓
Log activity & admin notification
```

**Template Default untuk Suspend:**
```
Yth. {customer_name},

⛔ Layanan internet Anda telah dinonaktifkan karena pembayaran belum diterima hingga tanggal 25.

📅 Due Date: {due_date}
💰 Total Tagihan: Rp {amount}

Silakan segera melakukan pembayaran untuk mengaktifkan kembali layanan Anda.

Terima kasih.
```

**Variabel yang Tersedia:**
- `{customer_name}` - Nama customer
- `{due_date}` - Tanggal jatuh tempo (format: 23 Oct 2025)
- `{amount}` - Total tagihan (format: 100.000)

---

### 2. **Unsuspend Customer (Auto/Manual)**

**Trigger:**
- 💰 **Auto Unsuspend**: Saat payment status berubah ke 'paid' atau 'confirmed' (via `PaymentObserver`)
- 👤 **Manual Unsuspend**: Admin unsuspend customer melalui Filament

**Flow:**
```
Payment received/confirmed
↓
Unsuspend via IP Binding (type: regular → bypassed)
↓
Update customer status to 'active'
↓
Send WhatsApp notification (template configurable) ✉️
↓
Log activity
```

**Template Default untuk Unsuspend:**
```
Yth. {customer_name},

✅ Layanan internet Anda telah diaktifkan kembali.

Terima kasih atas pembayaran Anda. Selamat menikmati layanan internet kami.

Jika ada kendala, silakan hubungi kami.

Terima kasih.
```

**Variabel yang Tersedia:**
- `{customer_name}` - Nama customer

---

## 🛠️ Konfigurasi Template

### 1. **Buat/Edit Template**

**Langkah:**
1. Login ke admin panel
2. Buka menu **"WhatsApp" → "Template Pesan"**
3. Klik **"Tambah Template"**
4. Isi form:
   - **Nama**: Nama template (misal: "Suspend - Friendly Reminder")
   - **Kode**: Kode unik (misal: "suspend.friendly")
   - **Tipe Template**: Pilih **"Penangguhan Layanan"** atau **"Pengaktifan Kembali Layanan"**
   - **Konten**: Tulis isi pesan (bisa pakai variabel)
   - **Urutan**: Urutan prioritas (1, 2, 3, dst)
   - **Status**: Aktif

**Contoh Template Custom untuk Suspend:**
```
Halo {customer_name} 👋

Maaf, layanan internet Anda sementara kami nonaktifkan karena tagihan bulan ini belum kami terima.

📋 Tagihan: Rp {amount}
📅 Jatuh tempo: {due_date}

Yuk segera lunasi agar bisa internetan lagi! 🚀

Info pembayaran hubungi: 0812-xxxx-xxxx
```

### 2. **Pilih Template Default**

**Langkah:**
1. Buka menu **"Pengaturan Sistem"**
2. Klik tab **"Template WhatsApp"**
3. Pada section **"Template untuk Layanan"**:
   - **Penangguhan Layanan**: Pilih template yang ingin digunakan untuk notifikasi suspend
   - **Pengaktifan Kembali Layanan**: Pilih template yang ingin digunakan untuk notifikasi unsuspend
4. Klik **"Simpan Pengaturan"**

**Screenshot Reference:**
```
┌────────────────────────────────────────────┐
│ Template untuk Layanan                     │
├────────────────────────────────────────────┤
│ Penangguhan Layanan                        │
│ [Dropdown: Pilih template]                 │
│ Template yang akan digunakan saat layanan  │
│ ditangguhkan                               │
│                                            │
│ Pengaktifan Kembali Layanan                │
│ [Dropdown: Pilih template]                 │
│ Template yang akan digunakan saat layanan  │
│ diaktifkan kembali                         │
└────────────────────────────────────────────┘
```

---

## 🧪 Testing

### 1. **Test Suspend Notification**

**Via Command (Dry Run):**
```bash
# Lihat customer mana saja yang akan di-suspend
php artisan suspend:auto-ip-binding --dry-run
```

**Via Command (Test Specific Customer):**
```bash
# Suspend customer tertentu dan kirim WhatsApp
php artisan suspend:auto-ip-binding --customer=1
```

**Via Filament UI:**
1. Buka menu **"Customer"**
2. Pilih customer yang active
3. Klik **"Actions" → "Suspend Customer"**
4. Confirm
5. Cek WhatsApp customer apakah notifikasi terkirim

### 2. **Test Unsuspend Notification**

**Via Payment Update:**
1. Buka menu **"Payments"**
2. Cari payment dengan status 'pending'
3. Update status menjadi **'Paid'** atau **'Confirmed'**
4. Sistem akan otomatis:
   - Unsuspend customer (jika suspended)
   - Kirim WhatsApp notification unsuspend
   - Kirim WhatsApp konfirmasi pembayaran

**Via Filament UI:**
1. Buka menu **"Customer"**
2. Pilih customer yang suspended
3. Klik **"Actions" → "Unsuspend Customer"**
4. Confirm
5. Cek WhatsApp customer apakah notifikasi terkirim

---

## 📊 Monitoring & Logging

### 1. **Log Activity**

Semua suspend/unsuspend akan tercatat di **Activity Log**:
```
Menu: "Activity Log"
Filter: 
  - Subject Type: Customer
  - Event: "suspend", "unsuspend"
```

**Contoh Log Entry:**
```
Action: suspend
Description: Customer Budi suspended via IP Binding
Properties:
  - ip_bindings_suspended: 1
  - method: ip_binding
  - reason: payment_overdue
```

### 2. **WhatsApp Log**

Cek status pengiriman WhatsApp di log file:
```bash
tail -f storage/logs/laravel.log | grep "Suspend notification"
tail -f storage/logs/laravel.log | grep "Unsuspend notification"
```

**Contoh Log Entry:**
```
[2025-10-23 10:30:15] INFO: Suspend notification sent to customer
  customer_id: 123
  customer_name: "Budi Santoso"
  phone: "6281234567890"
  template_used: "Penangguhan Layanan"
```

---

## 🔧 Troubleshooting

### ❌ Problem: Notifikasi Tidak Terkirim

**Possible Causes:**
1. **Customer tidak punya nomor telepon**
   - Solution: Pastikan field `phone` terisi di data customer

2. **WhatsApp API error**
   - Check: `storage/logs/laravel.log`
   - Solution: Cek koneksi ke GOWA API, pastikan token valid

3. **Template tidak ditemukan**
   - Check: Menu "Template Pesan" → Pastikan ada template aktif untuk type yang sesuai
   - Solution: Jika tidak ada, sistem akan menggunakan fallback message (hardcoded)

### ❌ Problem: Template Tidak Muncul di Dropdown

**Possible Causes:**
1. **Template belum dibuat**
   - Solution: Buat template baru di menu "Template Pesan"

2. **Template tidak aktif**
   - Solution: Set status template menjadi **"Aktif"**

3. **Template type salah**
   - Solution: Pastikan template type adalah:
     - `service_suspended` untuk suspend
     - `service_reactivated` untuk unsuspend

### 🔄 Regenerate Template Default

Jika template default hilang atau ingin reset:
```bash
php artisan db:seed --class=ServiceSuspendTemplateSeeder
```

---

## 📝 Technical Details

### **Service File**
- `app/Services/SuspendViaIpBindingService.php`
  - Method: `sendSuspendNotification()`
  - Method: `sendUnsuspendNotification()`

### **Observer**
- `app/Observers/PaymentObserver.php`
  - Trigger unsuspend saat payment status berubah ke 'paid' / 'confirmed'

### **Command**
- `app/Console/Commands/AutoSuspendViaIpBindingCommand.php`
  - Schedule: Tanggal 26 setiap bulan jam 00:01

### **Model**
- `app/Models/WhatsAppTemplate.php`
  - Constant: `TYPE_SERVICE_SUSPENDED`
  - Constant: `TYPE_SERVICE_REACTIVATED`

### **Settings Page**
- `app/Filament/Pages/WhatsAppTemplateSettings.php`
  - Setting key: `whatsapp_template_service_suspended`
  - Setting key: `whatsapp_template_service_reactivated`

### **Database**
- Template disimpan di table: `whatsapp_templates`
- Setting disimpan di table: `settings`

---

## 🎓 Best Practices

### 1. **Template Design**

✅ **DO:**
- Gunakan bahasa yang sopan dan jelas
- Sertakan informasi penting (due date, amount)
- Berikan call-to-action yang jelas
- Gunakan emoji untuk mempercantik tampilan (optional)
- Test template sebelum digunakan

❌ **DON'T:**
- Jangan terlalu panjang (max 1000 karakter)
- Jangan gunakan bahasa kasar atau mengancam
- Jangan lupa variabel yang penting

### 2. **Testing**

- Selalu test di dry-run mode dulu
- Test dengan customer dummy sebelum production
- Monitor log untuk memastikan delivery

### 3. **Backup**

- Export template penting secara berkala
- Simpan template yang sudah terbukti efektif

---

## 📚 Related Documentation

- [WhatsApp Integration Guide](./whatsapp_pdf_invoice_feature.md)
- [Suspend via IP Binding Feature](./suspend_via_ip_binding_feature.md)
- [Auto Update Overdue Payment Status](./auto_update_overdue_payment_status.md)
- [Payment Reminder System](./payment_reminder_system.md)

---

## ✅ Summary

Sistem sekarang **sudah fully support** WhatsApp notification untuk suspend/unsuspend dengan:
- ✅ Template system yang configurable
- ✅ Variabel dinamis untuk personalisasi
- ✅ Auto-send saat suspend/unsuspend
- ✅ Fallback message jika template tidak ada
- ✅ Activity logging & monitoring
- ✅ Integration dengan Payment flow

**Tidak perlu coding lagi!** Semua sudah bisa dikonfigurasi lewat admin panel. 🎉

