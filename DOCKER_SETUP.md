# Docker Setup untuk Internet Management System

## Prerequisites
- Docker Desktop terinstall
- Docker Compose terinstall

## Setup Awal

### 1. Clone dan Setup Environment
```bash
# Copy environment file untuk Docker
cp env.docker .env

# Generate application key
docker run --rm -v $(pwd):/app -w /app php:8.2-cli php artisan key:generate
```

### 2. Build dan Jalankan Container
```bash
# Build dan jalankan semua services
docker-compose up -d --build

# Atau jalankan step by step:
docker-compose build
docker-compose up -d
```

### 3. Setup Database
```bash
# Masuk ke container aplikasi
docker exec -it internet_app bash

# Jalankan migrasi dan seeder
php artisan migrate --seed

# Generate application key jika belum
php artisan key:generate

# Clear cache
php artisan config:clear
php artisan cache:clear
php artisan view:clear
```

## Akses Aplikasi

- **Aplikasi Web**: http://localhost:8000
- **phpMyAdmin**: http://localhost:8080
  - Username: `internet_user`
  - Password: `internet_password`
- **Database**: localhost:3306
- **Redis**: localhost:6379

## Perintah Berguna

### Container Management
```bash
# Lihat status container
docker-compose ps

# Restart aplikasi
docker-compose restart app

# Stop semua container
docker-compose down

# Stop dan hapus volume (HATI-HATI: akan menghapus data database)
docker-compose down -v
```

### Logs
```bash
# Lihat logs aplikasi
docker-compose logs app

# Lihat logs database
docker-compose logs db

# Follow logs real-time
docker-compose logs -f app
```

### Database Operations
```bash
# Backup database
docker exec internet_db mysqldump -u internet_user -p internet_db > backup.sql

# Restore database
docker exec -i internet_db mysql -u internet_user -p internet_db < backup.sql
```

### Development Commands
```bash
# Masuk ke container aplikasi
docker exec -it internet_app bash

# Install composer dependencies
composer install

# Install npm dependencies
npm install

# Build assets
npm run build

# Jalankan artisan commands
php artisan migrate
php artisan db:seed
php artisan queue:work
```

## Production Deployment

### 1. Build Production Image
```bash
# Build image untuk production
docker build -t internet-app:latest .

# Tag untuk registry
docker tag internet-app:latest your-registry/internet-app:latest

# Push ke registry
docker push your-registry/internet-app:latest
```

### 2. Deploy dengan Docker Compose
```bash
# Update environment untuk production
# Edit .env dengan konfigurasi production

# Deploy
docker-compose -f docker-compose.prod.yml up -d
```

## Troubleshooting

### Permission Issues
```bash
# Fix permissions
docker exec internet_app chown -R www-data:www-data /var/www/html
docker exec internet_app chmod -R 755 /var/www/html/storage
```

### Database Connection Issues
```bash
# Test database connection
docker exec internet_app php artisan tinker
# Di dalam tinker:
DB::connection()->getPdo();
```

### Clear All Caches
```bash
docker exec internet_app php artisan optimize:clear
```

## Environment Variables

Pastikan file `.env` memiliki konfigurasi yang benar:

```env
APP_URL=http://localhost:8000
DB_HOST=db
DB_DATABASE=internet_db
DB_USERNAME=internet_user
DB_PASSWORD=internet_password
REDIS_HOST=redis
```

## Monitoring

### Health Check
```bash
# Check container health
docker-compose ps

# Check application logs
docker-compose logs app | tail -50
```

### Performance Monitoring
```bash
# Monitor resource usage
docker stats

# Monitor specific container
docker stats internet_app
```
