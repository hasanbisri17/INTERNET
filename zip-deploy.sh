#!/bin/bash

# Script Otomatis untuk ZIP Deployment
# Internet Management System

set -e

# Colors
GREEN='\033[0;32m'
RED='\033[0;31m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m'

# Function untuk logging
log() {
    echo -e "${BLUE}[INFO]${NC} $1"
}

log_success() {
    echo -e "${GREEN}[SUCCESS]${NC} $1"
}

log_warning() {
    echo -e "${YELLOW}[WARNING]${NC} $1"
}

log_error() {
    echo -e "${RED}[ERROR]${NC} $1"
}

echo "📦 ZIP Deployment Script untuk Internet Management System"
echo "========================================================="

# Konfigurasi
ZIP_FILE=""
VPS_IP=""
VPS_DOMAIN=""
APP_PORT="8080"
PROJECT_DIR="internet-management-system"

# Function untuk menampilkan bantuan
show_help() {
    echo "Cara menggunakan script ini:"
    echo "1. Upload ZIP file ke VPS"
    echo "2. Edit script ini dan isi ZIP_FILE, VPS_IP, VPS_DOMAIN"
    echo "3. Jalankan: bash zip-deploy.sh"
    echo ""
    echo "Contoh konfigurasi:"
    echo "ZIP_FILE=internet-management-system.zip"
    echo "VPS_IP=192.168.1.100"
    echo "VPS_DOMAIN=your-domain.com"
}

# Cek apakah script sudah dikonfigurasi
check_config() {
    if [ -z "$ZIP_FILE" ] || [ -z "$VPS_IP" ]; then
        log_error "Konfigurasi belum lengkap!"
        show_help
        exit 1
    fi
}

# Cek prerequisites
check_prerequisites() {
    log "Mengecek prerequisites..."
    
    # Cek ZIP file
    if [ ! -f "$ZIP_FILE" ]; then
        log_error "ZIP file $ZIP_FILE tidak ditemukan!"
        exit 1
    fi
    
    # Cek Docker
    if ! command -v docker &> /dev/null; then
        log_warning "Docker tidak terinstall. Menginstall Docker..."
        install_docker
    fi
    
    # Cek Docker Compose
    if ! command -v docker-compose &> /dev/null; then
        log_warning "Docker Compose tidak terinstall. Menginstall Docker Compose..."
        install_docker_compose
    fi
    
    log_success "Semua prerequisites terpenuhi"
}

# Install Docker
install_docker() {
    log "Menginstall Docker..."
    
    # Update package list
    sudo apt-get update
    
    # Install Docker
    curl -fsSL https://get.docker.com -o get-docker.sh
    sudo sh get-docker.sh
    sudo usermod -aG docker $USER
    
    log_success "Docker berhasil diinstall"
}

# Install Docker Compose
install_docker_compose() {
    log "Menginstall Docker Compose..."
    
    sudo curl -L "https://github.com/docker/compose/releases/latest/download/docker-compose-$(uname -s)-$(uname -m)" -o /usr/local/bin/docker-compose
    sudo chmod +x /usr/local/bin/docker-compose
    
    log_success "Docker Compose berhasil diinstall"
}

# Extract ZIP file
extract_zip() {
    log "Mengextract ZIP file..."
    
    # Hapus directory lama jika ada
    if [ -d "$PROJECT_DIR" ]; then
        log_warning "Directory $PROJECT_DIR sudah ada. Menghapus..."
        rm -rf $PROJECT_DIR
    fi
    
    # Extract ZIP
    unzip -q $ZIP_FILE
    
    # Rename jika perlu
    if [ ! -d "$PROJECT_DIR" ]; then
        # Cari directory yang diextract
        EXTRACTED_DIR=$(unzip -l $ZIP_FILE | head -n 4 | tail -n 1 | awk '{print $4}' | cut -d'/' -f1)
        if [ ! -z "$EXTRACTED_DIR" ] && [ "$EXTRACTED_DIR" != "$PROJECT_DIR" ]; then
            mv $EXTRACTED_DIR $PROJECT_DIR
        fi
    fi
    
    cd $PROJECT_DIR
    
    log_success "ZIP file berhasil diextract"
}

# Setup environment
setup_environment() {
    log "Mengsetup environment..."
    
    # Copy environment file untuk VPS
    if [ -f "env.vps-standalone" ]; then
        cp env.vps-standalone .env
    elif [ -f "env.standalone" ]; then
        cp env.standalone .env
    else
        log_error "Environment file tidak ditemukan!"
        exit 1
    fi
    
    # Update .env dengan konfigurasi VPS
    sed -i "s/VPS_IP=192.168.1.100/VPS_IP=$VPS_IP/" .env
    
    if [ ! -z "$VPS_DOMAIN" ] && [ "$VPS_DOMAIN" != "your-domain.com" ]; then
        sed -i "s/VPS_DOMAIN=your-domain.com/VPS_DOMAIN=$VPS_DOMAIN/" .env
    fi
    
    sed -i "s/APP_PORT=8080/APP_PORT=$APP_PORT/" .env
    
    log_success "Environment berhasil disetup"
}

# Setup firewall
setup_firewall() {
    log "Mengsetup firewall..."
    
    # Install UFW jika belum ada
    if ! command -v ufw &> /dev/null; then
        sudo apt-get install -y ufw
    fi
    
    # Konfigurasi firewall
    sudo ufw default deny incoming
    sudo ufw default allow outgoing
    sudo ufw allow ssh
    sudo ufw allow $APP_PORT
    sudo ufw allow 8443
    sudo ufw --force enable
    
    log_success "Firewall berhasil disetup"
}

# Deploy aplikasi
deploy_application() {
    log "Membangun dan menjalankan aplikasi..."
    
    # Build dan jalankan dengan Docker Compose
    docker-compose -f docker-compose.standalone.yml up -d --build
    
    log_success "Aplikasi berhasil di-deploy"
}

# Verifikasi deployment
verify_deployment() {
    log "Memverifikasi deployment..."
    
    # Tunggu aplikasi start
    sleep 30
    
    # Cek status container
    docker-compose -f docker-compose.standalone.yml ps
    
    # Test aplikasi
    if curl -f http://localhost:$APP_PORT/health > /dev/null 2>&1; then
        log_success "Aplikasi berhasil diakses di http://localhost:$APP_PORT"
    else
        log_warning "Aplikasi belum bisa diakses. Coba lagi dalam beberapa menit."
    fi
    
    log_success "Deployment selesai!"
    echo ""
    echo "🌐 Aplikasi dapat diakses di:"
    if [ ! -z "$VPS_DOMAIN" ] && [ "$VPS_DOMAIN" != "your-domain.com" ]; then
        echo "   https://$VPS_DOMAIN:$APP_PORT"
    else
        echo "   http://$VPS_IP:$APP_PORT"
    fi
    echo ""
    echo "🔧 Admin Panel:"
    if [ ! -z "$VPS_DOMAIN" ] && [ "$VPS_DOMAIN" != "your-domain.com" ]; then
        echo "   https://$VPS_DOMAIN:$APP_PORT/admin"
    else
        echo "   http://$VPS_IP:$APP_PORT/admin"
    fi
    echo ""
    echo "📋 Default Admin Credentials:"
    echo "   Email: admin@example.com"
    echo "   Password: password"
    echo ""
    echo "⚠️  Jangan lupa untuk mengubah password default!"
}

# Main execution
main() {
    echo "Mulai proses ZIP deployment..."
    echo ""
    
    check_config
    check_prerequisites
    extract_zip
    setup_environment
    setup_firewall
    deploy_application
    verify_deployment
}

# Jalankan script
main
