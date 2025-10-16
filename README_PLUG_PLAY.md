# 🚀 Internet Management System - Plug & Play

**Seperti Portainer, WAHA, dan aplikasi Docker lainnya - langsung bisa digunakan setelah `docker-compose up`!**

## ⚡ Quick Start

### 1. Clone Repository
```bash
git clone https://github.com/yourusername/internet-management-system.git
cd internet-management-system
```

### 2. Start Application
```bash
docker-compose -f docker-compose.standalone.yml up -d
```

### 3. Access Application
- **URL**: http://localhost:8080
- **Admin Panel**: http://localhost:8080/admin
- **Health Check**: http://localhost:8080/health

### 4. Login
- **Email**: admin@example.com
- **Password**: password

**That's it! 🎉**

## 🔧 Port Configuration

Aplikasi menggunakan port yang tidak bentrok dengan aplikasi lain:

- **8080** - Web Application (HTTP)
- **8443** - Web Application (HTTPS)
- **6379** - Redis (optional)
- **3306** - MySQL (optional)

## 📋 Features

- ✅ **Auto Setup** - Database migration dan seeding otomatis
- ✅ **SQLite Database** - Tidak perlu setup database eksternal
- ✅ **Health Checks** - Monitoring kesehatan aplikasi
- ✅ **Persistent Data** - Data tersimpan dalam Docker volumes
- ✅ **Auto Restart** - Restart otomatis jika crash
- ✅ **Background Tasks** - Queue dan scheduler berjalan otomatis

## 🐳 Docker Services

### Main Service
- **internet-app** - Aplikasi utama (PHP 8.2 + Apache)

### Optional Services
- **redis** - Cache dan session storage (profile: redis)
- **mysql** - Database MySQL (profile: mysql)

## 🔧 Configuration

### Environment Variables
```env
# Application
APP_NAME=Internet Management System
APP_URL=http://localhost:8080
APP_TIMEZONE=Asia/Jakarta

# Database (SQLite default)
DB_CONNECTION=sqlite
DB_DATABASE=/var/www/html/database/database.sqlite

# Cache
CACHE_DRIVER=file
SESSION_DRIVER=file
```

### Custom Configuration
Buat file `custom.env` untuk override konfigurasi:
```env
APP_NAME=My Internet Management
APP_URL=http://my-domain.com:8080
```

## 📊 Monitoring

### Health Check
```bash
curl http://localhost:8080/health
```

Response:
```json
{
    "status": "ok",
    "timestamp": "2024-01-15T10:30:00Z",
    "version": "1.0.0",
    "database": "connected"
}
```

### Container Status
```bash
docker-compose -f docker-compose.standalone.yml ps
```

### Logs
```bash
docker-compose -f docker-compose.standalone.yml logs -f internet-app
```

## 🔄 Updates

### Update Application
```bash
git pull origin main
docker-compose -f docker-compose.standalone.yml up -d --build
```

### Reset Application
```bash
docker-compose -f docker-compose.standalone.yml down -v
docker-compose -f docker-compose.standalone.yml up -d
```

## 💾 Data Persistence

Data tersimpan dalam Docker volumes:
- **internet_data** - Storage files
- **internet_database** - SQLite database
- **internet_logs** - Application logs

### Backup Data
```bash
# Backup database
docker cp internet-management-system:/var/www/html/database/database.sqlite ./backup.sqlite

# Backup storage
docker cp internet-management-system:/var/www/html/storage ./backup-storage
```

### Restore Data
```bash
# Restore database
docker cp ./backup.sqlite internet-management-system:/var/www/html/database/database.sqlite

# Restore storage
docker cp ./backup-storage internet-management-system:/var/www/html/storage
```

## 🚀 Production Deployment

### Dengan Domain
```bash
# Edit docker-compose.standalone.yml
# Ubah APP_URL ke domain Anda
APP_URL=https://your-domain.com

# Start dengan domain
docker-compose -f docker-compose.standalone.yml up -d
```

### Dengan Reverse Proxy (Nginx/Traefik)
```bash
# Gunakan labels yang sudah ada di docker-compose
# Aplikasi akan otomatis terdeteksi oleh Traefik
```

## 🔒 Security

- **Non-root user** - Aplikasi berjalan dengan user www-data
- **Security headers** - XSS, CSRF protection
- **File permissions** - Proper file permissions
- **Health checks** - Monitoring kesehatan aplikasi

## 🛠️ Troubleshooting

### Application Tidak Bisa Diakses
```bash
# Cek status container
docker-compose -f docker-compose.standalone.yml ps

# Cek logs
docker-compose -f docker-compose.standalone.yml logs internet-app

# Cek health
curl http://localhost:8080/health
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
# Ubah port di docker-compose.standalone.yml
ports:
  - "8081:80"  # Ubah 8080 ke 8081
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

## 🎯 Keunggulan

- ✅ **Plug & Play** - Langsung bisa digunakan
- ✅ **No Dependencies** - Tidak perlu install apapun
- ✅ **Port Management** - Port tidak bentrok
- ✅ **Auto Setup** - Setup otomatis
- ✅ **Persistent Data** - Data tidak hilang
- ✅ **Health Monitoring** - Monitoring kesehatan
- ✅ **Easy Updates** - Update mudah
- ✅ **Production Ready** - Siap untuk production

## 🤝 Support

- **Issues**: [GitHub Issues](https://github.com/yourusername/internet-management-system/issues)
- **Documentation**: [Wiki](https://github.com/yourusername/internet-management-system/wiki)

---

**Made with ❤️ for Internet Service Providers**

**Star ⭐ this repository if you find it helpful!**
