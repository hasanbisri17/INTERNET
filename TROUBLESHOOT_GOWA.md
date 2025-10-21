# 🔧 Troubleshooting: Error 404 Not Found - GOWA API

## 🔍 Error yang Terjadi

```
Error: Client error: POST http://43.133.137.52:3000/send/text 
resulted in a '404 Not Found' response: Cannot POST /send/text
```

## 🎯 Penyebab

1. **GOWA server belum running** di server 43.133.137.52
2. **Port 3000 tidak terbuka** atau di-block firewall
3. **Endpoint API salah** (sudah diperbaiki di kode)

## ✅ Solusi Step-by-Step

### Step 1: Cek Apakah GOWA Server Running

#### Dari Browser
Buka browser dan akses:
```
http://43.133.137.52:3000
```

**Hasil yang Diharapkan:**
- ✅ Muncul dashboard GOWA atau halaman login
- ❌ Jika "Cannot connect" atau "Connection refused" → Server belum running

#### Dari Terminal (jika punya akses SSH)
```bash
# SSH ke server
ssh user@43.133.137.52

# Cek apakah port 3000 terbuka
netstat -tuln | grep 3000

# Cek Docker container GOWA
docker ps | grep gowa

# Jika tidak ada hasil, berarti GOWA belum running
```

### Step 2: Install & Jalankan GOWA Server

Jika server belum running, install GOWA:

```bash
# SSH ke server dulu
ssh user@43.133.137.52

# Jalankan GOWA dengan Docker
docker run -d \
  --name gowa \
  -p 3000:3000 \
  -e WHATSAPP_API_KEY=GQJLPguHbA4r8bT8v4K8TB7OW7L6xzww \
  -v gowa_data:/app/storages \
  --restart unless-stopped \
  aldinokemal/go-whatsapp-web-multidevice:latest

# Cek logs untuk memastikan running
docker logs -f gowa
```

**Output yang Diharapkan:**
```
Server is running on port 3000
WhatsApp Web Multi Device
```

### Step 3: Scan QR Code WhatsApp

1. Buka browser: `http://43.133.137.52:3000`
2. Akan muncul halaman GOWA
3. Klik **"Login"** atau akan langsung muncul QR Code
4. Scan dengan WhatsApp:
   - Buka WhatsApp → Settings → Linked Devices
   - Tap "Link a Device"
   - Scan QR Code
5. Tunggu hingga status **"Connected"** ✅

### Step 4: Test API Manual (Dari Terminal)

Setelah WhatsApp connected, test API manual:

```bash
# Test ping ke server
curl http://43.133.137.52:3000

# Test dengan endpoint yang benar
curl -X POST http://43.133.137.52:3000/send/message \
  -H "Content-Type: application/json" \
  -H "X-API-Key: GQJLPguHbA4r8bT8v4K8TB7OW7L6xzww" \
  -d '{
    "phone": "6281234567890",
    "message": "Test dari terminal"
  }'
```

**Response yang Diharapkan:**
```json
{
  "status": true,
  "message": "Message sent successfully",
  "id": "..."
}
```

### Step 5: Cek Firewall (Jika Masih Error)

```bash
# Ubuntu/Debian
sudo ufw status
sudo ufw allow 3000/tcp

# CentOS/RHEL
sudo firewall-cmd --list-all
sudo firewall-cmd --permanent --add-port=3000/tcp
sudo firewall-cmd --reload

# Cek apakah port bisa diakses dari luar
# Dari komputer lokal Anda:
telnet 43.133.137.52 3000
# atau
nc -zv 43.133.137.52 3000
```

### Step 6: Cek Endpoint yang Benar

GOWA API menggunakan salah satu endpoint ini:

| Endpoint | Method | Description |
|----------|--------|-------------|
| `/send/message` | POST | Send text message (kemungkinan besar ini) |
| `/api/send/message` | POST | Alternative endpoint |
| `/send/text` | POST | Alternative endpoint |
| `/api/sendText` | POST | Alternative endpoint |

**Kode sudah diperbaiki** untuk mencoba semua endpoint secara otomatis.

### Step 7: Test Lagi dari Aplikasi

Setelah GOWA running dan WhatsApp connected:

1. Refresh halaman **Pengaturan WhatsApp**
2. Klik **Edit** pada setting yang ada
3. Pastikan:
   - API Token: `GQJLPguHbA4r8bT8v4K8TB7OW7L6xzww`
   - API URL: `http://43.133.137.52:3000`
4. Klik **Test Koneksi**
5. Masukkan nomor HP Anda (format: 6281234567890)
6. **Submit**

**Seharusnya sekarang berhasil!** ✅

## 🔍 Debugging Lebih Lanjut

### Cek Laravel Logs

```bash
# Di server aplikasi Laravel
tail -f storage/logs/laravel.log | grep -i gowa
```

Output yang berguna:
```
[INFO] Trying endpoint: send/message
[INFO] ✅ Success with endpoint: send/message
[INFO] GOWA API Response: {"status":true,"message":"..."}
```

### Cek GOWA Logs

```bash
# Di server GOWA
docker logs -f gowa
```

### Verify GOWA API Key

```bash
# Cek environment variable di container
docker inspect gowa | grep WHATSAPP_API_KEY

# Output harus sama dengan yang di aplikasi Laravel
"WHATSAPP_API_KEY=GQJLPguHbA4r8bT8v4K8TB7OW7L6xzww"
```

## 📋 Checklist Troubleshooting

- [ ] Server 43.133.137.52 bisa diakses
- [ ] Port 3000 terbuka (cek firewall)
- [ ] GOWA container running (`docker ps`)
- [ ] GOWA accessible via browser (http://43.133.137.52:3000)
- [ ] WhatsApp sudah scan QR dan status "Connected"
- [ ] API Key sama di GOWA dan Laravel app
- [ ] Test API manual berhasil (curl)
- [ ] Laravel logs tidak ada error
- [ ] Test koneksi dari UI berhasil

## 🎯 Quick Fix Commands

```bash
# 1. SSH ke server
ssh user@43.133.137.52

# 2. Stop GOWA jika ada
docker stop gowa
docker rm gowa

# 3. Start fresh GOWA
docker run -d \
  --name gowa \
  -p 3000:3000 \
  -e WHATSAPP_API_KEY=GQJLPguHbA4r8bT8v4K8TB7OW7L6xzww \
  -v gowa_data:/app/storages \
  --restart unless-stopped \
  aldinokemal/go-whatsapp-web-multidevice:latest

# 4. Cek logs
docker logs -f gowa

# 5. Open browser: http://43.133.137.52:3000
# 6. Scan QR Code
# 7. Test dari aplikasi Laravel
```

## ❓ FAQ

**Q: Masih error 404 setelah GOWA running**
```
A: Cek endpoint API. Kode sudah diperbaiki untuk auto-detect endpoint yang benar.
   Cek logs untuk melihat endpoint mana yang berhasil.
```

**Q: Connection refused**
```
A: 
1. GOWA belum running → start Docker container
2. Port blocked → buka firewall
3. Server down → cek server status
```

**Q: 401 Unauthorized**
```
A: API Key salah. Pastikan:
- WHATSAPP_API_KEY di Docker = API Token di Laravel
- Sama persis, case-sensitive
```

**Q: WhatsApp disconnected**
```
A: 
1. Buka http://43.133.137.52:3000
2. Login/scan QR lagi
3. Jangan logout di HP
```

---

**Last Updated:** October 21, 2025  
**Issue:** Error 404 Not Found  
**Status:** ✅ Fixed - Auto-detect endpoint

