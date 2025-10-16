# Handling Test Mode di n8n Workflow

## 📋 Overview

Saat Anda klik button **"Test Webhook"** di admin panel, sistem mengirim payload dengan flag `test_mode: true` dan `action: "test"`.

Anda bisa setup n8n workflow untuk mendeteksi test mode dan skip aksi suspend Mikrotik.

---

## 🎯 Kenapa Perlu Test Mode?

**Tanpa Test Mode:**
```
Test webhook → n8n suspend Mikrotik → Customer internet mati
❌ BURUK! Customer real kena suspend saat testing
```

**Dengan Test Mode:**
```
Test webhook → n8n detect test_mode → Skip suspend → Log only
✅ BAGUS! Customer aman, testing tetap jalan
```

---

## 🔧 Setup di n8n

### Option 1: If Node (Simple)

```
1. Webhook Node
   ↓
2. IF Node
   Condition: {{ $json.test_mode }} is true
   ↓
   TRUE → Response "Test received, skipped Mikrotik"
   FALSE → Continue to Mikrotik suspend
```

**If Node Configuration:**
```javascript
// Condition
{{ $json.test_mode }} === true
```

**Response (Test Mode):**
```json
{
  "status": "success",
  "message": "Test webhook received. No action taken (test mode).",
  "received_data": "{{ $json }}"
}
```

---

### Option 2: Switch Node (Advanced)

```
1. Webhook Node
   ↓
2. Switch Node
   Mode: Rules
   ↓
   Route 0: test_mode = true → Log & Response
   Route 1: action = "suspend" → Mikrotik Suspend
   Route 2: action = "unsuspend" → Mikrotik Unsuspend
```

**Switch Node Configuration:**
```javascript
// Route 0 (Test)
{{ $json.test_mode }} === true

// Route 1 (Suspend)
{{ $json.action }} === "suspend" && {{ $json.test_mode }} !== true

// Route 2 (Unsuspend)
{{ $json.action }} === "unsuspend" && {{ $json.test_mode }} !== true
```

---

### Option 3: Function Node (Most Flexible)

```
1. Webhook Node
   ↓
2. Function Node
   ↓
3. If test_mode → Return test response
   If real → Continue to Mikrotik
```

**Function Node Code:**
```javascript
// Check if test mode
if ($input.item.json.test_mode === true) {
  // Test mode - return early without processing
  return {
    json: {
      status: 'success',
      message: 'Test webhook received successfully',
      test_mode: true,
      data_received: $input.item.json,
      timestamp: new Date().toISOString()
    }
  };
}

// Real mode - continue processing
return $input.item;
```

---

## 📦 Complete Example Workflow

```
┌─────────────────┐
│ Webhook Trigger │
└────────┬────────┘
         │
         ▼
┌─────────────────┐
│  Switch Node    │
│  on test_mode   │
└────┬────────┬───┘
     │        │
 test│        │real
     │        │
     ▼        ▼
┌──────┐  ┌─────────────────┐
│ Log  │  │ If action =     │
│ Only │  │ suspend/unsuspend│
└──────┘  └────┬────────┬───┘
               │        │
          suspend     unsuspend
               │        │
               ▼        ▼
         ┌─────────┐ ┌─────────┐
         │Mikrotik │ │Mikrotik │
         │Disable  │ │Enable   │
         └─────────┘ └─────────┘
```

---

## 🎨 Example Complete n8n Workflow

### Node 1: Webhook
```
Method: POST
Path: /webhook/dunning-action
Response: Workflow Data
```

### Node 2: Switch
```javascript
Mode: Rules
Rules:
  0. {{ $json.test_mode }} === true
  1. {{ $json.action }} === "suspend"
  2. {{ $json.action }} === "unsuspend"
```

### Node 3a: Test Response (from Switch route 0)
```
Type: Respond to Webhook
Response:
{
  "status": "success",
  "message": "Test webhook received. Skipped Mikrotik action.",
  "test_mode": true,
  "received_at": "{{ $now }}",
  "data": {
    "customer": "{{ $json.customer_name }}",
    "invoice": "{{ $json.invoice_number }}"
  }
}
```

### Node 3b: HTTP Request Suspend (from Switch route 1)
```
Method: POST
URL: http://{{ $json.mikrotik_ip }}:8728/api/user/disable
Body:
{
  "username": "{{ $json.customer_phone }}",
  "reason": "Overdue {{ $json.days_overdue }} days"
}
```

### Node 3c: HTTP Request Unsuspend (from Switch route 2)
```
Method: POST
URL: http://{{ $json.mikrotik_ip }}:8728/api/user/enable
Body:
{
  "username": "{{ $json.customer_phone }}"
}
```

### Node 4: Send Telegram Notification (Optional)
```
Message:
🔔 Dunning Action: {{ $json.action }}
👤 Customer: {{ $json.customer_name }}
📱 Phone: {{ $json.customer_phone }}
🧾 Invoice: {{ $json.invoice_number }}
⏰ Days Overdue: {{ $json.days_overdue }}
```

---

## ✅ Testing Checklist

### Test Mode (via Button)
- [ ] Klik "Test Webhook" di admin panel
- [ ] n8n terima webhook dengan `test_mode: true`
- [ ] n8n skip Mikrotik action
- [ ] n8n return response sukses
- [ ] Admin panel tampil notifikasi sukses
- [ ] Customer internet tetap aktif (tidak kesuspend)

### Real Mode (Suspend)
- [ ] Customer overdue >= trigger days
- [ ] Cron dunning:process berjalan
- [ ] n8n terima webhook dengan `test_mode: false/undefined`
- [ ] n8n eksekusi Mikrotik suspend
- [ ] Customer internet tersuspend
- [ ] Log tercatat

### Real Mode (Unsuspend)
- [ ] Staff update payment status → paid
- [ ] n8n terima webhook unsuspend dengan `test_mode: false/undefined`
- [ ] n8n eksekusi Mikrotik enable
- [ ] Customer internet aktif kembali
- [ ] Log tercatat

---

## 🔍 Debugging

### Cek Header Test Mode

n8n juga terima header `X-Test-Mode: true` saat test webhook:

```javascript
// Function node
if ($request.headers['x-test-mode'] === 'true') {
  // This is a test
}
```

### Cek Payload Test Mode

```javascript
// Function node
const isTest = $json.test_mode === true || 
               $json.action === 'test' ||
               $request.headers['x-test-mode'] === 'true';

if (isTest) {
  // Skip real action
}
```

---

## 📊 Payload Examples

### Test Webhook Payload
```json
{
  "action": "test",
  "test_mode": true,
  "customer_id": 999,
  "customer_name": "Test Customer",
  "customer_phone": "628123456789",
  "invoice_number": "TEST-INV-20251013143015",
  "invoice_amount": 250000,
  "days_overdue": 7,
  "message": "This is a test webhook from Laravel. Please ignore this data."
}
```

### Real Suspend Payload
```json
{
  "action": "suspend",
  "test_mode": false,  // or undefined
  "customer_id": 123,
  "customer_name": "John Doe",
  "customer_phone": "628123456789",
  "invoice_number": "INV-202510-001",
  "invoice_amount": 250000,
  "days_overdue": 8
}
```

---

## 🎯 Best Practices

1. **Always check test_mode** sebelum eksekusi aksi Mikrotik
2. **Log semua webhook** (test & real) untuk audit
3. **Return proper response** untuk test mode
4. **Monitor failed webhooks** dengan alert
5. **Gunakan test mode** sebelum production deploy

---

## 💡 Tips

- Test mode payload pakai `customer_id: 999` - mudah dibedakan
- Invoice number test selalu diawali `TEST-INV-`
- Bisa setup alert khusus jika terima banyak test webhook (> 10x/hour)
- Log test webhook ke database terpisah untuk audit

---

**Dibuat:** 13 Oktober 2025  
**Author:** System Administrator  
**Purpose:** Guide for handling test webhooks in n8n

