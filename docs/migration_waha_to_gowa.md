# Migrasi dari WAHA ke GOWA

## 📋 Overview

Aplikasi ini telah dimigrasi dari **WAHA (WhatsApp HTTP API)** ke **GOWA (go-whatsapp-web-multidevice)** untuk WhatsApp gateway.

**GOWA Repository:** https://github.com/aldinokemal/go-whatsapp-web-multidevice

## 🎯 Alasan Migrasi

1. **Open Source**: GOWA adalah fully open-source
2. **Self-Hosted**: Kontrol penuh atas data dan server
3. **Efficient**: Dibangun dengan Go untuk penggunaan memory yang efisien
4. **Active Development**: Aktif dikembangkan dan maintained
5. **Feature Rich**: Mendukung banyak fitur WhatsApp termasuk multi-device

## 🔄 Perubahan yang Dilakukan

### 1. Configuration File

**File:** `config/whatsapp.php`

```php
// Sebelumnya (WAHA)
'api_token' => env('WAHA_API_TOKEN', ''),
'api_url' => env('WAHA_API_URL', 'https://waha-xxx.com'),

// Sekarang (GOWA)
'api_token' => env('GOWA_API_TOKEN', ''),
'api_url' => env('GOWA_API_URL', 'http://localhost:3000'),
```

### 2. WhatsApp Service

**File:** `app/Services/WhatsAppService.php`

**Endpoint Changes:**
```php
// WAHA Endpoints
POST api/sendText
POST api/sendImage
POST api/sendFile

// GOWA Endpoints
POST send/text
POST send/image
POST send/file
```

**Request Format Changes:**
```php
// WAHA Format
{
    "chatId": "62xxx@c.us",
    "text": "message",
    "session": "default"
}

// GOWA Format
{
    "phone": "62xxx",
    "message": "message"
}
```

**Response Format Changes:**
```php
// WAHA Response
{
    "id": "xxx",
    "key": "xxx",
    "message": "xxx"
}

// GOWA Response
{
    "status": true,
    "id": "xxx",
    "message": "xxx"
}
```

### 3. UI & Settings

**File:** `app/Filament/Resources/WhatsAppSettingResource.php`

- Updated helper texts dari WAHA ke GOWA
- Updated instructions untuk setup API Key
- Updated default API URL ke `http://localhost:3000`
- **Removed Session field** (GOWA tidak menggunakan session)
- Added link ke GOWA documentation

### 4. Database Seeder

**File:** `database/seeders/WhatsAppSettingSeeder.php`

```php
// Sebelumnya
'api_url' => 'https://waha-xxx.com'
'session' => 'default'

// Sekarang (session field dihapus karena GOWA tidak menggunakannya)
'api_url' => env('GOWA_API_URL', 'http://localhost:3000')
```

### 5. Jobs & Logging

**File:** `app/Jobs/SendWhatsAppMessage.php`

- Updated log messages dari "WAHA" ke "GOWA"
- Updated response handling untuk GOWA format

### 6. Documentation

**Files Updated:**
- `docs/troubleshooting_whatsapp_401_error.md`
- `docs/whatsapp_pdf_invoice_feature.md`
- Added `docs/migration_waha_to_gowa.md` (this file)

## 🚀 Setup GOWA Server

### Option 1: Docker (Recommended)

```bash
docker run -d \
  --name gowa \
  -p 3000:3000 \
  -e WHATSAPP_API_KEY=your-secret-api-key \
  -v gowa_data:/app/storages \
  aldinokemal/go-whatsapp-web-multidevice:latest
```

### Option 2: Docker Compose

Create `docker-compose.yml`:

```yaml
version: '3.8'
services:
  gowa:
    image: aldinokemal/go-whatsapp-web-multidevice:latest
    container_name: gowa
    ports:
      - "3000:3000"
    environment:
      - WHATSAPP_API_KEY=your-secret-api-key
    volumes:
      - gowa_data:/app/storages
    restart: unless-stopped

volumes:
  gowa_data:
```

Then run:
```bash
docker-compose up -d
```

### Option 3: Binary

1. Download binary dari [releases page](https://github.com/aldinokemal/go-whatsapp-web-multidevice/releases)
2. Extract dan jalankan:
```bash
export WHATSAPP_API_KEY=your-secret-api-key
./whatsapp
```

## 📱 Connecting WhatsApp

1. **Akses Dashboard GOWA**
   - Buka browser: `http://localhost:3000`
   
2. **Scan QR Code**
   - Buka WhatsApp di HP → Settings → Linked Devices
   - Scan QR Code yang muncul di dashboard GOWA
   
3. **Verify Connection**
   - Tunggu hingga status "Connected"
   - Test dengan send message

## ⚙️ Konfigurasi di Aplikasi

### 1. Update Environment Variables

Add to `.env`:
```env
GOWA_API_TOKEN=your-secret-api-key
GOWA_API_URL=http://localhost:3000
```

Jika menggunakan remote server:
```env
GOWA_API_TOKEN=your-secret-api-key
GOWA_API_URL=https://wa.yourdomain.com
```

### 2. Update Database Settings

Jika sudah ada WhatsApp settings di database:

```sql
UPDATE whatsapp_settings 
SET 
  api_url = 'http://localhost:3000',
  api_token = 'your-secret-api-key',
  session = 'default'
WHERE is_active = 1;
```

Atau update via UI:
- Menu: **WhatsApp → Pengaturan WhatsApp**
- Edit pengaturan yang aktif
- Update API URL dan API Token
- Save

### 3. Test Connection

1. Buka menu **WhatsApp → Pengaturan WhatsApp**
2. Klik **Edit** pada setting yang aktif
3. Klik **Test Koneksi**
4. Masukkan nomor WhatsApp Anda
5. Verify menerima pesan test

## 🔍 Troubleshooting

### Issue 1: Connection Refused

**Error:** `Connection refused`

**Solution:**
- Pastikan GOWA server running
- Check port 3000 terbuka
- Check firewall settings
- Verify API URL benar

### Issue 2: 401 Unauthorized

**Error:** `401 Unauthorized`

**Solution:**
- Pastikan API Token sudah di-set
- Verify API Token sama dengan `WHATSAPP_API_KEY` di GOWA
- Check header `X-API-Key` terkirim

### Issue 3: WhatsApp Disconnected

**Error:** `WhatsApp not connected`

**Solution:**
- Buka dashboard GOWA
- Scan QR Code lagi
- Tunggu hingga status "Connected"

### Issue 4: Message Not Sent

**Error:** `Failed to send message`

**Solution:**
- Check GOWA logs: `docker logs gowa`
- Verify nomor HP format benar (62xxx)
- Test dengan nomor Anda sendiri dulu
- Check internet connection

## 📊 Monitoring

### Check GOWA Status

```bash
# Via API
curl http://localhost:3000/app/sessions

# Via Docker logs
docker logs -f gowa
```

### Check Laravel Logs

```bash
tail -f storage/logs/laravel.log | grep -i gowa
```

### Database Monitoring

```sql
-- Check recent messages
SELECT id, customer_id, status, message, sent_at, response
FROM whats_app_messages
ORDER BY id DESC
LIMIT 20;

-- Check success rate
SELECT 
  status,
  COUNT(*) as count,
  ROUND(COUNT(*) * 100.0 / SUM(COUNT(*)) OVER(), 2) as percentage
FROM whats_app_messages
WHERE created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)
GROUP BY status;
```

## 🔐 Security

### API Key Best Practices

1. **Use Strong API Key**
   ```bash
   # Generate random key
   openssl rand -hex 32
   ```

2. **Store Securely**
   - Never commit to git
   - Use `.env` file
   - Backup di password manager

3. **Restrict Access**
   - Firewall rules untuk port 3000
   - Gunakan HTTPS jika production
   - IP whitelist jika perlu

### SSL/TLS Setup (Production)

Jika deploy production, gunakan reverse proxy dengan SSL:

```nginx
server {
    listen 443 ssl;
    server_name wa.yourdomain.com;
    
    ssl_certificate /path/to/cert.pem;
    ssl_certificate_key /path/to/key.pem;
    
    location / {
        proxy_pass http://localhost:3000;
        proxy_set_header Host $host;
        proxy_set_header X-Real-IP $remote_addr;
    }
}
```

## 📈 Performance

### GOWA vs WAHA

| Feature | GOWA | WAHA |
|---------|------|------|
| Language | Go | Node.js |
| Memory | ~50-100MB | ~200-500MB |
| CPU Usage | Low | Medium |
| Response Time | <100ms | ~200ms |
| Multi-Device | ✅ Yes | ✅ Yes |
| Open Source | ✅ Yes | ❌ Limited |

### Optimization Tips

1. **Use Docker**
   - Easier deployment
   - Resource limiting
   - Auto-restart

2. **Monitor Resources**
   ```bash
   docker stats gowa
   ```

3. **Regular Cleanup**
   - Clean old session files
   - Rotate logs
   - Archive old messages

## 🎯 Migration Checklist

### Pre-Migration
- [ ] Install GOWA server
- [ ] Generate API Key
- [ ] Test GOWA connection
- [ ] Backup current settings

### Migration
- [ ] Update code (sudah done)
- [ ] Update environment variables
- [ ] Update database settings
- [ ] Test connection dari aplikasi

### Post-Migration
- [ ] Verify WhatsApp connected
- [ ] Test send message
- [ ] Test send document
- [ ] Monitor logs selama 24 jam
- [ ] Update documentation untuk team

## 📚 Resources

- **GOWA GitHub:** https://github.com/aldinokemal/go-whatsapp-web-multidevice
- **GOWA Docker Hub:** https://hub.docker.com/r/aldinokemal/go-whatsapp-web-multidevice
- **API Documentation:** Check GOWA README untuk endpoint details
- **Support:** GitHub Issues di GOWA repository

## ❓ FAQ

**Q: Apakah data customer hilang setelah migrasi?**  
A: **TIDAK**. Semua data customer, payment, dan message history tetap aman di database.

**Q: Apakah perlu setup ulang templates?**  
A: **TIDAK**. Template WhatsApp tidak berubah, hanya API gateway-nya yang berbeda.

**Q: Apakah bisa rollback ke WAHA?**  
A: **YA**, tapi perlu update kembali kode dan settings. Backup dulu sebelum migrasi.

**Q: Apakah GOWA gratis?**  
A: **YA**, GOWA fully open-source dan gratis. Anda hanya perlu server untuk menjalankannya.

**Q: Apakah perlu WhatsApp Business?**  
A: **TIDAK**. GOWA bisa digunakan dengan WhatsApp regular atau Business.

**Q: Apakah support semua fitur WhatsApp?**  
A: **YA**, GOWA support text, image, document, video, audio, location, contact, dll.

---

**Migration Date:** October 21, 2025  
**Status:** ✅ Completed  
**Breaking Changes:** API URL and format changed, requires GOWA server setup

