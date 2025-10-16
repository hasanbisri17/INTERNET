#!/bin/bash

# VPS Deployment Script untuk Internet Management System
# Plug & Play deployment di VPS

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

echo "🚀 VPS Deployment Script untuk Internet Management System"
echo "========================================================="

# Konfigurasi VPS
VPS_IP=""
VPS_DOMAIN=""
APP_PORT="8080"
GITHUB_REPO=""

# Function untuk menampilkan bantuan
show_help() {
    echo "Cara menggunakan script ini:"
    echo "1. Edit script ini dan isi VPS_IP, VPS_DOMAIN, dan GITHUB_REPO"
    echo "2. Jalankan: bash vps-deploy-standalone.sh"
    echo ""
    echo "Contoh konfigurasi:"
    echo "VPS_IP=192.168.1.100"
    echo "VPS_DOMAIN=your-domain.com"
    echo "GITHUB_REPO=https://github.com/yourusername/internet-management-system.git"
}

# Cek apakah script sudah dikonfigurasi
check_config() {
    if [ -z "$VPS_IP" ] || [ -z "$GITHUB_REPO" ]; then
        log_error "Konfigurasi belum lengkap!"
        show_help
        exit 1
    fi
}

# Cek prerequisites
check_prerequisites() {
    log "Mengecek prerequisites..."
    
    # Cek Docker
    if ! command -v docker &> /dev/null; then
        log_error "Docker tidak terinstall. Silakan install Docker terlebih dahulu."
        exit 1
    fi
    
    # Cek Docker Compose
    if ! command -v docker-compose &> /dev/null; then
        log_error "Docker Compose tidak terinstall. Silakan install Docker Compose terlebih dahulu."
        exit 1
    fi
    
    # Cek Git
    if ! command -v git &> /dev/null; then
        log_error "Git tidak terinstall. Silakan install Git terlebih dahulu."
        exit 1
    fi
    
    log_success "Semua prerequisites terpenuhi"
}

# Clone repository dari GitHub
clone_repository() {
    log "Mengclone repository dari GitHub..."
    
    if [ -d "internet-management-system" ]; then
        log_warning "Directory internet-management-system sudah ada. Menghapus..."
        rm -rf internet-management-system
    fi
    
    git clone $GITHUB_REPO internet-management-system
    cd internet-management-system
    
    log_success "Repository berhasil di-clone"
}

# Setup environment untuk VPS
setup_vps_environment() {
    log "Mengsetup environment untuk VPS..."
    
    # Copy environment file untuk VPS
    cp env.vps-standalone .env
    
    # Update .env dengan konfigurasi VPS
    sed -i "s/VPS_IP=192.168.1.100/VPS_IP=$VPS_IP/" .env
    
    if [ ! -z "$VPS_DOMAIN" ] && [ "$VPS_DOMAIN" != "your-domain.com" ]; then
        sed -i "s/VPS_DOMAIN=your-domain.com/VPS_DOMAIN=$VPS_DOMAIN/" .env
    fi
    
    sed -i "s/APP_PORT=8080/APP_PORT=$APP_PORT/" .env
    
    log_success "Environment VPS berhasil disetup"
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
    echo "🔍 Health Check:"
    if [ ! -z "$VPS_DOMAIN" ] && [ "$VPS_DOMAIN" != "your-domain.com" ]; then
        echo "   https://$VPS_DOMAIN:$APP_PORT/health"
    else
        echo "   http://$VPS_IP:$APP_PORT/health"
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
    echo "Mulai proses deployment VPS..."
    echo ""
    
    check_config
    check_prerequisites
    
    clone_repository
    setup_vps_environment
    setup_firewall
    deploy_application
    verify_deployment
}

# Jalankan script
main
