# Internet Management System

A comprehensive Laravel-based Internet Service Provider (ISP) management system with Docker support.

## 🚀 Quick Start

### Using Docker Hub Image

```bash
# Pull the image
docker pull yourusername/internet-management-system:latest

# Run with Docker Compose (Recommended)
docker-compose up -d

# Or run standalone
docker run -p 8000:80 -e DB_HOST=your-db-host yourusername/internet-management-system:latest
```

### Using Docker Compose (Full Stack)

```bash
# Clone the repository
git clone https://github.com/yourusername/internet-management-system.git
cd internet-management-system

# Copy environment file
cp env.docker .env

# Start all services
docker-compose up -d --build

# Setup database
docker exec -it internet_app php artisan migrate --seed
```

## 📋 Features

- **Customer Management** - Complete customer database and billing
- **Payment Processing** - Multiple payment gateway integration
- **Invoice Generation** - Automated invoice creation and PDF generation
- **WhatsApp Integration** - Automated notifications and reminders
- **Router Management** - Mikrotik router integration
- **Reporting Dashboard** - Comprehensive analytics and reports
- **Multi-language Support** - Indonesian and English
- **Responsive Design** - Mobile-friendly interface

## 🐳 Docker Services

- **app** - Laravel application (PHP 8.2 + Apache)
- **db** - MySQL 8.0 database
- **redis** - Redis cache and session storage
- **phpmyadmin** - Database management interface

## 🌐 Access Points

- **Application**: http://localhost:8000
- **Admin Panel**: http://localhost:8000/admin
- **phpMyAdmin**: http://localhost:8080
- **Database**: localhost:3306
- **Redis**: localhost:6379

## 🔧 Environment Variables

```env
APP_NAME="Internet Management System"
APP_ENV=production
APP_KEY=base64:your-app-key
APP_URL=http://localhost:8000

DB_CONNECTION=mysql
DB_HOST=db
DB_PORT=3306
DB_DATABASE=internet_db
DB_USERNAME=internet_user
DB_PASSWORD=internet_password

REDIS_HOST=redis
REDIS_PORT=6379
```

## 📊 Default Admin Credentials

- **Email**: admin@example.com
- **Password**: password

⚠️ **Important**: Change default credentials after first login!

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

## 🚀 Production Deployment

### Using Docker Hub Image

```bash
# Pull latest image
docker pull yourusername/internet-management-system:latest

# Run with production compose
docker-compose -f docker-compose.prod.yml up -d
```

### Manual Production Setup

```bash
# Build production image
docker build -f Dockerfile.prod -t internet-management-system:prod .

# Run with environment variables
docker run -d \
  --name internet-app \
  -p 80:80 \
  -e APP_ENV=production \
  -e DB_HOST=your-db-host \
  -e DB_DATABASE=your-db-name \
  -e DB_USERNAME=your-db-user \
  -e DB_PASSWORD=your-db-password \
  yourusername/internet-management-system:latest
```

## 📈 Monitoring

### Health Checks
```bash
# Check container status
docker ps

# Check application health
curl http://localhost:8000/health

# Monitor logs
docker logs -f internet_app
```

### Performance Monitoring
```bash
# Monitor resource usage
docker stats

# Check disk usage
docker system df
```

## 🔒 Security

- Non-root user execution
- Optimized PHP configuration
- Secure Apache configuration
- Regular security updates
- Environment-based configuration

## 📚 Documentation

- [Installation Guide](docs/INSTALLATION.md)
- [API Documentation](docs/API.md)
- [Configuration Guide](docs/CONFIGURATION.md)
- [Troubleshooting](docs/TROUBLESHOOTING.md)

## 🤝 Contributing

1. Fork the repository
2. Create a feature branch
3. Make your changes
4. Test with Docker
5. Submit a pull request

## 📄 License

This project is licensed under the MIT License - see the [LICENSE](LICENSE) file for details.

## 🆘 Support

- **Issues**: [GitHub Issues](https://github.com/yourusername/internet-management-system/issues)
- **Documentation**: [Wiki](https://github.com/yourusername/internet-management-system/wiki)
- **Email**: support@yourcompany.com

## 🏷️ Tags

- `latest` - Latest stable release
- `v1.0.0` - Version 1.0.0
- `dev` - Development version

## 📊 Image Size

- **Base Image**: ~200MB
- **With Dependencies**: ~400MB
- **Optimized**: ~300MB

---

Made with ❤️ for Internet Service Providers
