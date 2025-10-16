#!/bin/bash

# Script Deployment VPS untuk Internet Management System
# Menggunakan GitHub dan Docker

set -e

# Konfigurasi
GITHUB_REPO=""  # Ganti dengan URL repository GitHub Anda
APP_NAME="internet-management-system"
DOMAIN=""  # Ganti dengan domain VPS Anda
EMAIL=""  # Ganti dengan email Anda untuk SSL

# Colors untuk output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

# Fungsi untuk menampilkan pesan
print_message() {
    echo -e "${BLUE}[INFO]${NC} $1"
}

print_success() {
    echo -e "${GREEN}[SUCCESS]${NC} $1"
}

print_warning() {
    echo -e "${YELLOW}[WARNING]${NC} $1"
}

print_error() {
    echo -e "${RED}[ERROR]${NC} $1"
}

# Fungsi untuk menampilkan bantuan
show_help() {
    echo "Script Deployment VPS untuk Internet Management System"
    echo ""
    echo "Cara menggunakan:"
    echo "1. Edit script ini dan isi GITHUB_REPO, DOMAIN, dan EMAIL"
    echo "2. Jalankan: bash vps-deploy.sh"
    echo ""
    echo "Contoh konfigurasi:"
    echo "GITHUB_REPO=https://github.com/yourusername/internet-management-system.git"
    echo "DOMAIN=your-domain.com"
    echo "EMAIL=your-email@example.com"
}

# Cek apakah script sudah dikonfigurasi
check_config() {
    if [ -z "$GITHUB_REPO" ] || [ -z "$DOMAIN" ] || [ -z "$EMAIL" ]; then
        print_error "Konfigurasi belum lengkap!"
        show_help
        exit 1
    fi
}

# Cek prerequisites
check_prerequisites() {
    print_message "Mengecek prerequisites..."
    
    # Cek Docker
    if ! command -v docker &> /dev/null; then
        print_error "Docker tidak terinstall. Silakan install Docker terlebih dahulu."
        exit 1
    fi
    
    # Cek Docker Compose
    if ! command -v docker-compose &> /dev/null; then
        print_error "Docker Compose tidak terinstall. Silakan install Docker Compose terlebih dahulu."
        exit 1
    fi
    
    # Cek Git
    if ! command -v git &> /dev/null; then
        print_error "Git tidak terinstall. Silakan install Git terlebih dahulu."
        exit 1
    fi
    
    print_success "Semua prerequisites terpenuhi"
}

# Install Docker dan Docker Compose (jika belum ada)
install_docker() {
    print_message "Menginstall Docker dan Docker Compose..."
    
    # Update package list
    sudo apt-get update
    
    # Install Docker
    curl -fsSL https://get.docker.com -o get-docker.sh
    sudo sh get-docker.sh
    sudo usermod -aG docker $USER
    
    # Install Docker Compose
    sudo curl -L "https://github.com/docker/compose/releases/latest/download/docker-compose-$(uname -s)-$(uname -m)" -o /usr/local/bin/docker-compose
    sudo chmod +x /usr/local/bin/docker-compose
    
    print_success "Docker dan Docker Compose berhasil diinstall"
}

# Clone repository dari GitHub
clone_repository() {
    print_message "Mengclone repository dari GitHub..."
    
    if [ -d "$APP_NAME" ]; then
        print_warning "Directory $APP_NAME sudah ada. Menghapus..."
        rm -rf $APP_NAME
    fi
    
    git clone $GITHUB_REPO $APP_NAME
    cd $APP_NAME
    
    print_success "Repository berhasil di-clone"
}

# Setup environment
setup_environment() {
    print_message "Mengsetup environment..."
    
    # Copy environment file
    cp env.vps .env
    
    # Generate APP_KEY
    docker run --rm -v $(pwd):/app -w /app php:8.2-cli php -r "echo 'base64:' . base64_encode(random_bytes(32));" >> .env.tmp
    APP_KEY=$(cat .env.tmp)
    rm .env.tmp
    
    # Update .env dengan APP_KEY dan domain
    sed -i "s/APP_KEY=/APP_KEY=$APP_KEY/" .env
    sed -i "s|APP_URL=http://your-domain.com|APP_URL=https://$DOMAIN|" .env
    sed -i "s/your-domain.com/$DOMAIN/g" .env
    
    print_success "Environment berhasil disetup"
}

# Setup SSL dengan Let's Encrypt
setup_ssl() {
    print_message "Mengsetup SSL dengan Let's Encrypt..."
    
    # Install Certbot
    sudo apt-get install -y certbot
    
    # Generate SSL certificate
    sudo certbot certonly --standalone -d $DOMAIN --email $EMAIL --agree-tos --non-interactive
    
    # Copy SSL certificates
    sudo mkdir -p docker/ssl
    sudo cp /etc/letsencrypt/live/$DOMAIN/fullchain.pem docker/ssl/
    sudo cp /etc/letsencrypt/live/$DOMAIN/privkey.pem docker/ssl/
    sudo chown -R $USER:$USER docker/ssl/
    
    print_success "SSL berhasil disetup"
}

# Build dan jalankan aplikasi
deploy_application() {
    print_message "Membangun dan menjalankan aplikasi..."
    
    # Build dan jalankan dengan Docker Compose
    docker-compose -f docker-compose.vps.yml up -d --build
    
    print_success "Aplikasi berhasil di-deploy"
}

# Setup database
setup_database() {
    print_message "Mengsetup database..."
    
    # Tunggu database siap
    sleep 30
    
    # Jalankan migrasi dan seeder
    docker exec internet_app_prod php artisan migrate --force
    docker exec internet_app_prod php artisan db:seed --force
    
    print_success "Database berhasil disetup"
}

# Setup cron job untuk SSL renewal
setup_cron() {
    print_message "Mengsetup cron job untuk SSL renewal..."
    
    # Tambahkan cron job untuk SSL renewal
    (crontab -l 2>/dev/null; echo "0 12 * * * /usr/bin/certbot renew --quiet") | crontab -
    
    print_success "Cron job berhasil disetup"
}

# Setup firewall
setup_firewall() {
    print_message "Mengsetup firewall..."
    
    # Install UFW jika belum ada
    sudo apt-get install -y ufw
    
    # Konfigurasi firewall
    sudo ufw default deny incoming
    sudo ufw default allow outgoing
    sudo ufw allow ssh
    sudo ufw allow 80
    sudo ufw allow 443
    sudo ufw --force enable
    
    print_success "Firewall berhasil disetup"
}

# Verifikasi deployment
verify_deployment() {
    print_message "Memverifikasi deployment..."
    
    # Cek status container
    docker-compose -f docker-compose.vps.yml ps
    
    # Test aplikasi
    if curl -f https://$DOMAIN > /dev/null 2>&1; then
        print_success "Aplikasi berhasil diakses di https://$DOMAIN"
    else
        print_warning "Aplikasi belum bisa diakses. Coba lagi dalam beberapa menit."
    fi
    
    print_success "Deployment selesai!"
    echo ""
    echo "🌐 Aplikasi dapat diakses di: https://$DOMAIN"
    echo "🔧 Admin Panel: https://$DOMAIN/admin"
    echo "📊 phpMyAdmin: https://$DOMAIN:8080"
    echo ""
    echo "📋 Default Admin Credentials:"
    echo "   Email: admin@example.com"
    echo "   Password: password"
    echo ""
    echo "⚠️  Jangan lupa untuk mengubah password default!"
}

# Main execution
main() {
    echo "🚀 VPS Deployment Script untuk Internet Management System"
    echo "========================================================"
    echo ""
    
    check_config
    check_prerequisites
    
    # Tanya user apakah ingin install Docker
    read -p "Apakah Anda ingin menginstall Docker dan Docker Compose? (y/n): " -n 1 -r
    echo
    if [[ $REPLY =~ ^[Yy]$ ]]; then
        install_docker
    fi
    
    clone_repository
    setup_environment
    setup_ssl
    deploy_application
    setup_database
    setup_cron
    setup_firewall
    verify_deployment
}

# Jalankan script
main
