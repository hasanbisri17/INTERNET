# Quick Start: n8n Dunning Integration

## ⚡ Setup Cepat (5 Menit)

### 1. Buat Workflow n8n

**Node 1: Webhook Trigger**
```
URL: https://your-n8n.com/webhook/dunning-action
Method: POST
```

**Node 2: If Action = Suspend**
```javascript
{{ $json.action === 'suspend' }}
```

**Node 3: HTTP ke Mikrotik (Suspend)**
```
URL: http://192.168.1.1:8728/api/disable-user
Method: POST
Body: { "username": "{{ $json.customer_phone }}" }
```

**Node 4: If Action = Unsuspend**
```javascript
{{ $json.action === 'unsuspend' }}
```

**Node 5: HTTP ke Mikrotik (Unsuspend)**
```
URL: http://192.168.1.1:8728/api/enable-user
Method: POST
Body: { "username": "{{ $json.customer_phone }}" }
```

### 2. Copy Webhook URL

Dari n8n, copy URL webhook Anda, contoh:
```
https://your-n8n-instance.com/webhook-test/dunning-action
```

### 3. Konfigurasi di Laravel

1. Buka: **Konfigurasi Sistem → Penagihan Otomatis**
2. Klik: **Create**
3. Isi:
   - Nama: "Auto Suspend via n8n"
   - **✅ Aktifkan Integrasi n8n**
   - URL Webhook: `https://your-n8n-instance.com/webhook-test/dunning-action`
   - Trigger Setelah: `7` hari
   - **✅ Auto Unsuspend saat Customer Bayar**
4. Save

### 4. Test

**Cara Mudah (via UI):**
1. Klik button **"Test Webhook"** ⚡ di list Penagihan Otomatis
2. Tunggu notifikasi sukses/gagal
3. Cek di n8n apakah webhook diterima

**Cara Advanced (via Command):**
```bash
# Test tanpa trigger webhook (dry run)
php artisan dunning:process --dry-run

# Test sesungguhnya
php artisan dunning:process
```

### 5. Monitoring

```bash
# Cek log webhook
tail -f storage/logs/laravel.log | grep "n8n"
```

## 📋 Payload yang Diterima n8n

**Normal (suspend/unsuspend):**
```json
{
  "action": "suspend",              // atau "unsuspend"
  "customer_id": 123,
  "customer_name": "John Doe",
  "customer_phone": "628123456789",
  "invoice_number": "INV-202510-001",
  "invoice_amount": 250000,
  "days_overdue": 8,
  "due_date": "2025-10-05"
}
```

**Test (via button Test Webhook):**
```json
{
  "action": "test",
  "test_mode": true,
  "customer_name": "Test Customer",
  "invoice_number": "TEST-INV-20251013143015",
  "message": "This is a test webhook..."
}
```

## 🎯 Contoh Response dari n8n

```json
{
  "success": true,
  "message": "User suspended successfully",
  "customer": "John Doe"
}
```

## ⏰ Schedule

- **Suspend Check**: Otomatis **3x sehari** (09:00, 14:00, 18:00)
- **Unsuspend**: Otomatis realtime saat payment status → `paid`

## 🔧 Troubleshooting Cepat

| Error | Solusi |
|-------|--------|
| Webhook timeout | Cek koneksi n8n, pastikan bisa diakses |
| 401/403 | Tambahkan auth header di config |
| No trigger | Cek apakah ada payment overdue >= trigger days |
| Unsuspend tidak jalan | Pastikan toggle "Auto Unsuspend" ON |

## 📞 Need Help?

Baca dokumentasi lengkap: `docs/n8n_dunning_integration.md`

