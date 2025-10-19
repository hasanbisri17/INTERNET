# EasyPanel Deployment Guide

Panduan lengkap untuk deploy aplikasi Internet Management di EasyPanel menggunakan Docker image yang sudah di-push ke Docker Hub.

## 🚀 Quick Start

### 1. Login ke EasyPanel
- Buka EasyPanel dashboard
- Login dengan akun Anda

### 2. Create New Project
- Klik **"New Project"** atau **"Create Project"**
- Pilih **"Docker"** sebagai project type
- Beri nama project: `internet-management`

### 3. Deploy dari Docker Hub
- Pilih **"Deploy from Docker Hub"**
- Masukkan image name: `habis12/internet-management:latest`
- Klik **"Deploy"**

## ⚙️ Konfigurasi Environment Variables

Setelah project dibuat, konfigurasi environment variables:

```env
APP_NAME=Internet Management
APP_ENV=production
APP_KEY=base64:your-app-key-here
APP_DEBUG=false
APP_URL=https://your-domain.com
DB_CONNECTION=mysql
DB_HOST=mysql
DB_PORT=3306
DB_DATABASE=internet_management
DB_USERNAME=root
DB_PASSWORD=password
CACHE_DRIVER=file
SESSION_DRIVER=file
QUEUE_CONNECTION=sync
MAIL_MAILER=log
LOG_CHANNEL=stack
```

## 🗄️ Database Setup

### Option 1: EasyPanel MySQL Service
1. **Create MySQL Service**:
   - Service Type: `MySQL`
   - Version: `8.0`
   - Database Name: `internet_management`
   - Username: `root`
   - Password: `password`

2. **Update Environment Variables**:
   ```env
   DB_HOST=mysql-service-name
   DB_PORT=3306
   DB_DATABASE=internet_management
   DB_USERNAME=root
   DB_PASSWORD=password
   ```

### Option 2: External MySQL
- Gunakan MySQL dari provider lain (PlanetScale, Railway, dll)
- Update environment variables sesuai dengan kredensial external

## 🌐 Domain & SSL Setup

1. **Add Domain**:
   - Masuk ke project settings
   - Klik **"Domains"**
   - Add domain: `your-domain.com`

2. **SSL Certificate**:
   - EasyPanel akan otomatis generate SSL certificate
   - Atau upload custom certificate jika ada

## 📋 Port Configuration

- **Application Port**: `1217` (internal)
- **External Port**: EasyPanel akan handle port mapping
- **Health Check**: `/health`

## 🔧 Advanced Configuration

### 1. Resource Limits
```yaml
CPU: 1 core
Memory: 1GB
Storage: 10GB
```

### 2. Auto Restart
- Enable **"Auto Restart"** untuk restart otomatis jika crash
- Enable **"Health Check"** dengan endpoint `/health`

### 3. Environment Variables Template
```env
# Application
APP_NAME=Internet Management
APP_ENV=production
APP_KEY=base64:your-32-character-key
APP_DEBUG=false
APP_URL=https://your-domain.com

# Database
DB_CONNECTION=mysql
DB_HOST=mysql
DB_PORT=3306
DB_DATABASE=internet_management
DB_USERNAME=root
DB_PASSWORD=your-secure-password

# Cache & Session
CACHE_DRIVER=file
SESSION_DRIVER=file
QUEUE_CONNECTION=sync

# Mail
MAIL_MAILER=log
MAIL_FROM_ADDRESS=admin@your-domain.com
MAIL_FROM_NAME=Internet Management

# Logging
LOG_CHANNEL=stack
LOG_LEVEL=info
```

## 🚀 Deployment Steps

### Step 1: Create Project
1. Login ke EasyPanel
2. Click **"New Project"**
3. Select **"Docker"**
4. Name: `internet-management`

### Step 2: Configure Docker Compose
1. **Select "Docker Compose"** instead of single image
2. **Copy the configuration below**:

```yaml
services:
  app:
    image: habis12/internet-management:latest
    restart: unless-stopped
    ports:
      - "1217:1217"
    environment:
      - APP_NAME=Internet Management
      - APP_ENV=production
      - APP_KEY=base64:${APP_KEY}
      - APP_DEBUG=false
      - APP_URL=${APP_URL}
      - DB_CONNECTION=mysql
      - DB_HOST=mysql
      - DB_PORT=3306
      - DB_DATABASE=internet_management
      - DB_USERNAME=root
      - DB_PASSWORD=${DB_PASSWORD}
      - CACHE_DRIVER=file
      - SESSION_DRIVER=file
      - QUEUE_CONNECTION=sync
      - MAIL_MAILER=log
      - LOG_CHANNEL=stack
    volumes:
      - storage:/var/www/html/storage
      - public:/var/www/html/public
    networks:
      - internet-network
    depends_on:
      - mysql
    healthcheck:
      test: ["CMD", "curl", "-f", "http://localhost:1217/health"]
      interval: 30s
      timeout: 10s
      retries: 3
      start_period: 40s

  mysql:
    image: mysql:8.0
    restart: unless-stopped
    environment:
      - MYSQL_ROOT_PASSWORD=${DB_PASSWORD}
      - MYSQL_DATABASE=internet_management
      - MYSQL_USER=internet_user
      - MYSQL_PASSWORD=internet_password
    volumes:
      - mysql_data:/var/lib/mysql
    networks:
      - internet-network
    ports:
      - "3306:3306"

networks:
  internet-network:
    driver: bridge

volumes:
  mysql_data:
  storage:
  public:
```

### Step 3: Set Environment Variables
1. Go to **"Environment"** tab
2. Add these environment variables:
   ```env
   APP_KEY=base64:your-32-character-key-here
   APP_URL=https://your-domain.com
   DB_PASSWORD=your-secure-password
   ```
3. Generate secure `APP_KEY`:
   ```bash
   openssl rand -base64 32
   ```

### Step 4: Deploy
1. Click **"Deploy"**
2. Wait for deployment to complete
3. Check logs for any errors

### Step 5: Setup Application
1. **Access container**:
   ```bash
   # Via EasyPanel terminal or SSH
   docker exec -it internet-management-app-1 bash
   ```

2. **Run setup commands**:
   ```bash
   # Run migrations
   php artisan migrate --force
   
   # Seed database
   php artisan db:seed --force
   
   # Generate app key
   php artisan key:generate --force
   
   # Setup storage
   php artisan storage:link
   
   # Set permissions
   chown -R www-data:www-data /var/www/html/storage
   chmod -R 755 /var/www/html/storage
   ```

## 🔍 Monitoring & Maintenance

### 1. Health Check
- **Endpoint**: `https://your-domain.com/health`
- **Expected Response**: `{"status":"ok","timestamp":"...","version":"1.0.0","database":"connected"}`

### 2. Logs
- Access logs via EasyPanel dashboard
- Check application logs: `/var/www/html/storage/logs/`
- Check nginx logs: `/var/log/nginx/`

### 3. Backup
- **Database**: Export via EasyPanel MySQL service
- **Files**: Backup storage volume
- **Configuration**: Export environment variables

## 🚨 Troubleshooting

### Common Issues

1. **Application not starting**:
   - Check environment variables
   - Verify database connection
   - Check logs for errors

2. **Database connection failed**:
   - Verify database credentials
   - Check if MySQL service is running
   - Test connection from container

3. **Permission issues**:
   ```bash
   chown -R www-data:www-data /var/www/html/storage
   chmod -R 755 /var/www/html/storage
   ```

4. **SSL issues**:
   - Check domain configuration
   - Verify SSL certificate
   - Check nginx configuration

### Useful Commands

```bash
# Check container status
docker ps

# View logs
docker logs internet-management-container

# Access container
docker exec -it internet-management-container bash

# Check database connection
php artisan migrate:status

# Clear cache
php artisan cache:clear
php artisan config:clear
php artisan route:clear
php artisan view:clear
```

## 📞 Support

Jika mengalami masalah:

1. **Check EasyPanel logs** untuk error messages
2. **Verify environment variables** sudah benar
3. **Test database connection** dari container
4. **Check resource limits** (CPU/Memory)
5. **Contact EasyPanel support** jika masalah persisten

## 🎯 Production Tips

1. **Security**:
   - Gunakan password database yang kuat
   - Set `APP_DEBUG=false`
   - Enable SSL/HTTPS

2. **Performance**:
   - Set resource limits yang sesuai
   - Enable OPcache (sudah included)
   - Monitor memory usage

3. **Monitoring**:
   - Setup health check monitoring
   - Monitor logs regularly
   - Setup backup schedule

4. **Updates**:
   - Update image secara berkala
   - Test di staging environment dulu
   - Backup sebelum update

## ✅ Checklist Deployment

- [ ] Project created in EasyPanel
- [ ] Docker image pulled from Docker Hub
- [ ] Environment variables configured
- [ ] Database service created/configured
- [ ] Domain added and SSL enabled
- [ ] Application deployed successfully
- [ ] Health check passing
- [ ] Database migrations run
- [ ] Application accessible via domain
- [ ] Backup strategy implemented

**🎉 Selamat! Aplikasi Internet Management sudah siap digunakan di EasyPanel!**