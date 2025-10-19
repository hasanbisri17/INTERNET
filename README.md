# 🌐 Internet Management System

A comprehensive Laravel-based Internet Service Provider (ISP) management system with Docker support for easy deployment and **one-click auto-update** functionality.

## ✨ Features

- **👥 Customer Management** - Complete customer database and billing system
- **💳 Payment Processing** - Multiple payment gateway integration
- **📄 Invoice Generation** - Automated invoice creation and PDF generation
- **📱 WhatsApp Integration** - Automated notifications and payment reminders
- **🔧 Router Management** - Mikrotik router integration for bandwidth management
- **📊 Reporting Dashboard** - Comprehensive analytics and business reports
- **🔄 Auto-Update System** - One-click updates directly from admin panel
- **🐳 Docker Ready** - Pre-built Docker image available on Docker Hub
- **🌍 Multi-language Support** - Indonesian and English language support
- **📱 Responsive Design** - Mobile-friendly interface for all devices

## 🚀 Quick Start with Docker Hub

### 🐳 Docker Hub Installation

The easiest way to get started is using our pre-built Docker image from Docker Hub:

**Docker Image**: `habis12/internet-management:latest`

### 📋 Prerequisites

- Docker installed on your system
- Docker Compose (optional, for multi-container setup)
- At least 2GB RAM and 10GB storage

## 🚀 Installation Methods

### Method 1: EasyPanel (Recommended for VPS)

EasyPanel provides the easiest way to deploy and manage your application:

1. **Login to EasyPanel**
2. **Create New Project** → Select "Docker"
3. **Configure Image**:
   - Image Source: Docker Hub
   - Image Name: `habis12/internet-management:latest`
   - Tag: `latest`
4. **Set Environment Variables** (see configuration section below)
5. **Deploy** and access your application

📖 **Detailed Guide**: [EasyPanel Deployment Guide](EASYPANEL_DEPLOYMENT_GUIDE.md)

### Method 2: Portainer

Portainer provides a web-based Docker management interface:

1. **Install Portainer** (if not already installed)
2. **Create New Stack**:
   - Name: `internet-management`
   - Use Docker Compose
3. **Add Stack Configuration**:

```yaml
version: '3.8'
services:
  app:
    image: habis12/internet-management:latest
    container_name: internet-app
    restart: unless-stopped
    ports:
      - "1217:1217"
    environment:
      - APP_NAME=Internet Management
      - APP_ENV=production
      - APP_KEY=base64:your-app-key-here
      - APP_DEBUG=false
      - APP_URL=https://your-domain.com
      - DB_CONNECTION=mysql
      - DB_HOST=mysql
      - DB_PORT=3306
      - DB_DATABASE=internet_management
      - DB_USERNAME=root
      - DB_PASSWORD=your-secure-password
    volumes:
      - storage:/var/www/html/storage
      - public:/var/www/html/public
    networks:
      - internet-network
    depends_on:
      - mysql

  mysql:
    image: mysql:8.0
    container_name: internet-mysql
    restart: unless-stopped
    environment:
      - MYSQL_ROOT_PASSWORD=your-secure-password
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

4. **Deploy Stack**
5. **Setup Application** (see setup section below)

### Method 3: Docker Compose (Local/Server)

```bash
# Create project directory
mkdir internet-management
cd internet-management

# Create docker-compose.yml
cat > docker-compose.yml << 'EOF'
version: '3.8'
services:
  app:
    image: habis12/internet-management:latest
    container_name: internet-app
    restart: unless-stopped
    ports:
      - "1217:1217"
    environment:
      - APP_NAME=Internet Management
      - APP_ENV=production
      - APP_KEY=base64:$(openssl rand -base64 32)
      - APP_DEBUG=false
      - APP_URL=http://localhost:1217
      - DB_CONNECTION=mysql
      - DB_HOST=mysql
      - DB_PORT=3306
      - DB_DATABASE=internet_management
      - DB_USERNAME=root
      - DB_PASSWORD=password
    volumes:
      - ./storage:/var/www/html/storage
      - ./public:/var/www/html/public
    networks:
      - internet-network
    depends_on:
      - mysql

  mysql:
    image: mysql:8.0
    container_name: internet-mysql
    restart: unless-stopped
    environment:
      - MYSQL_ROOT_PASSWORD=password
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
EOF

# Start services
docker-compose up -d

# Setup application
docker exec internet-app php artisan migrate --seed
docker exec internet-app php artisan key:generate --force
docker exec internet-app php artisan storage:link
```

### Method 4: Docker Run (Simple)

```bash
# Run MySQL first
docker run -d \
  --name internet-mysql \
  --restart unless-stopped \
  -e MYSQL_ROOT_PASSWORD=password \
  -e MYSQL_DATABASE=internet_management \
  -e MYSQL_USER=internet_user \
  -e MYSQL_PASSWORD=internet_password \
  -p 3306:3306 \
  mysql:8.0

# Wait for MySQL to be ready
sleep 30

# Run application
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
  habis12/internet-management:latest

# Setup application
docker exec internet-app php artisan migrate --seed
docker exec internet-app php artisan key:generate --force
docker exec internet-app php artisan storage:link
```

## 🔧 Application Setup

After deploying with any method above, you need to setup the application:

### 1. Database Migration & Seeding

```bash
# Run migrations
docker exec internet-app php artisan migrate --force

# Seed database with sample data
docker exec internet-app php artisan db:seed --force

# Generate application key
docker exec internet-app php artisan key:generate --force

# Create storage link
docker exec internet-app php artisan storage:link

# Set proper permissions
docker exec internet-app chown -R www-data:www-data /var/www/html/storage
docker exec internet-app chmod -R 755 /var/www/html/storage
```

### 2. Verify Installation

```bash
# Check application health
curl http://localhost:1217/health

# Expected response:
# {"status":"ok","timestamp":"...","version":"1.0.0","database":"connected"}
```

## 🌐 Access Points

- **Application**: http://localhost:1217
- **Admin Panel**: http://localhost:1217/admin
- **Health Check**: http://localhost:1217/health
- **Database**: localhost:3306
- **Default Port**: 1217 (configurable)

## 🔄 Auto-Update Feature

The application includes a built-in auto-update system:

### Features:
- **Real-time Update Check** - Automatically checks for new versions
- **One-Click Update** - Update directly from admin panel
- **Progress Tracking** - Real-time update progress
- **Zero Downtime** - Seamless container replacement
- **Rollback Support** - Easy rollback if needed

### How to Use:
1. Login to admin panel
2. Look for update notification in header
3. Click "Update Now" when available
4. Wait for update to complete
5. Application will restart automatically

## 🐳 Docker Services

- **app** - Laravel application (PHP 8.2 + Nginx + Supervisor)
- **mysql** - MySQL 8.0 database
- **Auto-Update** - Built-in update system
- **Health Check** - Container health monitoring

## 🔧 Default Admin Credentials

- **Email**: admin@example.com
- **Password**: password

⚠️ **Important**: Change default credentials after first login!

## 🚀 VPS Deployment

### Automated Deployment

```bash
# Download deployment script
wget https://raw.githubusercontent.com/yourusername/internet-management-system/main/vps-deploy.sh
chmod +x vps-deploy.sh

# Edit configuration
nano vps-deploy.sh

# Run deployment
./vps-deploy.sh
```

### Manual VPS Setup

```bash
# Clone repository
git clone https://github.com/yourusername/internet-management-system.git
cd internet-management-system

# Setup environment
cp env.vps .env
nano .env  # Edit configuration

# Deploy with Docker Compose
docker-compose -f docker-compose.vps.yml up -d --build

# Setup database
docker exec internet_app_prod php artisan migrate --seed
```

## 📋 Requirements

### System Requirements
- **PHP**: 8.1 or higher
- **MySQL**: 8.0 or higher
- **Redis**: 6.0 or higher
- **Node.js**: 16.0 or higher
- **Composer**: 2.0 or higher

### Docker Requirements
- **Docker**: 20.0 or higher
- **Docker Compose**: 2.0 or higher

### VPS Requirements
- **OS**: Ubuntu 20.04+ or CentOS 8+
- **RAM**: 2GB minimum (4GB recommended)
- **Storage**: 20GB SSD minimum
- **CPU**: 2 cores minimum

## 🛠️ Development

### Prerequisites
- Docker Desktop
- Docker Compose
- Git

### Setup Development Environment

```bash
# Clone repository
git clone https://github.com/yourusername/internet-management-system.git
cd internet-management-system

# Start development environment
docker-compose up -d

# Install dependencies
docker exec -it internet_app composer install
docker exec -it internet_app npm install

# Build assets
docker exec -it internet_app npm run build

# Run migrations
docker exec -it internet_app php artisan migrate --seed
```

### Useful Commands

```bash
# View logs
docker-compose logs -f app

# Access container
docker exec -it internet_app bash

# Clear cache
docker exec -it internet_app php artisan optimize:clear

# Run tests
docker exec -it internet_app php artisan test
```

## 📚 Documentation

- [🐳 Docker Deployment Guide](DOCKER_DEPLOYMENT_GUIDE.md) - Complete Docker setup guide
- [🚀 EasyPanel Deployment Guide](EASYPANEL_DEPLOYMENT_GUIDE.md) - EasyPanel specific instructions
- [🔄 Auto-Update Feature](docs/auto_update_feature.md) - Auto-update system documentation
- [📋 Docker README](README_DOCKER.md) - Docker-specific documentation
- [🔧 API Documentation](docs/API.md) - API endpoints and usage
- [⚙️ Configuration Guide](docs/CONFIGURATION.md) - Detailed configuration options
- [🚨 Troubleshooting](docs/TROUBLESHOOTING.md) - Common issues and solutions

## ⚙️ Configuration

### Environment Variables

For Docker Hub deployment, configure these environment variables:

```env
# Application
APP_NAME=Internet Management
APP_ENV=production
APP_KEY=base64:your-32-character-key
APP_DEBUG=false
APP_URL=https://your-domain.com
APP_VERSION=1.0.0

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

# WhatsApp (optional)
WHATSAPP_API_URL=https://api.whatsapp.com
WHATSAPP_API_TOKEN=your-token

# Mikrotik (optional)
MIKROTIK_HOST=192.168.1.1
MIKROTIK_USERNAME=admin
MIKROTIK_PASSWORD=your-password
```

### Generate App Key

```bash
# Generate secure app key
openssl rand -base64 32

# Or use this one-liner in your environment
APP_KEY=base64:$(openssl rand -base64 32)
```

## 🚨 Troubleshooting

### Common Issues

#### 1. Application Won't Start
```bash
# Check container logs
docker logs internet-app

# Check container status
docker ps -a

# Restart container
docker restart internet-app
```

#### 2. Database Connection Failed
```bash
# Check MySQL container
docker logs internet-mysql

# Test database connection
docker exec internet-app php artisan migrate:status

# Reset database
docker exec internet-app php artisan migrate:fresh --seed
```

#### 3. Permission Issues
```bash
# Fix storage permissions
docker exec internet-app chown -R www-data:www-data /var/www/html/storage
docker exec internet-app chmod -R 755 /var/www/html/storage

# Fix public permissions
docker exec internet-app chown -R www-data:www-data /var/www/html/public
docker exec internet-app chmod -R 755 /var/www/html/public
```

#### 4. Update Issues
```bash
# Check update status
docker exec internet-app cat /var/www/html/storage/app/update_status.json

# Manual update
docker exec internet-app bash /var/www/html/scripts/update.sh

# Check Docker Hub connectivity
docker exec internet-app curl -I https://hub.docker.com
```

#### 5. Port Already in Use
```bash
# Check what's using port 1217
netstat -tulpn | grep 1217

# Kill process using port
sudo kill -9 $(lsof -t -i:1217)

# Or use different port
docker run -p 8080:1217 habis12/internet-management:latest
```

### Health Check

```bash
# Check application health
curl http://localhost:1217/health

# Expected response:
{
  "status": "ok",
  "timestamp": "2024-01-01T00:00:00.000000Z",
  "version": "1.0.0",
  "database": "connected"
}
```

### Logs

```bash
# Application logs
docker logs internet-app

# MySQL logs
docker logs internet-mysql

# Follow logs in real-time
docker logs -f internet-app
```

### Backup & Restore

```bash
# Backup database
docker exec internet-mysql mysqldump -u root -ppassword internet_management > backup.sql

# Restore database
docker exec -i internet-mysql mysql -u root -ppassword internet_management < backup.sql

# Backup application files
docker cp internet-app:/var/www/html/storage ./backup-storage
```

## 🧪 Testing

```bash
# Run all tests
php artisan test

# Run specific test
php artisan test --filter=CustomerTest

# Run with coverage
php artisan test --coverage
```

## 📈 Performance

### Optimization Tips

```bash
# Clear cache
php artisan optimize:clear

# Optimize for production
php artisan optimize

# Queue processing
php artisan queue:work --daemon
```

### Monitoring

```bash
# Check container status
docker-compose ps

# Monitor resource usage
docker stats

# View logs
docker-compose logs -f app
```

## 🔒 Security

- **Authentication**: Laravel Sanctum
- **Authorization**: Role-based access control
- **CSRF Protection**: Built-in CSRF tokens
- **SQL Injection**: Eloquent ORM protection
- **XSS Protection**: Blade templating engine
- **HTTPS**: SSL/TLS encryption support

## 🤝 Contributing

1. Fork the repository
2. Create a feature branch (`git checkout -b feature/amazing-feature`)
3. Commit your changes (`git commit -m 'Add some amazing feature'`)
4. Push to the branch (`git push origin feature/amazing-feature`)
5. Open a Pull Request

### Development Guidelines

- Follow PSR-12 coding standards
- Write tests for new features
- Update documentation
- Use meaningful commit messages

## 📄 License

This project is licensed under the MIT License - see the [LICENSE](LICENSE) file for details.

## 🆘 Support

- **Issues**: [GitHub Issues](https://github.com/yourusername/internet-management-system/issues)
- **Documentation**: [Wiki](https://github.com/yourusername/internet-management-system/wiki)
- **Email**: support@yourcompany.com

## 🙏 Acknowledgments

- [Laravel](https://laravel.com) - The PHP framework
- [Filament](https://filamentphp.com) - Admin panel
- [Tailwind CSS](https://tailwindcss.com) - CSS framework
- [Docker](https://docker.com) - Containerization platform

## 📊 Project Status

[![Docker Hub](https://img.shields.io/badge/Docker%20Hub-habis12%2Finternet--management-blue?logo=docker)](https://hub.docker.com/r/habis12/internet-management)
[![Version](https://img.shields.io/badge/version-1.1.0-green.svg)](https://github.com/hasanbisri17/INTERNET/releases)
[![License](https://img.shields.io/badge/license-MIT-blue.svg)](LICENSE)
[![PHP Version](https://img.shields.io/badge/PHP-8.2-green.svg)](https://php.net)
[![Laravel Version](https://img.shields.io/badge/Laravel-11.x-red.svg)](https://laravel.com)
[![Docker](https://img.shields.io/badge/Docker-Ready-2496ED?logo=docker)](https://docker.com)
[![Auto-Update](https://img.shields.io/badge/Auto--Update-Enabled-green.svg)](docs/auto_update_feature.md)
[![Port](https://img.shields.io/badge/Port-1217-orange.svg)](http://localhost:1217)

## 🚀 Quick Start with Docker Hub

### One-Command Installation

```bash
# Pull and run the application
docker run -d \
  --name internet-app \
  -p 1217:1217 \
  -e APP_KEY="base64:$(openssl rand -base64 32)" \
  -e APP_URL="http://localhost:1217" \
  habis12/internet-management:latest

# Setup application
docker exec internet-app php artisan migrate --seed
docker exec internet-app php artisan key:generate --force
docker exec internet-app php artisan storage:link

# Access application
open http://localhost:1217
```

### With MySQL Database

```bash
# Run MySQL
docker run -d \
  --name internet-mysql \
  -e MYSQL_ROOT_PASSWORD=password \
  -e MYSQL_DATABASE=internet_management \
  -p 3306:3306 \
  mysql:8.0

# Run application with MySQL
docker run -d \
  --name internet-app \
  -p 1217:1217 \
  -e DB_CONNECTION=mysql \
  -e DB_HOST=host.docker.internal \
  -e DB_PASSWORD=password \
  --add-host=host.docker.internal:host-gateway \
  habis12/internet-management:latest
```

## 🎯 Why Choose This Solution?

- ✅ **Pre-built Docker Image** - No compilation needed
- ✅ **One-Click Updates** - Auto-update from admin panel
- ✅ **Multiple Deployment Options** - EasyPanel, Portainer, Docker Compose
- ✅ **Production Ready** - Optimized for performance
- ✅ **Comprehensive Documentation** - Step-by-step guides
- ✅ **Active Support** - Regular updates and bug fixes
- ✅ **Easy Maintenance** - Built-in health checks and monitoring

---

Made with ❤️ for Internet Service Providers

**Star ⭐ this repository if you find it helpful!**

[![GitHub stars](https://img.shields.io/github/stars/hasanbisri17/INTERNET?style=social)](https://github.com/hasanbisri17/INTERNET)
[![Docker pulls](https://img.shields.io/docker/pulls/habis12/internet-management?logo=docker)](https://hub.docker.com/r/habis12/internet-management)