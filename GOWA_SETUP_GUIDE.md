# 🚀 Panduan Setup GOWA untuk WhatsApp Gateway

Panduan lengkap instalasi dan konfigurasi GOWA (go-whatsapp-web-multidevice) untuk aplikasi ini.

## 📋 Apa itu GOWA?

**GOWA** adalah WhatsApp Web Multi-Device API yang dibangun dengan Golang. Lebih efisien, open-source, dan mudah di-deploy.

**Repository:** https://github.com/aldinokemal/go-whatsapp-web-multidevice

## ⚡ Quick Start (Recommended)

### 1. Install GOWA dengan Docker

```bash
docker run -d \
  --name gowa \
  -p 3000:3000 \
  -e WHATSAPP_API_KEY=GQJLPguHbA4r8bT8v4K8TB7OW7L6xzww \
  -v gowa_data:/app/storages \
  --restart unless-stopped \
  aldinokemal/go-whatsapp-web-multidevice:latest
```

**Penjelasan:**
- `-p 3000:3000` → Expose port 3000
- `-e WHATSAPP_API_KEY=xxx` → **INI API TOKEN ANDA** (ganti dengan key yang aman)
- `-v gowa_data:/app/storages` → Persistent storage untuk session WhatsApp
- `--restart unless-stopped` → Auto restart jika container mati

### 2. Akses Dashboard GOWA

Buka browser: `http://localhost:3000` atau `http://43.133.137.52:3000`

### 3. Login WhatsApp

1. Di dashboard GOWA, klik **"Login"** atau **"Add Device"**
2. Scan QR Code dengan WhatsApp Anda:
   - Buka WhatsApp → **Settings** → **Linked Devices**
   - Tap **"Link a Device"**
   - Scan QR Code yang muncul
3. Tunggu hingga status **"Connected"** ✅

### 4. Update Aplikasi Laravel

Edit file `.env`:

```env
GOWA_API_TOKEN=GQJLPguHbA4r8bT8v4K8TB7OW7L6xzww
GOWA_API_URL=http://43.133.137.52:3000
```

**⚠️ PENTING:** API Token harus **SAMA** dengan `WHATSAPP_API_KEY` di Docker!

### 5. Update Settings di UI

1. Login ke aplikasi Laravel
2. Menu: **WhatsApp → Pengaturan WhatsApp**
3. Klik **Edit**
4. Isi:
   - **API Token:** `GQJLPguHbA4r8bT8v4K8TB7OW7L6xzww`
   - **API URL:** `http://43.133.137.52:3000`
   - **Kode Negara:** `62`
5. Klik **Test Koneksi** untuk verify
6. **Save changes**

### 6. Test Kirim Pesan

Di form test koneksi, masukkan nomor WhatsApp Anda (contoh: `6281234567890`) dan cek apakah pesan test terkirim.

## 🔐 Generate API Key yang Aman

Jangan gunakan API key yang mudah ditebak! Generate yang secure:

```bash
# Linux/Mac dengan OpenSSL
openssl rand -hex 32

# Output contoh:
# a1b2c3d4e5f6g7h8i9j0k1l2m3n4o5p6q7r8s9t0u1v2w3x4y5z6a7b8

# Atau online generator:
# https://www.uuidgenerator.net/
```

## 📦 Alternatif Setup

### Opsi 1: Docker Compose (Lebih Terorganisir)

Buat file `docker-compose.yml`:

```yaml
version: '3.8'

services:
  gowa:
    image: aldinokemal/go-whatsapp-web-multidevice:latest
    container_name: gowa-whatsapp
    ports:
      - "3000:3000"
    environment:
      - WHATSAPP_API_KEY=your-very-secure-api-key-here
      - WHATSAPP_WEBHOOK_URL=  # Opsional untuk webhook
    volumes:
      - gowa_data:/app/storages
    restart: unless-stopped
    networks:
      - whatsapp_network

volumes:
  gowa_data:
    driver: local

networks:
  whatsapp_network:
    driver: bridge
```

Jalankan:
```bash
docker-compose up -d
```

### Opsi 2: Binary Executable (Tanpa Docker)

1. Download binary dari [releases page](https://github.com/aldinokemal/go-whatsapp-web-multidevice/releases)

2. Extract dan jalankan:
```bash
# Linux/Mac
export WHATSAPP_API_KEY=your-api-key
chmod +x whatsapp
./whatsapp

# Windows
set WHATSAPP_API_KEY=your-api-key
whatsapp.exe
```

## 🌐 Setup untuk Production (VPS/Cloud)

### 1. Install di VPS

SSH ke server Anda:

```bash
ssh user@43.133.137.52

# Install Docker jika belum
curl -fsSL https://get.docker.com -o get-docker.sh
sudo sh get-docker.sh

# Jalankan GOWA
docker run -d \
  --name gowa \
  -p 3000:3000 \
  -e WHATSAPP_API_KEY=ProductionSecureKey2025 \
  -v /opt/gowa/data:/app/storages \
  --restart unless-stopped \
  aldinokemal/go-whatsapp-web-multidevice:latest
```

### 2. Setup Reverse Proxy dengan Nginx (Opsional)

Untuk menggunakan SSL/HTTPS:

```nginx
server {
    listen 80;
    server_name wa.yourdomain.com;
    return 301 https://$server_name$request_uri;
}

server {
    listen 443 ssl http2;
    server_name wa.yourdomain.com;

    ssl_certificate /etc/letsencrypt/live/wa.yourdomain.com/fullchain.pem;
    ssl_certificate_key /etc/letsencrypt/live/wa.yourdomain.com/privkey.pem;

    location / {
        proxy_pass http://localhost:3000;
        proxy_http_version 1.1;
        proxy_set_header Upgrade $http_upgrade;
        proxy_set_header Connection 'upgrade';
        proxy_set_header Host $host;
        proxy_cache_bypass $http_upgrade;
        proxy_set_header X-Real-IP $remote_addr;
        proxy_set_header X-Forwarded-For $proxy_add_x_forwarded_for;
        proxy_set_header X-Forwarded-Proto $scheme;
    }
}
```

Lalu update di aplikasi:
```env
GOWA_API_URL=https://wa.yourdomain.com
```

### 3. Setup Firewall

```bash
# Ubuntu/Debian
sudo ufw allow 3000/tcp
sudo ufw enable

# CentOS/RHEL
sudo firewall-cmd --permanent --add-port=3000/tcp
sudo firewall-cmd --reload
```

## 🔍 Verifikasi & Troubleshooting

### Cek Status GOWA

```bash
# Cek container running
docker ps | grep gowa

# Cek logs
docker logs gowa
docker logs -f gowa  # Follow logs realtime

# Cek status via API
curl http://localhost:3000/app/sessions
```

### Test API Manual

```bash
# Test send message
curl -X POST http://localhost:3000/send/text \
  -H "Content-Type: application/json" \
  -H "X-API-Key: GQJLPguHbA4r8bT8v4K8TB7OW7L6xzww" \
  -d '{
    "phone": "6281234567890",
    "message": "Test dari GOWA API"
  }'
```

### Common Issues

**❌ Error: Connection refused**
```
Solution:
1. Pastikan GOWA container running: docker ps
2. Pastikan port 3000 terbuka
3. Cek firewall settings
```

**❌ Error: 401 Unauthorized**
```
Solution:
1. API Token salah atau kosong
2. Pastikan X-API-Key header terkirim
3. Verify API_KEY sama di Docker dan aplikasi
```

**❌ Error: WhatsApp disconnected**
```
Solution:
1. Buka dashboard GOWA
2. Logout dan login ulang
3. Scan QR Code lagi
```

**❌ Error: Message not sent**
```
Solution:
1. Cek WhatsApp status: Connected atau tidak
2. Verify nomor HP format benar (62xxx)
3. Cek logs: docker logs gowa
```

## 📊 Monitoring

### Resource Usage

```bash
# Cek CPU & Memory usage
docker stats gowa

# Expected usage:
# CPU: 0-5%
# Memory: 50-100MB
```

### Check Recent Messages

```bash
# Via Laravel Tinker
php artisan tinker

# Di tinker console:
DB::table('whats_app_messages')->orderBy('id', 'desc')->limit(10)->get();
```

## 🔄 Maintenance

### Backup Session Data

```bash
# Backup volume
docker run --rm -v gowa_data:/data -v $(pwd):/backup ubuntu tar czf /backup/gowa-backup-$(date +%Y%m%d).tar.gz /data
```

### Restore Session

```bash
# Restore dari backup
docker run --rm -v gowa_data:/data -v $(pwd):/backup ubuntu tar xzf /backup/gowa-backup-20251021.tar.gz -C /
```

### Update GOWA ke Versi Terbaru

```bash
# Pull latest image
docker pull aldinokemal/go-whatsapp-web-multidevice:latest

# Stop current container
docker stop gowa
docker rm gowa

# Start dengan image baru
docker run -d \
  --name gowa \
  -p 3000:3000 \
  -e WHATSAPP_API_KEY=your-api-key \
  -v gowa_data:/app/storages \
  --restart unless-stopped \
  aldinokemal/go-whatsapp-web-multidevice:latest
```

## 🎯 Best Practices

1. **Gunakan API Key yang Kuat**
   - Minimal 32 karakter
   - Kombinasi huruf, angka, dan simbol
   - Jangan share ke orang lain

2. **Backup Rutin**
   - Backup session data setiap minggu
   - Backup database aplikasi
   - Simpan API key di password manager

3. **Monitor Logs**
   - Cek logs GOWA secara berkala
   - Setup alert jika ada error
   - Monitor success rate pengiriman

4. **Security**
   - Gunakan HTTPS jika production
   - Restrict akses port 3000 dari IP tertentu
   - Update GOWA ke versi terbaru

5. **Keep WhatsApp Connected**
   - Jangan logout WhatsApp di HP
   - Jangan unlink device
   - Pastikan HP selalu online

## 📚 Resources

- **GOWA GitHub:** https://github.com/aldinokemal/go-whatsapp-web-multidevice
- **GOWA Docker Hub:** https://hub.docker.com/r/aldinokemal/go-whatsapp-web-multidevice
- **API Documentation:** Check GitHub README
- **Issues & Support:** GitHub Issues

## ❓ FAQ

**Q: Apakah gratis?**  
A: Ya, GOWA 100% open-source dan gratis.

**Q: Apakah perlu WhatsApp Business?**  
A: Tidak, bisa menggunakan WhatsApp regular atau Business.

**Q: Berapa banyak pesan yang bisa dikirim?**  
A: Tergantung limit WhatsApp, biasanya ~1000 pesan/hari untuk akun baru.

**Q: Apakah data aman?**  
A: Ya, semua data tersimpan di server Anda sendiri.

**Q: Apakah bisa multi-device?**  
A: Ya, GOWA mendukung WhatsApp multi-device.

**Q: Session hilang setelah restart?**  
A: Gunakan volume (`-v gowa_data:/app/storages`) agar session persistent.

---

**Last Updated:** October 21, 2025  
**Version:** 1.0  
**Status:** ✅ Production Ready

