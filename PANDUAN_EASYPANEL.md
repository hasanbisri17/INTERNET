# 🚀 Panduan Deploy ke EasyPanel

Panduan lengkap untuk deploy **Internet Management System** ke VPS menggunakan EasyPanel.

---

## 📋 Prerequisites

Sebelum memulai, pastikan:

- ✅ VPS dengan minimal 2GB RAM, 2 CPU cores, 20GB storage
- ✅ EasyPanel sudah terinstall di VPS
- ✅ Domain sudah pointing ke IP VPS (opsional tapi recommended)

---

## 🎯 Metode Deployment

### Metode 1: Build dari Source (Recommended)

#### Step 1: Upload Source Code

1. **Compress project** (exclude node_modules, vendor):
```bash
# Di local machine
tar -czvf internet-management.tar.gz \
    --exclude='node_modules' \
    --exclude='vendor' \
    --exclude='.git' \
    --exclude='storage/logs/*' \
    --exclude='temp-upload' \
    .
```

2. **Upload ke VPS**:
```bash
scp internet-management.tar.gz user@your-vps-ip:/opt/
```

3. **Extract di VPS**:
```bash
ssh user@your-vps-ip
cd /opt
mkdir internet-management
tar -xzvf internet-management.tar.gz -C internet-management
```

#### Step 2: Deploy di EasyPanel

1. **Login ke EasyPanel Dashboard**
   - Akses: `https://your-vps-ip:3000` atau domain EasyPanel Anda

2. **Create New Project**
   - Klik **"+ New Project"**
   - Pilih nama project: `internet-management`

3. **Add App Service**
   - Klik **"+ App"** atau **"Create Service"**
   - Pilih **"Docker Compose"**

4. **Configure Docker Compose**
   - Copy isi file `docker-compose.production.yml`
   - Atau gunakan konfigurasi berikut:

```yaml
services:
  app:
    build:
      context: /opt/internet-management
      dockerfile: Dockerfile
    ports:
      - "1217:1217"
    environment:
      - APP_NAME=Internet Management
      - APP_ENV=production
      - APP_DEBUG=false
      - APP_URL=https://your-domain.com
      - DB_CONNECTION=mysql
      - DB_HOST=mysql
      - DB_PORT=3306
      - DB_DATABASE=internet_management
      - DB_USERNAME=root
      - DB_PASSWORD=YourSecurePassword123!
      - RUN_MIGRATIONS=true
      - RUN_SEEDERS=true
    volumes:
      - app_storage:/var/www/html/storage
    depends_on:
      mysql:
        condition: service_healthy
    healthcheck:
      test: ["CMD", "curl", "-f", "http://localhost:1217/health"]
      interval: 30s
      timeout: 10s
      retries: 3
      start_period: 120s

  mysql:
    image: mysql:8.0
    environment:
      - MYSQL_ROOT_PASSWORD=YourSecurePassword123!
      - MYSQL_DATABASE=internet_management
    volumes:
      - mysql_data:/var/lib/mysql
    healthcheck:
      test: ["CMD", "mysqladmin", "ping", "-h", "localhost"]
      interval: 10s
      timeout: 5s
      retries: 5
      start_period: 30s

volumes:
  mysql_data:
  app_storage:
```

5. **Set Environment Variables**
   - Tab **"Environment"** → Add variables:
   
| Variable | Value | Keterangan |
|----------|-------|------------|
| `APP_NAME` | Internet Management | Nama aplikasi |
| `APP_URL` | https://your-domain.com | URL akses |
| `DB_PASSWORD` | (password kuat) | Password database |
| `MAIL_MAILER` | smtp | Jika perlu email |
| `WHATSAPP_API_URL` | (URL GOWA) | Untuk integrasi WA |

6. **Configure Domain**
   - Tab **"Domains"** → Add domain
   - Enable **SSL/HTTPS**

7. **Deploy!**
   - Klik **"Deploy"**
   - Tunggu proses build selesai (5-10 menit)

---

### Metode 2: Pull dari Docker Hub

Jika Anda sudah push image ke Docker Hub:

```yaml
services:
  app:
    image: your-dockerhub-username/internet-management:latest
    ports:
      - "1217:1217"
    environment:
      - APP_NAME=Internet Management
      - APP_ENV=production
      - APP_URL=https://your-domain.com
      - DB_HOST=mysql
      - DB_DATABASE=internet_management
      - DB_USERNAME=root
      - DB_PASSWORD=YourSecurePassword123!
      - RUN_MIGRATIONS=true
      - RUN_SEEDERS=true
    volumes:
      - storage:/var/www/html/storage
    depends_on:
      - mysql

  mysql:
    image: mysql:8.0
    environment:
      - MYSQL_ROOT_PASSWORD=YourSecurePassword123!
      - MYSQL_DATABASE=internet_management
    volumes:
      - mysql_data:/var/lib/mysql

volumes:
  mysql_data:
  storage:
```

---

## ⚙️ Konfigurasi Lengkap

### Environment Variables Wajib

```env
# Application
APP_NAME=Internet Management
APP_ENV=production
APP_DEBUG=false
APP_URL=https://your-domain.com

# Database
DB_CONNECTION=mysql
DB_HOST=mysql
DB_PORT=3306
DB_DATABASE=internet_management
DB_USERNAME=root
DB_PASSWORD=YourSecurePassword123!

# Auto-setup
RUN_MIGRATIONS=true
RUN_SEEDERS=true
```

### Environment Variables Opsional

```env
# Cache & Session
CACHE_DRIVER=file
SESSION_DRIVER=file
QUEUE_CONNECTION=database

# Email
MAIL_MAILER=smtp
MAIL_HOST=smtp.gmail.com
MAIL_PORT=587
MAIL_USERNAME=your-email@gmail.com
MAIL_PASSWORD=your-app-password
MAIL_FROM_ADDRESS=noreply@your-domain.com

# WhatsApp Integration
WHATSAPP_API_URL=http://your-gowa-url
WHATSAPP_API_TOKEN=your-token

# Mikrotik Router Integration
MIKROTIK_HOST=192.168.1.1
MIKROTIK_USERNAME=admin
MIKROTIK_PASSWORD=router-password
```

---

## 🔧 Post-Deployment Setup

### 1. Verifikasi Instalasi

```bash
# Check container status
docker ps

# Check logs
docker logs internet-app

# Test health endpoint
curl http://localhost:1217/health
```

Response yang diharapkan:
```json
{
  "status": "ok",
  "timestamp": "2024-01-01T00:00:00.000000Z",
  "version": "1.0.0",
  "database": "connected"
}
```

### 2. Akses Aplikasi

| URL | Keterangan |
|-----|------------|
| `https://your-domain.com` | Redirect ke admin |
| `https://your-domain.com/admin` | Panel Admin |
| `https://your-domain.com/health` | Health Check |

### 3. Login Default

```
Email: admin@example.com
Password: password
```

> ⚠️ **PENTING**: Segera ganti password default setelah login pertama!

### 4. Konfigurasi Tambahan (Opsional)

Masuk ke container untuk konfigurasi manual:
```bash
docker exec -it internet-app bash

# Clear cache
php artisan optimize:clear

# Re-cache config
php artisan config:cache

# Generate key baru (jika perlu)
php artisan key:generate --force
```

---

## 🔄 Update Aplikasi

### Via Git Pull

```bash
# SSH ke VPS
cd /opt/internet-management

# Pull changes
git pull origin main

# Rebuild container
docker-compose -f docker-compose.production.yml build
docker-compose -f docker-compose.production.yml up -d
```

### Via EasyPanel

1. Build ulang di EasyPanel Dashboard
2. Atau setup auto-deploy dari GitHub

---

## 🚨 Troubleshooting

### Container tidak start

```bash
# Check logs
docker logs internet-app --tail 100

# Biasanya karena:
# 1. Database belum ready - tunggu MySQL healthy
# 2. Permission error - check storage permissions
# 3. ENV tidak lengkap - check environment variables
```

### Database connection failed

```bash
# Test koneksi dari container
docker exec -it internet-app php artisan migrate:status

# Reset database
docker exec -it internet-app php artisan migrate:fresh --seed --force
```

### Permission Error

```bash
docker exec -it internet-app bash
chown -R www-data:www-data /var/www/html/storage
chmod -R 775 /var/www/html/storage
```

### Health Check Failed

```bash
# Check nginx & php-fpm
docker exec -it internet-app ps aux | grep -E "nginx|php-fpm"

# Restart services
docker restart internet-app
```

---

## 📊 Monitoring

### Resource Usage

```bash
docker stats internet-app internet-mysql
```

### Application Logs

```bash
# Laravel logs
docker exec -it internet-app tail -f /var/www/html/storage/logs/laravel.log

# Nginx logs
docker exec -it internet-app tail -f /var/log/nginx/error.log

# Queue logs
docker exec -it internet-app tail -f /var/log/supervisor/queue.log
```

---

## 💾 Backup

### Database

```bash
# Backup
docker exec internet-mysql mysqldump -u root -p internet_management > backup.sql

# Restore
docker exec -i internet-mysql mysql -u root -p internet_management < backup.sql
```

### Application Files

```bash
# Backup storage
docker cp internet-app:/var/www/html/storage ./backup-storage
```

---

## ✅ Checklist Deployment

- [ ] VPS ready dengan EasyPanel
- [ ] Source code uploaded
- [ ] Docker Compose configured
- [ ] Environment variables set
- [ ] Domain configured (optional)
- [ ] SSL enabled (optional)
- [ ] Deploy berhasil
- [ ] Health check passed
- [ ] Login test berhasil
- [ ] Password default diganti
- [ ] Backup strategy configured

---

## 🎉 Selesai!

Aplikasi Internet Management System Anda sekarang sudah berjalan di EasyPanel!

**Support**: Jika ada masalah, check logs terlebih dahulu sebelum troubleshooting lebih lanjut.
