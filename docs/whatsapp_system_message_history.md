# Fitur Riwayat Pesan Sistem WhatsApp

## 📋 Overview
Fitur untuk melihat riwayat semua pesan WhatsApp yang dikirim otomatis oleh sistem, seperti:
- 📧 Tagihan Baru
- ⏰ Pengingat Tagihan  
- ⚠️ Tagihan Terlambat
- ✅ Konfirmasi Pembayaran

## ✨ Fitur Utama

### 1. **List Riwayat Pesan dengan Tabs**
   - **Lokasi:** WhatsApp > Riwayat Pesan Sistem
   - **Tabs Available:**
     - 📋 Semua Pesan
     - ✅ Terkirim
     - ❌ Gagal
     - 📧 Tagihan Baru
     - ⏰ Pengingat
     - ⚠️ Terlambat
     - 💰 Konfirmasi Bayar
   - **Badge:** Setiap tab menampilkan jumlah pesan

### 2. **Tabel List Pesan**
   - **Kolom:**
     - Pelanggan (nama + no. WhatsApp)
     - Jenis Pesan (badge berwarna)
     - No. Invoice + Jumlah
     - Status (dengan icon)
     - Waktu Kirim (dengan "X ago")
   - **Fitur:**
     - Search pelanggan, jenis pesan, invoice
     - Sort semua kolom
     - Auto-refresh setiap 30 detik

### 3. **Filter Advanced**
   - Filter Jenis Pesan (multiple select)
   - Filter Status (multiple select)
   - Filter Tanggal (dari - sampai)

### 4. **Detail Pesan**
   - Info lengkap: pelanggan, invoice, status
   - Isi pesan yang dikirim
   - Response API (JSON formatted)
   - **Tombol Kirim Ulang** (untuk pesan gagal/pending)

### 5. **Kirim Ulang Pesan**
   - Tersedia untuk pesan dengan status:
     - ❌ Gagal
     - ⏳ Pending
   - Confirmation modal sebelum kirim
   - Auto-update status setelah berhasil

## 🎨 Color Coding

### Jenis Pesan:
- 🔵 **Biru (Info):** Tagihan Baru
- 🟡 **Kuning (Warning):** Pengingat Tagihan
- 🔴 **Merah (Danger):** Tagihan Terlambat
- 🟢 **Hijau (Success):** Konfirmasi Pembayaran
- ⚪ **Abu-abu (Gray):** Broadcast

### Status:
- 🟢 **Hijau:** Terkirim (dengan ✓ icon)
- 🔴 **Merah:** Gagal (dengan ✕ icon)
- 🟡 **Kuning:** Menunggu (dengan ⏱ icon)

## 📂 File yang Dibuat

### Resource & Pages
```
app/
├── Filament/
│   └── Resources/
│       ├── WhatsAppMessageResource.php
│       └── WhatsAppMessageResource/
│           └── Pages/
│               ├── ListWhatsAppMessages.php
│               └── ViewWhatsAppMessage.php
```

### 1. `WhatsAppMessageResource.php`
- Main resource untuk CRUD
- Query scope: hanya pesan sistem (bukan broadcast)
- Table dengan 5 kolom informatif
- 3 jenis filter (jenis, status, tanggal)
- Infolist untuk detail view dengan 3 sections

### 2. `ListWhatsAppMessages.php`
- 7 tabs untuk filter cepat
- Badge counter untuk setiap tab
- Color-coded badges

### 3. `ViewWhatsAppMessage.php`
- Detail view
- Action "Kirim Ulang" dengan confirmation
- Integration dengan WhatsAppService

## 🚀 Cara Menggunakan

### Melihat Riwayat Pesan:
1. Login ke admin panel
2. Buka menu **WhatsApp > Riwayat Pesan Sistem**
3. Pilih tab sesuai kebutuhan:
   - "Semua Pesan" → Lihat semua
   - "Terkirim" → Hanya yang sukses
   - "Gagal" → Hanya yang error
   - "Tagihan Baru" → Filter jenis tertentu
4. Gunakan search/filter untuk pencarian spesifik

### Lihat Detail Pesan:
1. Di list, klik **"Detail"** pada baris pesan
2. Tab **"Info"** akan terbuka otomatis
3. Lihat informasi lengkap:
   - Jenis pesan & status
   - Data pelanggan
   - No. Invoice & jumlah tagihan
   - Waktu pengiriman
4. Scroll ke **"Isi Pesan"** untuk lihat pesan lengkap
5. Expand **"Response API"** untuk lihat response teknis

### Kirim Ulang Pesan Gagal:
1. Buka detail pesan yang gagal/pending
2. Klik tombol **"Kirim Ulang"** di header (orange, icon paper airplane)
3. Konfirmasi di modal
4. Tunggu notifikasi:
   - ✅ Sukses → Status otomatis update jadi "Terkirim"
   - ❌ Gagal → Lihat error message

## 🔍 Fitur Detail

### Auto-Refresh
- List auto-refresh setiap **30 detik**
- Badge counter update otomatis
- Status terbaru selalu ditampilkan

### Search & Filter
**Search (realtime):**
- Nama pelanggan
- No. WhatsApp
- No. Invoice
- Jenis pesan

**Filter:**
- **Jenis Pesan:** Multiple select
- **Status:** Multiple select
- **Tanggal:** Range picker (dari - sampai)

**Sort:**
- Default: Waktu kirim (terbaru)
- Bisa sort semua kolom

### Badge Counter
Setiap tab menampilkan jumlah pesan:
```
Semua Pesan (125)
Terkirim (100)
Gagal (5)
Tagihan Baru (50)
...
```

### Description Text
Kolom dengan info tambahan:
- **Pelanggan:** Menampilkan no. WhatsApp
- **Invoice:** Menampilkan jumlah tagihan (Rp formatted)
- **Waktu Kirim:** Menampilkan relative time ("2 hours ago")

## 🔒 Security & Permissions

### Query Scope
```php
whereNotNull('payment_id')
->orWhere('message_type', '!=', 'broadcast')
```
- Hanya tampilkan pesan sistem
- Broadcast message tidak muncul (ada menu terpisah)

### Actions
- **View:** Semua user bisa lihat
- **Kirim Ulang:** Perlu konfirmasi modal
- **Delete:** Bulk delete available

## 📊 Database Structure

### Tabel: `whats_app_messages`
Kolom yang digunakan:
```sql
- customer_id (foreign key)
- payment_id (foreign key, nullable)
- message_type (varchar: billing.new, billing.reminder, dll)
- message (text)
- status (varchar: sent, failed, pending)
- sent_at (timestamp)
- response (text: JSON)
- media_path (varchar, nullable)
- media_type (enum, nullable)
```

## 🎯 Use Cases

### 1. Monitor Pengiriman Otomatis
- Cek status pengiriman tagihan baru
- Track reminder yang dikirim
- Lihat pesan terlambat

### 2. Troubleshooting
- Cari pesan yang gagal
- Lihat error response
- Kirim ulang pesan gagal

### 3. Customer Service
- Cek apakah customer sudah terima pesan
- Lihat detail pesan yang dikirim
- Verify invoice notification

### 4. Reporting
- Hitung success rate per jenis pesan
- Track delivery time
- Analyze failed messages

## 🔮 Future Enhancements (Optional)

1. **Export to Excel**
   - Export filtered messages
   - Include all details

2. **Bulk Resend**
   - Resend multiple failed messages
   - Schedule resend

3. **Analytics Dashboard**
   - Success rate chart
   - Message type distribution
   - Timeline graph

4. **Email Notification**
   - Alert on failed messages
   - Daily summary report

5. **WhatsApp Preview**
   - Preview message as WhatsApp bubble
   - Show media attachments

## 📝 Examples

### Scenario 1: Check Tagihan Baru Delivery
```
1. Buka "Riwayat Pesan Sistem"
2. Klik tab "Tagihan Baru"
3. Filter tanggal: hari ini
4. Lihat list pesan terkirim hari ini
5. Cek yang gagal (badge merah)
6. Klik detail untuk troubleshoot
```

### Scenario 2: Resend Failed Message
```
1. Buka tab "Gagal"
2. Cari customer tertentu (search)
3. Klik "Detail"
4. Lihat error di "Response API"
5. Klik "Kirim Ulang"
6. Konfirmasi
7. Status berubah jadi "Terkirim"
```

### Scenario 3: Verify Customer Complaint
```
Customer: "Saya belum terima notif tagihan"

Admin:
1. Buka "Riwayat Pesan Sistem"
2. Search nama customer
3. Filter jenis: "Tagihan Baru"
4. Cek status: 
   - Terkirim? → Mungkin customer belum cek WA
   - Gagal? → Kirim ulang
   - Tidak ada? → Pesan belum dijadwalkan
```

## 🎊 Benefits

### Untuk Admin:
- ✅ Monitoring real-time
- ✅ Troubleshooting cepat
- ✅ Resend dengan mudah
- ✅ Audit trail lengkap

### Untuk Customer Service:
- ✅ Verify delivery status
- ✅ Handle complaint
- ✅ Provide proof of notification

### Untuk Developer:
- ✅ Debug API issues
- ✅ Monitor system health
- ✅ Analyze patterns

---

**Created:** 13 Oktober 2025  
**Version:** 1.0  
**Status:** ✅ Production Ready  
**Menu:** WhatsApp > Riwayat Pesan Sistem

