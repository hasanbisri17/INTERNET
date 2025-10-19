# Docker Deployment Guide

Panduan lengkap untuk deploy aplikasi Internet Management menggunakan Docker.

## 🚀 Quick Start

### 1. Build dan Push ke Docker Hub

```bash
# Build image
./build-and-push.sh your-dockerhub-username/internet-management latest

# Atau dengan nama custom
./build-and-push.sh your-dockerhub-username/internet-management v1.0.0
```

### 2. Deploy di Server

```bash
# Clone atau download script
wget https://raw.githubusercontent.com/your-repo/main/run-on-server.sh
chmod +x run-on-server.sh

# Jalankan aplikasi
./run-on-server.sh your-dockerhub-username/internet-management latest
```

## 📋 Prerequisites

- Docker Desktop terinstall dan terhubung ke Docker Hub
- Server target memiliki Docker terinstall
- Akses internet untuk pull image dari Docker Hub

## 🔧 Manual Deployment

### Build Image

```bash
# Build image
docker build -t internet-management:latest .

# Tag untuk Docker Hub
docker tag internet-management:latest your-username/internet-management:latest

# Push ke Docker Hub
docker push your-username/internet-management:latest
```

### Run di Server

```bash
# Pull image
docker pull your-username/internet-management:latest

# Run container
docker run -d \
    --name internet-app \
    --restart unless-stopped \
    -p 1217:1217 \
    -v $(pwd)/database:/var/www/html/database \
    -v $(pwd)/storage:/var/www/html/storage \
    -v $(pwd)/public:/var/www/html/public \
    -e APP_KEY="base64:$(openssl rand -base64 32)" \
    -e APP_URL="http://localhost:1217" \
    your-username/internet-management:latest
```

### Menggunakan Docker Compose

```bash
# Download docker-compose.yml
wget https://raw.githubusercontent.com/your-repo/main/docker-compose.yml

# Edit environment variables
nano docker-compose.yml

# Start aplikasi
docker-compose up -d
```

## ⚙️ Konfigurasi

### Environment Variables

Edit file `docker-compose.yml` untuk mengatur environment variables:

```yaml
environment:
  - APP_NAME=Internet Management
  - APP_ENV=production
  - APP_KEY=base64:your-app-key
  - APP_DEBUG=false
  - APP_URL=http://your-domain.com:1217
  - DB_CONNECTION=sqlite
  - DB_DATABASE=/var/www/html/database/database.sqlite
```

### Port Configuration

Aplikasi berjalan di port **1217** secara default. Untuk mengubah port:

```yaml
ports:
  - "8080:1217"  # External port:Internal port
```

## 📁 Volume Mapping

Aplikasi menggunakan volume mapping untuk:

- `./database` → `/var/www/html/database` (SQLite database)
- `./storage` → `/var/www/html/storage` (Laravel storage)
- `./public` → `/var/www/html/public` (Public assets)

## 🔍 Monitoring

### View Logs

```bash
# View all logs
docker logs internet-app

# Follow logs
docker logs -f internet-app

# View specific service logs
docker exec internet-app tail -f /var/log/supervisor/nginx.out.log
docker exec internet-app tail -f /var/log/supervisor/php-fpm.out.log
```

### Health Check

```bash
# Check container health
docker ps

# Test health endpoint
curl http://localhost:1217/health
```

## 🛠️ Maintenance

### Update Application

```bash
# Stop container
docker stop internet-app

# Pull latest image
docker pull your-username/internet-management:latest

# Remove old container
docker rm internet-app

# Start new container
./run-on-server.sh your-username/internet-management latest
```

### Backup Database

```bash
# Copy database file
cp ./database/database.sqlite ./backup-$(date +%Y%m%d).sqlite
```

### Restore Database

```bash
# Stop container
docker stop internet-app

# Replace database file
cp ./backup-20240101.sqlite ./database/database.sqlite

# Start container
docker start internet-app
```

## 🚨 Troubleshooting

### Container Won't Start

```bash
# Check logs
docker logs internet-app

# Check container status
docker ps -a

# Check resource usage
docker stats internet-app
```

### Permission Issues

```bash
# Fix storage permissions
docker exec internet-app chown -R www-data:www-data /var/www/html/storage
docker exec internet-app chmod -R 755 /var/www/html/storage
```

### Database Issues

```bash
# Check database file
ls -la ./database/

# Recreate database
docker exec internet-app php artisan migrate:fresh --seed
```

## 📞 Support

Jika mengalami masalah:

1. Check logs: `docker logs internet-app`
2. Check container status: `docker ps`
3. Check resource usage: `docker stats internet-app`
4. Restart container: `docker restart internet-app`

## 🎯 Production Tips

1. **Security**: Ganti `APP_KEY` dengan key yang aman
2. **Domain**: Set `APP_URL` dengan domain yang benar
3. **SSL**: Gunakan reverse proxy (nginx/traefik) untuk SSL
4. **Backup**: Setup backup otomatis untuk database
5. **Monitoring**: Setup monitoring untuk container health
