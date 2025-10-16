# 🚀 Panduan Deployment VPS dengan GitHub dan Docker

## Overview

Panduan lengkap untuk mengupload aplikasi ke GitHub dan melakukan deployment otomatis di VPS menggunakan Docker.

## 📋 Prerequisites

### VPS Requirements
- **OS**: Ubuntu 20.04+ atau CentOS 8+
- **RAM**: Minimal 2GB (Recommended: 4GB+)
- **Storage**: Minimal 20GB SSD
- **CPU**: Minimal 2 cores
- **Network**: Public IP dengan domain yang sudah di-point ke VPS

### Software Requirements
- Docker & Docker Compose
- Git
- Certbot (untuk SSL)
- UFW (untuk firewall)

## 🔧 Setup GitHub Repository

### 1. Buat Repository di GitHub

```bash
# Di local machine
git init
git add .
git commit -m "Initial commit: Internet Management System"

# Buat repository di GitHub, lalu:
git remote add origin https://github.com/yourusername/internet-management-system.git
git branch -M main
git push -u origin main
```

### 2. Struktur Repository

```
internet-management-system/
├── app/
├── config/
├── database/
├── resources/
├── routes/
├── public/
├── docker/
│   ├── apache/
│   ├── nginx/
│   ├── mysql/
│   └── supervisor/
├── docker-compose.vps.yml
├── Dockerfile.prod
├── env.vps
├── vps-deploy.sh
├── README.md
└── .gitignore
```

### 3. File .gitignore

```gitignore
# Laravel
/vendor/
/node_modules/
/public/build/
/public/hot
/public/storage
/storage/*.key
/storage/app/public
/storage/framework/cache
/storage/framework/sessions
/storage/framework/views
/storage/logs
/bootstrap/cache

# Environment
.env
.env.local
.env.production

# Docker
docker-compose.override.yml

# IDE
.vscode/
.idea/
*.swp
*.swo

# OS
.DS_Store
Thumbs.db
```

## 🐳 Setup VPS

### 1. Persiapan VPS

```bash
# Update system
sudo apt update && sudo apt upgrade -y

# Install essential packages
sudo apt install -y curl wget git unzip software-properties-common

# Install Docker
curl -fsSL https://get.docker.com -o get-docker.sh
sudo sh get-docker.sh
sudo usermod -aG docker $USER

# Install Docker Compose
sudo curl -L "https://github.com/docker/compose/releases/latest/download/docker-compose-$(uname -s)-$(uname -m)" -o /usr/local/bin/docker-compose
sudo chmod +x /usr/local/bin/docker-compose

# Install Certbot untuk SSL
sudo apt install -y certbot

# Install UFW untuk firewall
sudo apt install -y ufw
```

### 2. Konfigurasi Domain

```bash
# Pastikan domain sudah di-point ke VPS
# Test dengan:
nslookup your-domain.com

# Pastikan port 80 dan 443 terbuka
sudo ufw allow 80
sudo ufw allow 443
sudo ufw allow ssh
sudo ufw enable
```

## 🚀 Deployment Otomatis

### 1. Menggunakan Script Otomatis

```bash
# Clone script deployment
wget https://raw.githubusercontent.com/yourusername/internet-management-system/main/vps-deploy.sh
chmod +x vps-deploy.sh

# Edit konfigurasi
nano vps-deploy.sh

# Isi konfigurasi:
GITHUB_REPO="https://github.com/yourusername/internet-management-system.git"
DOMAIN="your-domain.com"
EMAIL="your-email@example.com"

# Jalankan deployment
./vps-deploy.sh
```

### 2. Deployment Manual

```bash
# Clone repository
git clone https://github.com/yourusername/internet-management-system.git
cd internet-management-system

# Setup environment
cp env.vps .env
nano .env  # Edit sesuai kebutuhan

# Generate APP_KEY
docker run --rm -v $(pwd):/app -w /app php:8.2-cli php -r "echo 'base64:' . base64_encode(random_bytes(32));"

# Setup SSL
sudo certbot certonly --standalone -d your-domain.com --email your-email@example.com --agree-tos

# Copy SSL certificates
sudo mkdir -p docker/ssl
sudo cp /etc/letsencrypt/live/your-domain.com/fullchain.pem docker/ssl/
sudo cp /etc/letsencrypt/live/your-domain.com/privkey.pem docker/ssl/
sudo chown -R $USER:$USER docker/ssl/

# Deploy aplikasi
docker-compose -f docker-compose.vps.yml up -d --build

# Setup database
sleep 30
docker exec internet_app_prod php artisan migrate --force
docker exec internet_app_prod php artisan db:seed --force
```

## 🔧 Konfigurasi Environment

### File .env untuk VPS

```env
# Application
APP_NAME="Internet Management System"
APP_ENV=production
APP_KEY=base64:your-generated-key
APP_DEBUG=false
APP_URL=https://your-domain.com

# Database
DB_CONNECTION=mysql
DB_HOST=db
DB_PORT=3306
DB_DATABASE=internet_db
DB_USERNAME=internet_user
DB_PASSWORD=your_secure_password
DB_ROOT_PASSWORD=your_root_password

# Cache & Session
CACHE_DRIVER=redis
SESSION_DRIVER=redis
QUEUE_CONNECTION=redis

# Redis
REDIS_HOST=redis
REDIS_PORT=6379

# Mail
MAIL_MAILER=smtp
MAIL_HOST=smtp.gmail.com
MAIL_PORT=587
MAIL_USERNAME=your-email@gmail.com
MAIL_PASSWORD=your-app-password
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS=your-email@gmail.com
MAIL_FROM_NAME="${APP_NAME}"

# Security
SANCTUM_STATEFUL_DOMAINS=your-domain.com
SESSION_DOMAIN=your-domain.com

# Timezone
APP_TIMEZONE=Asia/Jakarta
```

## 📊 Monitoring dan Maintenance

### 1. Cek Status Aplikasi

```bash
# Cek status container
docker-compose -f docker-compose.vps.yml ps

# Cek logs
docker-compose -f docker-compose.vps.yml logs -f app

# Cek resource usage
docker stats
```

### 2. Backup Database

```bash
# Backup harian
docker exec internet_db_prod mysqldump -u internet_user -p internet_db > backup_$(date +%Y%m%d).sql

# Restore backup
docker exec -i internet_db_prod mysql -u internet_user -p internet_db < backup_20240115.sql
```

### 3. Update Aplikasi

```bash
# Pull update dari GitHub
git pull origin main

# Rebuild dan restart
docker-compose -f docker-compose.vps.yml up -d --build

# Clear cache
docker exec internet_app_prod php artisan optimize:clear
```

### 4. SSL Renewal

```bash
# Test renewal
sudo certbot renew --dry-run

# Setup auto-renewal
echo "0 12 * * * /usr/bin/certbot renew --quiet" | sudo crontab -
```

## 🔒 Security Best Practices

### 1. Firewall Configuration

```bash
# Konfigurasi UFW
sudo ufw default deny incoming
sudo ufw default allow outgoing
sudo ufw allow ssh
sudo ufw allow 80
sudo ufw allow 443
sudo ufw enable
```

### 2. Database Security

```bash
# Buat user database khusus
docker exec -it internet_db_prod mysql -u root -p
CREATE USER 'internet_user'@'%' IDENTIFIED BY 'strong_password';
GRANT ALL PRIVILEGES ON internet_db.* TO 'internet_user'@'%';
FLUSH PRIVILEGES;
```

### 3. Application Security

```bash
# Set proper permissions
sudo chown -R www-data:www-data /var/www/html
sudo chmod -R 755 /var/www/html/storage
sudo chmod -R 755 /var/www/html/bootstrap/cache
```

## 🚨 Troubleshooting

### 1. Aplikasi Tidak Bisa Diakses

```bash
# Cek status container
docker-compose -f docker-compose.vps.yml ps

# Cek logs
docker-compose -f docker-compose.vps.yml logs app

# Cek firewall
sudo ufw status

# Cek port
sudo netstat -tlnp | grep :80
```

### 2. Database Connection Error

```bash
# Cek database container
docker exec -it internet_db_prod mysql -u root -p

# Test connection
docker exec internet_app_prod php artisan tinker
DB::connection()->getPdo();
```

### 3. SSL Certificate Issues

```bash
# Cek certificate
sudo certbot certificates

# Renew certificate
sudo certbot renew

# Test SSL
curl -I https://your-domain.com
```

## 📈 Performance Optimization

### 1. Database Optimization

```bash
# Optimize MySQL
docker exec internet_db_prod mysql -u root -p
OPTIMIZE TABLE users, customers, payments;
```

### 2. Application Optimization

```bash
# Clear cache
docker exec internet_app_prod php artisan optimize

# Queue processing
docker exec internet_app_prod php artisan queue:work --daemon
```

### 3. Nginx Optimization

```nginx
# Edit nginx.conf
worker_processes auto;
worker_connections 1024;
keepalive_timeout 65;
gzip on;
gzip_types text/plain text/css application/json application/javascript text/xml application/xml;
```

## 🔄 CI/CD dengan GitHub Actions (Opsional)

### 1. Setup GitHub Actions

```yaml
# .github/workflows/deploy.yml
name: Deploy to VPS

on:
  push:
    branches: [ main ]

jobs:
  deploy:
    runs-on: ubuntu-latest
    steps:
    - uses: actions/checkout@v2
    
    - name: Deploy to VPS
      uses: appleboy/ssh-action@v0.1.5
      with:
        host: ${{ secrets.VPS_HOST }}
        username: ${{ secrets.VPS_USERNAME }}
        key: ${{ secrets.VPS_SSH_KEY }}
        script: |
          cd /home/user/internet-management-system
          git pull origin main
          docker-compose -f docker-compose.vps.yml up -d --build
          docker exec internet_app_prod php artisan optimize:clear
```

### 2. Setup Secrets di GitHub

- `VPS_HOST`: IP address VPS
- `VPS_USERNAME`: Username VPS
- `VPS_SSH_KEY`: Private SSH key

## 📞 Support

Jika mengalami masalah:

1. **Cek logs**: `docker-compose logs -f app`
2. **Cek status**: `docker-compose ps`
3. **Cek resources**: `docker stats`
4. **Cek network**: `docker network ls`

## 🎯 Keunggulan Setup Ini

- ✅ **Otomatis**: Script deployment otomatis
- ✅ **Scalable**: Mudah untuk scaling horizontal
- ✅ **Secure**: SSL, firewall, dan security best practices
- ✅ **Maintainable**: Easy update dan maintenance
- ✅ **Monitoring**: Built-in health checks dan logging
- ✅ **Backup**: Automated backup system
- ✅ **CI/CD**: GitHub Actions integration

---

**Happy Deploying! 🚀**
