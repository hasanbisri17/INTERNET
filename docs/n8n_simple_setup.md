# Setup Sederhana: Penagihan Otomatis dengan n8n

## 📋 Overview

Form "Penagihan Otomatis" sudah disederhanakan untuk fokus pada integrasi n8n suspend/unsuspend customer.

**Yang dihapus:**
- ❌ Kirim Pengingat (sudah ada di menu "Pengaturan Reminder")
- ❌ Saluran Notifikasi (sudah ada di menu "Pengaturan Reminder")  
- ❌ Custom Headers (terlalu teknis, tidak perlu untuk kebanyakan kasus)
- ❌ Custom Payload Template (terlalu teknis, tidak perlu untuk kebanyakan kasus)

## 🎯 Form Baru (Simplified)

### Field yang Perlu Diisi

**1. Informasi Dasar**
- **Nama Konfigurasi**: Contoh: "Auto Suspend n8n"
- **Deskripsi**: Penjelasan singkat (optional)
- **✅ Aktif**: Toggle ON

**2. Pengaturan Penagihan**
- **Masa Tenggang (hari)**: Default `0` hari
  - Toleransi setelah jatuh tempo sebelum mulai dunning

**3. Pengaturan Penangguhan**
- **Tangguhkan Otomatis**: Toggle ON/OFF (sistem lama)
- **Tangguhkan Setelah (hari)**: Default `7` hari
- **✅ Aktifkan Kembali Otomatis Setelah Pembayaran**: Toggle ON

**4. Integrasi n8n** ⭐
- **✅ Aktifkan Integrasi n8n**: Toggle ON
- **URL Webhook n8n**: `https://your-n8n.com/webhook/suspend`
- **Trigger Setelah (hari keterlambatan)**: Default `7` hari
- **✅ Auto Unsuspend saat Customer Bayar**: Toggle ON

---

## 🚀 Langkah Setup (Super Cepat)

### Step 1: Setup n8n Workflow
Buat workflow sederhana di n8n:
```
Webhook (POST) 
→ If action = "suspend" → HTTP ke Mikrotik disable user
→ If action = "unsuspend" → HTTP ke Mikrotik enable user
```

### Step 2: Copy Webhook URL
Copy URL dari webhook node n8n, contoh:
```
https://n8n.myisp.com/webhook/suspend-customer
```

### Step 3: Buat Config di Laravel
1. Buka: **Konfigurasi Sistem → Penagihan Otomatis**
2. Klik: **Create**
3. Isi form:

```
Nama Konfigurasi: Auto Suspend n8n
Deskripsi: Suspend otomatis via n8n untuk customer overdue

✅ Aktif

--- Pengaturan Penagihan ---
Masa Tenggang: 0 hari

--- Pengaturan Penangguhan ---
Tangguhkan Otomatis: OFF (pakai n8n)
Tangguhkan Setelah: 7 hari
✅ Aktifkan Kembali Otomatis Setelah Pembayaran

--- Integrasi n8n ---
✅ Aktifkan Integrasi n8n
URL Webhook n8n: https://n8n.myisp.com/webhook/suspend-customer
Trigger Setelah: 7 hari
✅ Auto Unsuspend saat Customer Bayar
```

4. Klik **Create**

### Step 4: Test Webhook

**Opsi 1: Test via Button (Paling Mudah)** ⭐
1. Di list "Penagihan Otomatis", klik **Test Webhook** (icon petir ⚡)
2. Klik **Kirim Test**
3. Tunggu notifikasi:
   - ✅ **Sukses** → Webhook connected!
   - ❌ **Gagal** → Cek URL atau n8n workflow

**Opsi 2: Test via Command Line**
```bash
# Test preview
php artisan dunning:process --dry-run

# Test real
php artisan dunning:process
```

**Selesai!** 🎉

---

## 📡 Data yang Dikirim ke n8n

### Data Real (Suspend/Unsuspend)
```json
{
  "action": "suspend",              // atau "unsuspend"
  "customer_id": 123,
  "customer_name": "John Doe",
  "customer_phone": "628123456789",
  "customer_email": "john@example.com",
  "customer_address": "Jl. Contoh No. 123",
  "invoice_number": "INV-202510-001",
  "invoice_amount": 250000,
  "due_date": "2025-10-05",
  "days_overdue": 8,
  "payment_id": 456,
  "triggered_at": "2025-10-13T10:30:15+07:00"
}
```

### Data Test (via Button Test Webhook)
```json
{
  "action": "test",
  "test_mode": true,
  "customer_id": 999,
  "customer_name": "Test Customer",
  "customer_phone": "628123456789",
  "customer_email": "test@example.com",
  "customer_address": "Jl. Test No. 123",
  "invoice_number": "TEST-INV-20251013143015",
  "invoice_amount": 250000,
  "due_date": "2025-10-06",
  "days_overdue": 7,
  "payment_id": 999,
  "triggered_at": "2025-10-13T14:30:15+07:00",
  "message": "This is a test webhook from Laravel..."
}
```

**Note:** n8n workflow bisa detect `test_mode: true` untuk skip aksi suspend Mikrotik.

Gunakan data ini di n8n workflow Anda untuk:
- Suspend user by `customer_phone` atau `customer_id`
- Kirim notifikasi ke admin
- Log ke database
- dll

---

## ⏰ Kapan Webhook Dikirim?

### Suspend (3x sehari)
- **09:00** pagi - Morning check
- **14:00** siang - Afternoon check
- **18:00** sore - Evening check

**Contoh:**
```
Customer telat 7 hari: 13 Okt jam 08:00
→ Jam 09:00 → Webhook suspend dikirim
→ n8n suspend Mikrotik
```

### Unsuspend (Realtime)
```
Staff konfirmasi payment: 13 Okt jam 14:30
→ Langsung (1-2 detik) → Webhook unsuspend dikirim
→ n8n aktifkan Mikrotik
```

---

## 🔧 FAQ

**Q: Perlu setup Custom Headers atau Custom Payload?**  
A: **TIDAK PERLU!** Sudah dihapus dari form. Webhook langsung kirim data customer & invoice.

**Q: Gimana cara auth ke n8n jika perlu password?**  
A: Setup authentication di n8n workflow (cek IP whitelist atau basic auth di n8n).

**Q: Bisa kirim data tambahan seperti IP Mikrotik?**  
A: Bisa! Tapi hardcode di n8n workflow, bukan di Laravel config.

**Q: Kenapa field "Kirim Pengingat" dihapus?**  
A: Karena sudah ada menu terpisah "Pengaturan Reminder" yang lebih powerful.

**Q: Method HTTP yang dipakai apa?**  
A: Otomatis **POST**. Sudah hardcoded, tidak perlu setting.

---

## 🎯 Perbedaan dengan Pengaturan Reminder

| Fitur | Pengaturan Reminder | Penagihan Otomatis (n8n) |
|-------|-------------------|------------------------|
| **Fokus** | Kirim pesan WA reminder | Suspend/unsuspend layanan |
| **Timing** | Sebelum & saat jatuh tempo | Setelah overdue X hari |
| **Action** | WhatsApp message | n8n webhook → Mikrotik |
| **Trigger** | H-3, H-1, H+0, Overdue | Hanya overdue X hari |
| **Realtime Unsuspend** | ❌ Tidak ada | ✅ Ya (saat payment paid) |

**Rekomendasi:** Gunakan **KEDUANYA**
1. **Pengaturan Reminder** → Kirim WA H-3, H-1, H+0
2. **Penagihan Otomatis** → Suspend H+7, Unsuspend saat bayar

---

## ✅ Checklist Setup

- [ ] Setup n8n workflow (webhook + Mikrotik logic)
- [ ] Buat config di menu "Penagihan Otomatis"
- [ ] Isi URL webhook n8n
- [ ] Set trigger days (default 7)
- [ ] **Klik button "Test Webhook" ⚡** (cara termudah!)
- [ ] Verifikasi webhook diterima di n8n
- [ ] (Optional) Test dengan `php artisan dunning:process --dry-run`
- [ ] Test suspend untuk 1 customer real
- [ ] Test unsuspend dengan update payment
- [ ] Monitor log: `tail -f storage/logs/laravel.log | grep n8n`

---

**Dibuat:** 13 Oktober 2025  
**Versi:** 2.0 (Simplified)  
**Perubahan:** Hapus field complex yang tidak perlu

