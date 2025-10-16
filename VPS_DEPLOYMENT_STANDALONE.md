# 🌐 VPS Deployment Guide - Internet Management System

## Overview

Panduan lengkap untuk deployment Internet Management System di VPS dengan konfigurasi yang benar untuk IP public dan domain.

## 🔧 Konfigurasi VPS

### Masalah dengan localhost di VPS

❌ **Yang Salah:**
```env
APP_URL=http://localhost:8080
SANCTUM_STATEFUL_DOMAINS=localhost:8080
SESSION_DOMAIN=localhost
```

✅ **Yang Benar untuk VPS:**
```env
APP_URL=http://192.168.1.100:8080
SANCTUM_STATEFUL_DOMAINS=192.168.1.100:8080
SESSION_DOMAIN=192.168.1.100
```

## 🚀 Cara Deployment VPS

### Metode 1: Script Otomatis

```bash
# Download script deployment
wget https://raw.githubusercontent.com/yourusername/internet-management-system/main/vps-deploy-standalone.sh
chmod +x vps-deploy-standalone.sh

# Edit konfigurasi
nano vps-deploy-standalone.sh

# Isi konfigurasi:
VPS_IP="192.168.1.100"  # Ganti dengan IP VPS Anda
VPS_DOMAIN="your-domain.com"  # Ganti dengan domain Anda (opsional)
APP_PORT="8080"  # Port aplikasi
GITHUB_REPO="https://github.com/yourusername/internet-management-system.git"

# Jalankan deployment
./vps-deploy-standalone.sh
```

### Metode 2: Manual

```bash
# Clone repository
git clone https://github.com/yourusername/internet-management-system.git
cd internet-management-system

# Setup environment untuk VPS
cp env.vps-standalone .env

# Edit konfigurasi
nano .env

# Isi konfigurasi VPS:
VPS_MODE=true
VPS_IP=192.168.1.100
VPS_DOMAIN=your-domain.com
APP_PORT=8080

# Deploy aplikasi
docker-compose -f docker-compose.standalone.yml up -d --build
```

## 📋 Konfigurasi Environment

### File .env untuk VPS

```env
# VPS Configuration
VPS_MODE=true
VPS_IP=192.168.1.100
VPS_DOMAIN=your-domain.com
APP_PORT=8080

# Application Settings
APP_NAME="Internet Management System"
APP_ENV=production
APP_DEBUG=false
# APP_URL akan otomatis diset berdasarkan VPS_IP atau VPS_DOMAIN

# Database Configuration
DB_CONNECTION=sqlite
DB_DATABASE=/var/www/html/database/database.sqlite

# Cache Configuration
CACHE_DRIVER=file
SESSION_DRIVER=file
QUEUE_CONNECTION=sync

# Security Configuration
# SANCTUM_STATEFUL_DOMAINS akan otomatis diset
# SESSION_DOMAIN akan otomatis diset

# Timezone
APP_TIMEZONE=Asia/Jakarta
```

## 🔄 Auto Configuration

### Startup Script akan Otomatis:

1. **Deteksi VPS Mode** - Cek `VPS_MODE=true`
2. **Set APP_URL** - Berdasarkan `VPS_DOMAIN` atau `VPS_IP`
3. **Set Security Domains** - `SANCTUM_STATEFUL_DOMAINS` dan `SESSION_DOMAIN`
4. **Apply Production Settings** - `APP_ENV=production`, `APP_DEBUG=false`

### Contoh Auto Configuration:

```bash
# Jika ada domain
VPS_DOMAIN=your-domain.com
# Maka akan set:
APP_URL=https://your-domain.com:8080
SANCTUM_STATEFUL_DOMAINS=your-domain.com:8080
SESSION_DOMAIN=your-domain.com

# Jika hanya IP
VPS_IP=192.168.1.100
# Maka akan set:
APP_URL=http://192.168.1.100:8080
SANCTUM_STATEFUL_DOMAINS=192.168.1.100:8080
SESSION_DOMAIN=192.168.1.100
```

## 🌐 Akses Aplikasi

### Dengan Domain
```
https://your-domain.com:8080
https://your-domain.com:8080/admin
https://your-domain.com:8080/health
```

### Dengan IP
```
http://192.168.1.100:8080
http://192.168.1.100:8080/admin
http://192.168.1.100:8080/health
```

## 🔒 Security Configuration

### Firewall Setup

```bash
# Install UFW
sudo apt-get install -y ufw

# Konfigurasi firewall
sudo ufw default deny incoming
sudo ufw default allow outgoing
sudo ufw allow ssh
sudo ufw allow 8080
sudo ufw allow 8443
sudo ufw --force enable
```

### SSL Configuration (Opsional)

```bash
# Install Certbot
sudo apt-get install -y certbot

# Generate SSL certificate
sudo certbot certonly --standalone -d your-domain.com --email your-email@example.com --agree-tos

# Copy SSL certificates
sudo mkdir -p docker/ssl
sudo cp /etc/letsencrypt/live/your-domain.com/fullchain.pem docker/ssl/
sudo cp /etc/letsencrypt/live/your-domain.com/privkey.pem docker/ssl/
sudo chown -R $USER:$USER docker/ssl/
```

## 📊 Monitoring

### Health Check

```bash
# Test aplikasi
curl http://192.168.1.100:8080/health

# Response:
{
    "status": "ok",
    "timestamp": "2024-01-15T10:30:00Z",
    "version": "1.0.0",
    "database": "connected"
}
```

### Container Status

```bash
# Cek status container
docker-compose -f docker-compose.standalone.yml ps

# Cek logs
docker-compose -f docker-compose.standalone.yml logs -f internet-app
```

## 🔄 Updates

### Update Aplikasi

```bash
# Pull update dari GitHub
git pull origin main

# Rebuild dan restart
docker-compose -f docker-compose.standalone.yml up -d --build

# Clear cache
docker exec internet-management-system php artisan optimize:clear
```

### Reset Aplikasi

```bash
# Stop dan hapus data
docker-compose -f docker-compose.standalone.yml down -v

# Start ulang
docker-compose -f docker-compose.standalone.yml up -d
```

## 🛠️ Troubleshooting

### Aplikasi Tidak Bisa Diakses dari Luar

```bash
# Cek firewall
sudo ufw status

# Cek port binding
sudo netstat -tlnp | grep :8080

# Cek Docker port mapping
docker port internet-management-system
```

### Database Issues

```bash
# Cek database file
docker exec internet-management-system ls -la /var/www/html/database/

# Reset database
docker exec internet-management-system rm /var/www/html/database/database.sqlite
docker-compose -f docker-compose.standalone.yml restart internet-app
```

### Port Conflicts

```bash
# Ubah port di .env
APP_PORT=8081

# Restart aplikasi
docker-compose -f docker-compose.standalone.yml up -d
```

## 📈 Performance

### Resource Usage
- **RAM**: ~200-300MB
- **CPU**: Minimal usage
- **Storage**: ~500MB base + data

### Optimization

```bash
# Enable Redis untuk performa lebih baik
docker-compose -f docker-compose.standalone.yml --profile redis up -d

# Enable MySQL untuk production
docker-compose -f docker-compose.standalone.yml --profile mysql up -d
```

## 🎯 Keunggulan VPS Setup

- ✅ **Auto Configuration** - Konfigurasi otomatis berdasarkan VPS_IP/VPS_DOMAIN
- ✅ **Security** - Firewall dan security headers
- ✅ **Monitoring** - Health checks dan logging
- ✅ **Easy Updates** - Update mudah dengan Git
- ✅ **Production Ready** - Optimized untuk production
- ✅ **No Manual Setup** - Tidak perlu konfigurasi manual

## 📞 Support

Jika mengalami masalah:

1. **Cek logs**: `docker-compose logs -f internet-app`
2. **Cek status**: `docker-compose ps`
3. **Cek health**: `curl http://your-ip:8080/health`
4. **Cek firewall**: `sudo ufw status`

---

**Happy VPS Deploying! 🚀**
