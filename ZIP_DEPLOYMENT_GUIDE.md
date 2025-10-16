# 📦 Panduan Upload Manual Menggunakan ZIP

## Overview

Panduan lengkap untuk mengupload dan menjalankan Internet Management System menggunakan ZIP file tanpa Git atau GitHub.

## 📋 Prerequisites VPS

### System Requirements
- **OS**: Ubuntu 20.04+ atau CentOS 8+
- **RAM**: Minimal 2GB (Recommended: 4GB+)
- **Storage**: Minimal 20GB SSD
- **CPU**: Minimal 2 cores
- **Network**: Public IP dengan domain (opsional)

### Software Requirements
- Docker & Docker Compose
- Unzip
- UFW (untuk firewall)
- Curl

## 🚀 Langkah-langkah Upload Manual

### 1. Persiapan ZIP File

#### Di Local Machine:
```bash
# Buat ZIP file dari project
zip -r internet-management-system.zip . -x "*.git*" "node_modules/*" "vendor/*" "storage/logs/*" "storage/framework/cache/*" "storage/framework/sessions/*" "storage/framework/views/*" ".env"
```

#### Atau menggunakan GUI:
1. Pilih semua file dan folder di project
2. Exclude folder: `.git`, `node_modules`, `vendor`, `storage/logs`, `storage/framework/cache`, `storage/framework/sessions`, `storage/framework/views`
3. Exclude file: `.env`
4. Buat ZIP file

### 2. Upload ke VPS

#### Metode 1: SCP
```bash
# Upload ZIP file ke VPS
scp internet-management-system.zip user@your-vps-ip:/home/user/
```

#### Metode 2: SFTP
```bash
# Connect ke VPS
sftp user@your-vps-ip

# Upload file
put internet-management-system.zip

# Exit
quit
```

#### Metode 3: Web Upload
1. Upload ZIP file melalui cPanel File Manager
2. Atau menggunakan FTP client seperti FileZilla

### 3. Setup VPS

#### Login ke VPS
```bash
ssh user@your-vps-ip
```

#### Install Prerequisites
```bash
# Update system
sudo apt update && sudo apt upgrade -y

# Install essential packages
sudo apt install -y curl wget unzip software-properties-common

# Install Docker
curl -fsSL https://get.docker.com -o get-docker.sh
sudo sh get-docker.sh
sudo usermod -aG docker $USER

# Install Docker Compose
sudo curl -L "https://github.com/docker/compose/releases/latest/download/docker-compose-$(uname -s)-$(uname -m)" -o /usr/local/bin/docker-compose
sudo chmod +x /usr/local/bin/docker-compose

# Install UFW untuk firewall
sudo apt install -y ufw
```

### 4. Deploy Aplikasi

#### Metode 1: Script Otomatis

```bash
# Download script deployment
wget https://raw.githubusercontent.com/yourusername/internet-management-system/main/zip-deploy.sh
chmod +x zip-deploy.sh

# Edit konfigurasi
nano zip-deploy.sh

# Isi konfigurasi:
ZIP_FILE="internet-management-system.zip"
VPS_IP="192.168.1.100"  # Ganti dengan IP VPS Anda
VPS_DOMAIN="your-domain.com"  # Ganti dengan domain Anda (opsional)
APP_PORT="8080"  # Port aplikasi

# Jalankan deployment
./zip-deploy.sh
```

#### Metode 2: Manual

```bash
# Extract ZIP file
unzip internet-management-system.zip
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

# Setup firewall
sudo ufw default deny incoming
sudo ufw default allow outgoing
sudo ufw allow ssh
sudo ufw allow 8080
sudo ufw allow 8443
sudo ufw --force enable

# Deploy aplikasi
docker-compose -f docker-compose.standalone.yml up -d --build
```

## 🔧 Konfigurasi Environment

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

# Auto-setup Configuration
AUTO_SETUP=true
AUTO_MIGRATE=true
AUTO_SEED=true
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
# Upload ZIP file baru
scp internet-management-system-v2.zip user@your-vps-ip:/home/user/

# Di VPS
unzip -o internet-management-system-v2.zip
cd internet-management-system

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

### ZIP File Tidak Bisa Diextract

```bash
# Cek apakah ZIP file valid
unzip -t internet-management-system.zip

# Cek permission
ls -la internet-management-system.zip

# Extract dengan verbose
unzip -v internet-management-system.zip
```

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

### Permission Issues

```bash
# Fix permissions
docker exec internet-management-system chown -R www-data:www-data /var/www/html
docker exec internet-management-system chmod -R 755 /var/www/html/storage
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

## 🔒 Security

### Firewall Configuration
```bash
# Konfigurasi UFW
sudo ufw default deny incoming
sudo ufw default allow outgoing
sudo ufw allow ssh
sudo ufw allow 8080
sudo ufw allow 8443
sudo ufw enable
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

## 📋 Checklist Deployment

### Sebelum Upload
- [ ] ZIP file sudah dibuat dengan benar
- [ ] File yang tidak perlu sudah di-exclude
- [ ] Environment file sudah disiapkan

### Setelah Upload
- [ ] ZIP file berhasil diextract
- [ ] Environment file sudah dikonfigurasi
- [ ] Docker dan Docker Compose sudah terinstall
- [ ] Firewall sudah dikonfigurasi
- [ ] Aplikasi berhasil di-deploy
- [ ] Health check berhasil
- [ ] Aplikasi bisa diakses dari luar

### Verifikasi
- [ ] Aplikasi bisa diakses di browser
- [ ] Admin panel bisa diakses
- [ ] Login dengan default credentials berhasil
- [ ] Database migration dan seeding berhasil
- [ ] Health check endpoint berfungsi

## 🎯 Keunggulan ZIP Deployment

- ✅ **No Git Required** - Tidak perlu Git atau GitHub
- ✅ **Simple Upload** - Upload ZIP file saja
- ✅ **One-Time Setup** - Setup sekali, jalan selamanya
- ✅ **Easy Updates** - Update dengan ZIP file baru
- ✅ **Portable** - Bisa di-deploy di VPS manapun
- ✅ **Self-Contained** - Semua dependencies include

## 📞 Support

Jika mengalami masalah:

1. **Cek logs**: `docker-compose logs -f internet-app`
2. **Cek status**: `docker-compose ps`
3. **Cek health**: `curl http://your-ip:8080/health`
4. **Cek firewall**: `sudo ufw status`

---

**Happy ZIP Deploying! 📦**
