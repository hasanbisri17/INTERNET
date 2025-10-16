# Penjelasan Field Integrasi n8n

## Field-field di "Penagihan Otomatis"

### ✅ Field Wajib (REQUIRED)

#### 1. **Aktifkan Integrasi n8n**
- **Toggle ON** untuk mengaktifkan fitur
- Jika OFF, field lain tidak muncul

#### 2. **URL Webhook n8n**
- **Wajib diisi** jika integrasi aktif
- Contoh: `https://your-n8n-instance.com/webhook/suspend-customer`
- Cara dapat: Copy dari webhook node di n8n workflow Anda

#### 3. **HTTP Method**
- Default: **POST** (sudah terisi otomatis)
- Biasanya tidak perlu diubah
- Pilihan: POST, PUT, PATCH

#### 4. **Trigger Setelah (hari keterlambatan)**
- Default: **7 hari** (sudah terisi otomatis)
- Artinya: Webhook baru dikirim setelah customer telat 7 hari
- Bisa diubah sesuai kebijakan: 3, 5, 7, 10, 14, dll

#### 5. **Auto Unsuspend saat Customer Bayar**
- Default: **ON** (sudah terisi otomatis)
- Rekomendasi: Biarkan ON
- Fungsi: Otomatis unsuspend saat customer bayar

---

### 📝 Field Optional (TIDAK WAJIB)

#### 6. **Custom Headers (JSON)**

**❓ Apa itu?**
HTTP headers tambahan yang dikirim ke n8n untuk authentication/security.

**🤔 Kapan perlu diisi?**
- ✅ Jika n8n webhook Anda **dilindungi dengan password/token**
- ✅ Jika workflow n8n Anda **validate authorization header**
- ❌ Jika webhook n8n Anda **public/tanpa auth** → **KOSONGKAN**

**💡 Contoh Pengisian:**

**Skenario A: Pakai Bearer Token**
```json
{
  "Authorization": "Bearer abc123xyz456"
}
```

**Skenario B: Pakai API Key**
```json
{
  "X-API-Key": "rahasia-kunci-api-anda-123"
}
```

**Skenario C: Kombinasi**
```json
{
  "Authorization": "Bearer abc123xyz456",
  "X-API-Key": "rahasia-kunci-api-anda-123",
  "X-Custom-Header": "nilai-custom"
}
```

**❌ Jika tidak perlu → KOSONGKAN saja!**

---

#### 7. **Custom Payload Template (JSON)**

**❓ Apa itu?**
Data tambahan yang akan digabungkan dengan payload default dan dikirim ke n8n.

**🤔 Kapan perlu diisi?**
- ✅ Jika workflow n8n butuh **IP Mikrotik** atau **port**
- ✅ Jika punya **multiple router** per region/area
- ✅ Jika butuh **data custom lain** untuk workflow
- ❌ Jika workflow n8n cukup dengan **data customer & invoice saja** → **KOSONGKAN**

**💡 Contoh Pengisian:**

**Skenario A: Single Mikrotik**
```json
{
  "mikrotik_ip": "192.168.1.1",
  "mikrotik_port": "8728"
}
```

**Skenario B: Multiple Mikrotik per Region**
```json
{
  "jakarta_mikrotik": "192.168.1.1",
  "bandung_mikrotik": "192.168.2.1",
  "surabaya_mikrotik": "192.168.3.1",
  "default_port": "8728"
}
```

**Skenario C: Mikrotik + Notifikasi**
```json
{
  "mikrotik_ip": "192.168.1.1",
  "mikrotik_port": "8728",
  "telegram_chat_id": "-1001234567890",
  "notification_enabled": true
}
```

**❌ Jika tidak perlu → KOSONGKAN saja!**

---

## 📦 Payload Final yang Dikirim ke n8n

### Default Payload (Selalu Dikirim)

```json
{
  "action": "suspend",                   // atau "unsuspend"
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

### Jika Anda Isi Custom Payload

Misalnya Anda isi Custom Payload:
```json
{
  "mikrotik_ip": "192.168.1.1",
  "region": "Jakarta"
}
```

Maka payload final yang dikirim ke n8n:
```json
{
  "action": "suspend",
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
  "triggered_at": "2025-10-13T10:30:15+07:00",
  
  // Data custom Anda (merged)
  "mikrotik_ip": "192.168.1.1",
  "region": "Jakarta"
}
```

---

## 🎯 Rekomendasi Sederhana

### Untuk Pemula (Setup Minimal)

**Yang WAJIB diisi:**
1. ✅ Aktifkan Integrasi n8n: **ON**
2. URL Webhook n8n: `https://your-n8n.com/webhook/suspend`
3. Trigger Setelah: `7` (default)
4. Auto Unsuspend: **ON** (default)

**Yang TIDAK perlu diisi (kosongkan):**
- ❌ Custom Headers → kosong
- ❌ Custom Payload → kosong

**Total: 4 field saja!**

---

### Untuk Advanced (Butuh Security)

**Tambahan:**
- **Custom Headers**: Isi jika n8n pakai auth
  ```json
  {"Authorization": "Bearer token-rahasia-anda"}
  ```

**Total: 5 field**

---

### Untuk Multi-Region (Multiple Mikrotik)

**Tambahan:**
- **Custom Payload**: Isi dengan mapping Mikrotik
  ```json
  {
    "jakarta_ip": "192.168.1.1",
    "bandung_ip": "192.168.2.1"
  }
  ```

**Total: 5 field**

---

## 🧪 Testing

### Test 1: Setup Minimal (Tanpa Custom Fields)

1. Isi hanya field wajib
2. **Kosongkan** Custom Headers & Custom Payload
3. Run: `php artisan dunning:process --dry-run`
4. Cek log: Payload yang dikirim hanya data default

### Test 2: Dengan Custom Payload

1. Isi Custom Payload:
   ```json
   {"mikrotik_ip": "192.168.1.1"}
   ```
2. Run: `php artisan dunning:process --dry-run`
3. Cek log: Payload sekarang include `mikrotik_ip`

### Test 3: Dengan Custom Headers

1. Isi Custom Headers:
   ```json
   {"Authorization": "Bearer test123"}
   ```
2. Run: `php artisan dunning:process`
3. Cek n8n webhook: Terima request dengan header Authorization

---

## ❓ FAQ

**Q: Wajib isi Custom Headers atau Custom Payload?**  
A: **TIDAK WAJIB**. Hanya isi jika workflow n8n Anda memang butuh.

**Q: Jika saya kosongkan, apakah webhook tetap jalan?**  
A: **YA**. Webhook tetap jalan, hanya kirim data default (customer + invoice).

**Q: Format JSON salah gimana?**  
A: Sistem akan error. Pastikan format JSON valid. Gunakan tools seperti jsonlint.com untuk validate.

**Q: Bisa pakai variabel di Custom Payload?**  
A: Tidak. Custom Payload adalah **static template**. Data customer & invoice sudah auto-included di payload default.

**Q: Beda Custom Headers vs Custom Payload?**  
A: 
- **Custom Headers** = HTTP headers (untuk auth/security)
- **Custom Payload** = HTTP body/data tambahan (untuk workflow logic)

---

## 📞 Contoh Lengkap

### Setup Lengkap untuk ISP dengan Multiple Mikrotik

```
Nama Konfigurasi: Auto Suspend Jakarta Region
Deskripsi: Suspend otomatis untuk customer Jakarta area

--- Pengaturan Penagihan ---
Masa Tenggang: 0 hari
Kirim Pengingat: 3 hari

--- Pengaturan Penangguhan ---
Tangguhkan Otomatis: OFF (pakai n8n)
Aktifkan Kembali Otomatis: ON

--- Saluran Notifikasi ---
✅ WhatsApp

--- Integrasi n8n ---
✅ Aktifkan Integrasi n8n

URL Webhook n8n:
https://n8n.myisp.com/webhook/dunning-jakarta

HTTP Method: POST

Custom Headers (JSON):
{
  "Authorization": "Bearer eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9",
  "X-Region": "Jakarta"
}

Trigger Setelah: 7 hari

✅ Auto Unsuspend saat Customer Bayar

Custom Payload Template (JSON):
{
  "mikrotik_ip": "192.168.10.1",
  "mikrotik_port": "8728",
  "mikrotik_username": "admin-api",
  "region": "Jakarta",
  "notification_telegram": "-1001234567890",
  "priority": "high"
}
```

---

**Dibuat:** 13 Oktober 2025  
**Update:** Field explanation dengan contoh praktis

