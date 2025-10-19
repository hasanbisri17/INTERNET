# EasyPanel Troubleshooting Guide

Panduan troubleshooting untuk deployment Internet Management di EasyPanel.

## 🚨 Common Errors & Solutions

### 1. Version Attribute Obsolete Error

**Error**: `the attribute 'version' is obsolete, it will be ignored`

**Solution**:
- Hapus baris `version: '3.8'` dari docker-compose.yml
- EasyPanel tidak memerlukan version attribute

**Correct Format**:
```yaml
services:
  app:
    image: habis12/internet-management:latest
    # ... rest of configuration
```

### 2. Container Name Conflicts

**Error**: `container_name is used in app. It might cause conflicts with other services`

**Solution**:
- Hapus `container_name` dari konfigurasi
- EasyPanel akan generate nama container otomatis

**Before**:
```yaml
services:
  app:
    image: habis12/internet-management:latest
    container_name: internet-app  # ❌ Remove this
```

**After**:
```yaml
services:
  app:
    image: habis12/internet-management:latest
    # ✅ No container_name needed
```

### 3. Port Conflicts

**Error**: `ports is used in app. It might cause conflicts with other services`

**Solution**:
- Pastikan port 1217 tidak digunakan oleh service lain
- Atau gunakan port yang berbeda

**Alternative Port**:
```yaml
services:
  app:
    image: habis12/internet-management:latest
    ports:
      - "8080:1217"  # Use port 8080 instead
```

### 4. Environment Variables Not Working

**Error**: Environment variables tidak ter-load

**Solution**:
1. Pastikan format environment variables benar:
   ```yaml
   environment:
     - APP_KEY=base64:your-key-here
     - APP_URL=https://your-domain.com
   ```

2. Atau gunakan format object:
   ```yaml
   environment:
     APP_KEY: "base64:your-key-here"
     APP_URL: "https://your-domain.com"
   ```

### 5. Database Connection Failed

**Error**: Database connection timeout

**Solution**:
1. Pastikan MySQL service sudah running
2. Check environment variables:
   ```yaml
   environment:
     - DB_HOST=mysql
     - DB_PORT=3306
     - DB_DATABASE=internet_management
     - DB_USERNAME=root
     - DB_PASSWORD=${DB_PASSWORD}
   ```

3. Pastikan `depends_on` ada:
   ```yaml
   depends_on:
     - mysql
   ```

### 6. Volume Mount Issues

**Error**: Storage permission denied

**Solution**:
1. Pastikan volume sudah didefinisikan:
   ```yaml
   volumes:
     - storage:/var/www/html/storage
     - public:/var/www/html/public
   ```

2. Set permissions setelah container running:
   ```bash
   docker exec internet-management-app-1 chown -R www-data:www-data /var/www/html/storage
   docker exec internet-management-app-1 chmod -R 755 /var/www/html/storage
   ```

## 🔧 Step-by-Step Fix

### 1. Clean Configuration

Gunakan konfigurasi yang sudah diperbaiki:

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
    volumes:
      - storage:/var/www/html/storage
      - public:/var/www/html/public
    networks:
      - internet-network
    depends_on:
      - mysql

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

networks:
  internet-network:
    driver: bridge

volumes:
  mysql_data:
  storage:
  public:
```

### 2. Set Environment Variables

Di EasyPanel Environment tab, set:

```env
APP_KEY=base64:your-32-character-key-here
APP_URL=https://your-domain.com
DB_PASSWORD=your-secure-password
```

### 3. Deploy

1. Click **"Deploy"**
2. Wait for services to start
3. Check logs for errors

### 4. Setup Application

```bash
# Access container
docker exec -it internet-management-app-1 bash

# Run setup
php artisan migrate --force
php artisan db:seed --force
php artisan key:generate --force
php artisan storage:link
chown -R www-data:www-data /var/www/html/storage
chmod -R 755 /var/www/html/storage
```

## 🔍 Debugging Commands

### Check Container Status
```bash
docker ps
docker logs internet-management-app-1
docker logs internet-management-mysql-1
```

### Check Environment Variables
```bash
docker exec internet-management-app-1 env | grep APP_
docker exec internet-management-app-1 env | grep DB_
```

### Test Database Connection
```bash
docker exec internet-management-app-1 php artisan migrate:status
```

### Check Health
```bash
curl http://your-domain.com:1217/health
```

## 📞 Support

Jika masih mengalami masalah:

1. **Check Logs**: Lihat logs di EasyPanel dashboard
2. **Verify Configuration**: Pastikan konfigurasi sesuai panduan
3. **Test Locally**: Test dengan Docker Compose lokal dulu
4. **Contact Support**: Hubungi tim support EasyPanel

## ✅ Checklist

- [ ] Hapus `version` dari docker-compose.yml
- [ ] Hapus `container_name` dari semua services
- [ ] Set environment variables dengan benar
- [ ] Pastikan port tidak conflict
- [ ] Deploy dan check logs
- [ ] Run setup commands
- [ ] Test aplikasi
