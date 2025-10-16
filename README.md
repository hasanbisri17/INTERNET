# 🌐 Internet Management System

A comprehensive Laravel-based Internet Service Provider (ISP) management system with Docker support for easy deployment.

## ✨ Features

- **👥 Customer Management** - Complete customer database and billing system
- **💳 Payment Processing** - Multiple payment gateway integration
- **📄 Invoice Generation** - Automated invoice creation and PDF generation
- **📱 WhatsApp Integration** - Automated notifications and payment reminders
- **🔧 Router Management** - Mikrotik router integration for bandwidth management
- **📊 Reporting Dashboard** - Comprehensive analytics and business reports
- **🌍 Multi-language Support** - Indonesian and English language support
- **📱 Responsive Design** - Mobile-friendly interface for all devices

## 🚀 Quick Start

### Using Docker (Recommended)

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

### Manual Installation

```bash
# Install dependencies
composer install
npm install && npm run build

# Setup environment
cp .env.example .env
php artisan key:generate

# Setup database
php artisan migrate --seed
php artisan serve
```

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

- [Installation Guide](docs/INSTALLATION.md)
- [VPS Deployment Guide](VPS_DEPLOYMENT_GUIDE.md)
- [Docker Setup Guide](DOCKER_SETUP.md)
- [API Documentation](docs/API.md)
- [Configuration Guide](docs/CONFIGURATION.md)
- [Troubleshooting](docs/TROUBLESHOOTING.md)

## 🔧 Configuration

### Environment Variables

```env
# Application
APP_NAME="Internet Management System"
APP_ENV=production
APP_KEY=base64:your-app-key
APP_URL=https://your-domain.com

# Database
DB_CONNECTION=mysql
DB_HOST=db
DB_DATABASE=internet_db
DB_USERNAME=internet_user
DB_PASSWORD=your-password

# Cache & Session
CACHE_DRIVER=redis
SESSION_DRIVER=redis
REDIS_HOST=redis

# Mail
MAIL_MAILER=smtp
MAIL_HOST=smtp.gmail.com
MAIL_USERNAME=your-email@gmail.com
MAIL_PASSWORD=your-app-password

# WhatsApp (optional)
WHATSAPP_API_URL=https://api.whatsapp.com
WHATSAPP_API_TOKEN=your-token

# Mikrotik (optional)
MIKROTIK_HOST=192.168.1.1
MIKROTIK_USERNAME=admin
MIKROTIK_PASSWORD=your-password
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

![Build Status](https://github.com/yourusername/internet-management-system/workflows/Build%20and%20Test/badge.svg)
![Deploy Status](https://github.com/yourusername/internet-management-system/workflows/Deploy%20to%20VPS/badge.svg)
![License](https://img.shields.io/badge/license-MIT-blue.svg)
![PHP Version](https://img.shields.io/badge/PHP-8.2-green.svg)
![Laravel Version](https://img.shields.io/badge/Laravel-10.x-red.svg)

---

Made with ❤️ for Internet Service Providers

**Star ⭐ this repository if you find it helpful!**