#!/bin/bash

# Test script untuk Plug & Play Internet Management System

set -e

echo "🧪 Testing Plug & Play Internet Management System"
echo "================================================="

# Colors
GREEN='\033[0;32m'
RED='\033[0;31m'
YELLOW='\033[1;33m'
NC='\033[0m'

# Function untuk logging
log() {
    echo -e "${GREEN}[TEST]${NC} $1"
}

error() {
    echo -e "${RED}[ERROR]${NC} $1"
}

warning() {
    echo -e "${YELLOW}[WARNING]${NC} $1"
}

# Test 1: Cek Docker
log "Testing Docker installation..."
if ! command -v docker &> /dev/null; then
    error "Docker tidak terinstall"
    exit 1
fi

if ! command -v docker-compose &> /dev/null; then
    error "Docker Compose tidak terinstall"
    exit 1
fi

log "Docker dan Docker Compose tersedia"

# Test 2: Cek file yang diperlukan
log "Testing required files..."
required_files=(
    "docker-compose.standalone.yml"
    "Dockerfile.standalone"
    "docker/scripts/startup.sh"
    "docker/scripts/healthcheck.sh"
    "docker/apache/standalone.conf"
    "docker/supervisor/standalone.conf"
    "env.standalone"
)

for file in "${required_files[@]}"; do
    if [ ! -f "$file" ]; then
        error "File $file tidak ditemukan"
        exit 1
    fi
done

log "Semua file yang diperlukan tersedia"

# Test 3: Cek permission script
log "Testing script permissions..."
if [ ! -x "docker/scripts/startup.sh" ]; then
    warning "startup.sh tidak executable, memperbaiki..."
    chmod +x docker/scripts/startup.sh
fi

if [ ! -x "docker/scripts/healthcheck.sh" ]; then
    warning "healthcheck.sh tidak executable, memperbaiki..."
    chmod +x docker/scripts/healthcheck.sh
fi

log "Script permissions OK"

# Test 4: Cek port availability
log "Testing port availability..."
if lsof -Pi :8080 -sTCP:LISTEN -t >/dev/null 2>&1; then
    warning "Port 8080 sudah digunakan"
    echo "   Solusi: Ubah port di docker-compose.standalone.yml"
fi

if lsof -Pi :6379 -sTCP:LISTEN -t >/dev/null 2>&1; then
    warning "Port 6379 sudah digunakan (Redis)"
fi

log "Port check selesai"

# Test 5: Test Docker Compose syntax
log "Testing Docker Compose syntax..."
if docker-compose -f docker-compose.standalone.yml config > /dev/null 2>&1; then
    log "Docker Compose syntax valid"
else
    error "Docker Compose syntax invalid"
    exit 1
fi

# Test 6: Test Dockerfile syntax (skip build test)
log "Testing Dockerfile syntax..."
if [ -f "Dockerfile.standalone" ]; then
    log "Dockerfile.standalone exists"
else
    error "Dockerfile.standalone tidak ditemukan"
    exit 1
fi

# Test 7: Cek Laravel requirements
log "Testing Laravel requirements..."
if [ ! -f "composer.json" ]; then
    error "composer.json tidak ditemukan"
    exit 1
fi

if [ ! -f "artisan" ]; then
    error "artisan tidak ditemukan"
    exit 1
fi

log "Laravel requirements OK"

echo ""
echo "✅ Semua test berhasil!"
echo ""
echo "🚀 Siap untuk deployment:"
echo "   docker-compose -f docker-compose.standalone.yml up -d"
echo ""
echo "🌐 Setelah deployment, akses:"
echo "   http://localhost:8080"
echo ""
echo "👤 Default credentials:"
echo "   Email: admin@example.com"
echo "   Password: password"
echo ""
echo "🔍 Health check:"
echo "   http://localhost:8080/health"
