# 🚀 EasyPanel VPS Deployment Guide
# Internet Management System

## 📋 Prerequisites

- VPS dengan EasyPanel terinstall
- Domain yang sudah di-point ke VPS (opsional)
- Repository GitHub (tidak harus public)

## 🔧 Step-by-Step Deployment

### 1. Persiapan Repository

#### Option A: Repository Public (Recommended)
```bash
# Repository sudah public, langsung bisa digunakan
Repository URL: https://github.com/hasanbisri17/INTERNET.git
```

#### Option B: Repository Private
```bash
# Jika repository private, gunakan Personal Access Token
# 1. Buat Personal Access Token di GitHub
# 2. Gunakan format: https://username:token@github.com/hasanbisri17/INTERNET.git
```

### 2. Konfigurasi di EasyPanel

#### A. Buat New Project
1. Login ke EasyPanel
2. Klik "New Project"
3. Pilih "Git Repository"
4. Masukkan repository URL:
   ```
   https://github.com/hasanbisri17/INTERNET.git
   ```

#### B. Konfigurasi Build
1. **Dockerfile Path**: `Dockerfile.easypanel`
2. **Build Context**: `.` (root directory)
3. **Branch**: `main`

#### C. Environment Variables
Copy dari `env.easypanel` dan sesuaikan:

```env
APP_NAME="Internet Management System"
APP_ENV=production
APP_KEY=base64:YOUR_APP_KEY_HERE
APP_DEBUG=false
APP_URL=https://your-domain.com

# Database (SQLite - tidak perlu konfigurasi tambahan)
DB_CONNECTION=sqlite
DB_DATABASE=/var/www/html/database/database.sqlite

# Cache
CACHE_DRIVER=file
SESSION_DRIVER=file

# Security
SANCTUM_STATEFUL_DOMAINS=your-domain.com
SESSION_DOMAIN=your-domain.com
SESSION_SECURE_COOKIE=true

# Timezone
APP_TIMEZONE=Asia/Jakarta

# WhatsApp (sesuaikan dengan konfigurasi Anda)
WHATSAPP_API_URL=https://graph.facebook.com/v18.0
WHATSAPP_ACCESS_TOKEN=your-whatsapp-token
WHATSAPP_PHONE_NUMBER_ID=your-phone-number-id
WHATSAPP_WEBHOOK_VERIFY_TOKEN=your-webhook-token

# Mikrotik (sesuaikan dengan konfigurasi Anda)
MIKROTIK_API_URL=http://192.168.1.1
MIKROTIK_USERNAME=admin
MIKROTIK_PASSWORD=your-mikrotik-password
```

#### D. Port Configuration
- **Container Port**: `80`
- **Public Port**: `8080` (atau port yang diinginkan)

### 3. Deploy Application

1. Klik "Deploy" di EasyPanel
2. Tunggu proses build selesai (biasanya 5-10 menit)
3. Monitor logs untuk memastikan tidak ada error

### 4. Post-Deployment Setup

#### A. Generate APP_KEY
Jika belum ada APP_KEY, jalankan:
```bash
# Di EasyPanel terminal atau SSH ke VPS
docker exec -it your-container-name php artisan key:generate
```

#### B. Run Migrations
```bash
docker exec -it your-container-name php artisan migrate --force
```

#### C. Seed Database
```bash
docker exec -it your-container-name php artisan db:seed --force
```

#### D. Create Storage Link
```bash
docker exec -it your-container-name php artisan storage:link
```

### 5. Akses Application

#### Default Login Credentials:
- **Email**: `admin@example.com`
- **Password**: `password`

⚠️ **PENTING**: Ganti password default setelah login pertama!

## 🔧 Troubleshooting

### Problem: Build Failed
**Solution**:
1. Pastikan menggunakan `Dockerfile.easypanel`
2. Check logs untuk error spesifik
3. Pastikan repository accessible

### Problem: Application Not Starting
**Solution**:
1. Check environment variables
2. Pastikan APP_KEY sudah di-generate
3. Check logs: `docker logs your-container-name`

### Problem: Database Error
**Solution**:
1. Pastikan SQLite database file ada
2. Check permissions: `chmod 644 database/database.sqlite`
3. Run migrations: `php artisan migrate --force`

### Problem: Permission Denied
**Solution**:
```bash
# Set proper permissions
docker exec -it your-container-name chown -R www-data:www-data /var/www/html
docker exec -it your-container-name chmod -R 755 /var/www/html/storage
```

### Problem: WhatsApp Not Working
**Solution**:
1. Pastikan webhook URL accessible dari internet
2. Check WhatsApp credentials
3. Verify webhook token

## 📊 Monitoring

### Health Check
Application memiliki health check endpoint:
```
GET /health
```

### Logs
```bash
# View application logs
docker logs your-container-name

# View Laravel logs
docker exec -it your-container-name tail -f storage/logs/laravel.log
```

## 🔄 Updates

Untuk update application:
1. Push changes ke GitHub
2. Di EasyPanel, klik "Redeploy"
3. Tunggu build selesai

## 📞 Support

Jika mengalami masalah:
1. Check logs terlebih dahulu
2. Pastikan semua environment variables sudah benar
3. Verify repository accessibility
4. Check VPS resources (RAM, CPU, Disk)

## 🎯 Best Practices

1. **Backup**: Regular backup database dan storage
2. **Monitoring**: Setup monitoring untuk uptime
3. **Security**: Update password default dan enable HTTPS
4. **Performance**: Monitor resource usage
5. **Updates**: Regular update dependencies

---

**Note**: Repository tidak harus public untuk digunakan di EasyPanel. Anda bisa menggunakan Personal Access Token untuk repository private.
