# Internet Management - Docker Setup

Aplikasi Internet Management yang dapat dijalankan di server Docker mana saja dengan port default 1217.

## 🚀 Quick Start

### 1. Build dan Push ke Docker Hub

```bash
# Build dan push image ke Docker Hub
./build-and-push.sh your-dockerhub-username/internet-management latest
```

### 2. Deploy di Server

```bash
# Download dan jalankan di server
wget https://raw.githubusercontent.com/your-repo/main/run-on-server.sh
chmod +x run-on-server.sh
./run-on-server.sh your-dockerhub-username/internet-management latest
```

### 3. Setup Aplikasi

```bash
# Setup database dan konfigurasi awal
./setup-app.sh
```

## 📋 Prerequisites

- Docker Desktop terinstall dan terhubung ke Docker Hub
- Server target memiliki Docker terinstall
- Akses internet untuk pull image dari Docker Hub

## 🔧 Manual Setup

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

# Run MySQL container
docker run -d \
    --name internet-mysql \
    --restart unless-stopped \
    -e MYSQL_ROOT_PASSWORD=password \
    -e MYSQL_DATABASE=internet_management \
    -e MYSQL_USER=internet_user \
    -e MYSQL_PASSWORD=internet_password \
    -p 3306:3306 \
    mysql:8.0

# Run application container
docker run -d \
    --name internet-app \
    --restart unless-stopped \
    -p 1217:1217 \
    -v $(pwd)/storage:/var/www/html/storage \
    -v $(pwd)/public:/var/www/html/public \
    -e APP_KEY="base64:$(openssl rand -base64 32)" \
    -e APP_URL="http://localhost:1217" \
    -e DB_CONNECTION=mysql \
    -e DB_HOST=host.docker.internal \
    -e DB_PORT=3306 \
    -e DB_DATABASE=internet_management \
    -e DB_USERNAME=root \
    -e DB_PASSWORD=password \
    --add-host=host.docker.internal:host-gateway \
    your-username/internet-management:latest
```

### Setup Aplikasi

```bash
# Setup database
docker exec internet-app php artisan migrate --force

# Seed database
docker exec internet-app php artisan db:seed --force

# Generate app key
docker exec internet-app php artisan key:generate --force

# Setup storage
docker exec internet-app php artisan storage:link
```

## 🌐 Akses Aplikasi

Setelah setup selesai, aplikasi dapat diakses di:

- **URL**: http://localhost:1217
- **MySQL Database**: localhost:3306
- **Default Login**:
  - Email: admin@example.com
  - Password: password
- **Database Credentials**:
  - Host: localhost:3306
  - Database: internet_management
  - Username: root
  - Password: password

## 📁 File Structure

```
├── Dockerfile                 # Docker image configuration
├── docker-compose.yml        # Docker Compose configuration
├── .dockerignore            # Docker ignore file
├── build-and-push.sh        # Script untuk build dan push
├── run-on-server.sh         # Script untuk run di server
├── setup-app.sh             # Script untuk setup aplikasi
├── docker/
│   ├── nginx/
│   │   └── default.conf     # Nginx configuration
│   ├── php/
│   │   └── php.ini          # PHP configuration
│   └── supervisor/
│       └── supervisord.conf # Supervisor configuration
└── DOCKER_DEPLOYMENT_GUIDE.md # Panduan deployment lengkap
```

## 🔍 Monitoring

### View Logs

```bash
# View all logs
docker logs internet-app

# Follow logs
docker logs -f internet-app
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

# Setup aplikasi
./setup-app.sh
```

### Backup Database

```bash
# Backup MySQL database
docker exec internet-mysql mysqldump -u root -ppassword internet_management > backup-$(date +%Y%m%d).sql
```

## 🚨 Troubleshooting

### Container Won't Start

```bash
# Check logs
docker logs internet-app

# Check container status
docker ps -a
```

### Permission Issues

```bash
# Fix storage permissions
docker exec internet-app chown -R www-data:www-data /var/www/html/storage
docker exec internet-app chmod -R 755 /var/www/html/storage
```

### Database Issues

```bash
# Check MySQL container
docker logs internet-mysql

# Check database connection
docker exec internet-app php artisan migrate:status

# Recreate database
docker exec internet-app php artisan migrate:fresh --seed
```

## 📞 Support

Jika mengalami masalah:

1. Check logs: `docker logs internet-app`
2. Check container status: `docker ps`
3. Restart container: `docker restart internet-app`
4. Check dokumentasi lengkap: `DOCKER_DEPLOYMENT_GUIDE.md`

## 🎯 Production Tips

1. **Security**: Ganti `APP_KEY` dengan key yang aman
2. **Domain**: Set `APP_URL` dengan domain yang benar
3. **SSL**: Gunakan reverse proxy (nginx/traefik) untuk SSL
4. **Backup**: Setup backup otomatis untuk database
5. **Monitoring**: Setup monitoring untuk container health
