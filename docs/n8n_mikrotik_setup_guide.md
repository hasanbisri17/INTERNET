# Setup Guide: n8n Mikrotik Auto Suspend

## 📋 Prerequisites

### 1. Install Node Mikrotik di n8n
```bash
# Via n8n Community Nodes
Settings → Community Nodes → Install
Package: @digital-boss/n8n-nodes-mikrotik
```

### 2. Enable Mikrotik API
```bash
# Login ke Mikrotik via SSH/Terminal
/ip service enable api
/ip service set api port=8728

# Create API user (recommended: dedicated user untuk n8n)
/user add name=n8n-api password=YourStrongPassword group=full
```

### 3. Whitelist n8n IP di Mikrotik
```bash
# Allow n8n server IP
/ip firewall filter add chain=input protocol=tcp dst-port=8728 \
  src-address=YOUR-N8N-SERVER-IP action=accept \
  comment="n8n API Access"
```

---

## 🚀 Setup Workflow n8n

### Step 1: Import Workflow

1. Buka n8n → **Workflows** → **Add workflow** → **Import from File**
2. Upload file: `.cursor/commands/n8n-mikrotik-dunning-workflow.json`
3. Klik **Save**

### Step 2: Setup Mikrotik Credentials

1. Di workflow, klik node **"Mikrotik: Disable PPPoE"**
2. Klik **Create New Credential**
3. Isi data:
   ```
   Name: Mikrotik API
   Host: 192.168.1.1          (IP Mikrotik Anda)
   Port: 8728                 (Default API port)
   Username: n8n-api          (User yang dibuat tadi)
   Password: YourStrongPassword
   ```
4. Klik **Save**
5. Credential otomatis tersimpan untuk semua Mikrotik nodes

### Step 3: Sesuaikan Settings

#### A. Jika Mikrotik Username Beda Format

**Default:** Pakai `customer_phone` sebagai username PPPoE
```javascript
// Di node Mikrotik
name: {{ $json.body.customer_phone }}  // "081259789714"
```

**Jika pakai format lain:**
```javascript
// Option 1: Pakai customer_id
name: {{ $json.body.customer_id }}     // "1"

// Option 2: Pakai email
name: {{ $json.body.customer_email }}  // "bisri171998@gmail.com"

// Option 3: Custom format
name: customer_{{ $json.body.customer_id }}  // "customer_1"
```

#### B. Jika Pakai Hotspot (bukan PPPoE)

Ganti resource di Mikrotik nodes:
```
Resource: pppSecret → hotspotUser
Operation: disable / enable
```

#### C. Multiple Mikrotik

Jika punya beberapa Mikrotik router, tambahkan logic routing:

**Function Node (before Mikrotik node):**
```javascript
// Tentukan Mikrotik berdasarkan area/region
const customer = $input.item.json.body;
let mikrotikHost = '192.168.1.1'; // default

// Mapping customer ke Mikrotik
if (customer.customer_address.includes('Jakarta')) {
  mikrotikHost = '192.168.1.1';
} else if (customer.customer_address.includes('Bandung')) {
  mikrotikHost = '192.168.2.1';
} else if (customer.customer_address.includes('Surabaya')) {
  mikrotikHost = '192.168.3.1';
}

return {
  json: {
    ...customer,
    mikrotik_host: mikrotikHost
  }
};
```

### Step 4: Test Workflow

1. **Activate** workflow (toggle ON di kanan atas)
2. Di Laravel admin → Klik **"Test Webhook"**
3. Cek di n8n → **Executions** → Lihat hasil
4. Verifikasi:
   - ✅ Webhook diterima
   - ✅ Test mode detected (skip Mikrotik)
   - ✅ Response status: success

---

## 📡 Webhook URL

Setelah activate workflow, n8n akan generate webhook URL:

```
Production: https://n8n.inosoft.io/webhook/3fee2757-bba7-4387-bfe1-b324a4571f01
Test: https://n8n.inosoft.io/webhook-test/3fee2757-bba7-4387-bfe1-b324a4571f01
```

**Copy URL ini ke Laravel:**
```
Laravel Admin → Konfigurasi Sistem → Penagihan Otomatis → URL Webhook n8n
```

---

## 🎯 Flow Diagram

```
Laravel Webhook
      ↓
[1. Webhook Trigger]
      ↓
[2. Log Webhook Data] (console.log untuk debugging)
      ↓
[3. Check Test Mode?]
      ↓
   ┌──┴──┐
   │     │
 YES    NO
   │     │
   ↓     ↓
[Test]  [4. Route by Action]
Return       ↓
Success  ┌───┴───┐
         │       │
      Suspend  Unsuspend
         │       │
         ↓       ↓
   [5. Mikrotik] [6. Mikrotik]
     Disable      Enable
      PPPoE       PPPoE
         │       │
         ↓       ↓
   [7. Format] [8. Format]
    Response    Response
         │       │
         ↓       ↓
    [Return]  [Return]
    Success   Success
```

---

## 🔧 Troubleshooting

### Error: "Could not connect to Mikrotik"

**Penyebab:**
- Mikrotik API tidak aktif
- Firewall block port 8728
- IP/credentials salah

**Solusi:**
```bash
# Cek API service
/ip service print

# Pastikan api enabled dan port 8728
/ip service enable api

# Cek firewall
/ip firewall filter print

# Test koneksi dari n8n server
telnet 192.168.1.1 8728
```

### Error: "User not found in PPP secrets"

**Penyebab:**
- Username di Laravel tidak match dengan Mikrotik
- Customer phone di Laravel beda dengan PPPoE secret name

**Solusi:**
```bash
# Cek PPP secrets di Mikrotik
/ppp secret print

# Pastikan name match dengan customer_phone
# Misalnya: 081259789714
```

### Error: "Permission denied"

**Penyebab:**
- User n8n-api tidak punya permission

**Solusi:**
```bash
# Set user ke group full (atau create custom group)
/user set n8n-api group=full

# Atau create custom group dengan specific permission
/user group add name=api-group policy=api,read,write
/user set n8n-api group=api-group
```

### Test Mode Tidak Skip Mikrotik

**Penyebab:**
- Logic IF node salah

**Solusi:**
- Cek condition di "Check: Is Test Mode?"
- Pastikan: `{{ $json.body.test_mode }} === true`
- Test dengan manual execution

---

## 📊 Monitoring

### 1. Cek Execution History
```
n8n → Executions → Filter by workflow
```

### 2. Cek Error Logs
```
n8n → Executions → Failed
```

### 3. Laravel Logs
```bash
tail -f storage/logs/laravel.log | grep "n8n"
```

### 4. Mikrotik Logs
```bash
/log print where topics~"system" or topics~"ppp"
```

---

## 🎨 Advanced: Add Telegram Notification

Tambahkan node setelah Mikrotik action:

### Node: HTTP Request (Telegram)
```
Method: POST
URL: https://api.telegram.org/bot{{ YOUR_BOT_TOKEN }}/sendMessage

Body (JSON):
{
  "chat_id": "-1001234567890",
  "text": "🔴 Customer Suspended\n\nName: {{ $json.body.customer_name }}\nPhone: {{ $json.body.customer_phone }}\nInvoice: {{ $json.body.invoice_number }}\nDays Overdue: {{ Math.floor($json.body.days_overdue) }}\n\nAction: Suspended by Auto Dunning",
  "parse_mode": "HTML"
}
```

---

## ✅ Production Checklist

- [ ] Mikrotik API enabled & accessible
- [ ] Firewall allow n8n IP
- [ ] Dedicated user created (n8n-api)
- [ ] Credentials saved di n8n
- [ ] Workflow imported & activated
- [ ] Webhook URL set di Laravel
- [ ] Test mode working (skip Mikrotik)
- [ ] Real suspend tested (1 customer)
- [ ] Real unsuspend tested
- [ ] Logs monitoring setup
- [ ] Alert/notification configured
- [ ] Backup workflow exported

---

## 🔐 Security Best Practices

### 1. Use Dedicated API User
```bash
# DON'T use admin user
# DO create dedicated user
/user add name=n8n-api password=StrongPass group=api-only
```

### 2. Restrict API Access
```bash
# Only allow from n8n server
/ip firewall filter add chain=input protocol=tcp dst-port=8728 \
  src-address=!YOUR-N8N-IP action=drop
```

### 3. Use Strong Password
```bash
# Minimum 16 characters, mixed case, numbers, symbols
password=Abc123!@#XyzStrongPass2024
```

### 4. Regular Audit
```bash
# Check who accessed API
/log print where topics~"api"
```

---

## 📝 Customer Phone Format Notes

**Data dari webhook:**
```json
"customer_phone": "081259789714"
```

**Format di Mikrotik PPPoE:**
- Bisa sama: `081259789714`
- Bisa beda: `customer_1`, `user_081259789714`, dll

**Jika format beda, adjust di Function Node:**
```javascript
// Before Mikrotik node
const phone = $input.item.json.body.customer_phone;
const username = `user_${phone}`; // Hasil: user_081259789714

return {
  json: {
    ...$input.item.json.body,
    mikrotik_username: username
  }
};

// Di Mikrotik node, ganti jadi:
name: {{ $json.mikrotik_username }}
```

---

**Created:** 14 Oktober 2025  
**Version:** 1.0  
**Author:** System Administrator  
**Purpose:** Complete guide for Mikrotik auto suspend via n8n

