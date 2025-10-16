#!/bin/bash

# Script untuk Build dan Upload Docker Image ke Docker Hub
# Internet Management System

echo "🐳 Docker Hub Upload Script untuk Internet Management System"
echo "=========================================================="

# Konfigurasi
IMAGE_NAME="internet-management-system"
DOCKER_HUB_USERNAME=""  # Ganti dengan username Docker Hub Anda
DOCKER_HUB_REPOSITORY=""  # Ganti dengan nama repository yang diinginkan
VERSION="latest"

# Fungsi untuk menampilkan bantuan
show_help() {
    echo "Cara menggunakan script ini:"
    echo "1. Edit script ini dan isi DOCKER_HUB_USERNAME dan DOCKER_HUB_REPOSITORY"
    echo "2. Pastikan Docker Desktop sudah berjalan"
    echo "3. Jalankan: bash docker-upload.sh"
    echo ""
    echo "Contoh konfigurasi:"
    echo "DOCKER_HUB_USERNAME=yourusername"
    echo "DOCKER_HUB_REPOSITORY=internet-management-system"
}

# Cek apakah Docker berjalan
check_docker() {
    echo "🔍 Mengecek status Docker..."
    if ! docker info > /dev/null 2>&1; then
        echo "❌ Docker tidak berjalan. Silakan jalankan Docker Desktop terlebih dahulu."
        echo "   Download Docker Desktop dari: https://www.docker.com/products/docker-desktop"
        exit 1
    fi
    echo "✅ Docker berjalan dengan baik"
}

# Build Docker image
build_image() {
    echo "🏗️  Membangun Docker image..."
    echo "   Image name: $IMAGE_NAME:$VERSION"
    
    if docker build -t $IMAGE_NAME:$VERSION .; then
        echo "✅ Docker image berhasil dibangun"
    else
        echo "❌ Gagal membangun Docker image"
        exit 1
    fi
}

# Tag image untuk Docker Hub
tag_image() {
    if [ -z "$DOCKER_HUB_USERNAME" ] || [ -z "$DOCKER_HUB_REPOSITORY" ]; then
        echo "⚠️  DOCKER_HUB_USERNAME atau DOCKER_HUB_REPOSITORY belum dikonfigurasi"
        echo "   Silakan edit script ini dan isi kedua variabel tersebut"
        show_help
        exit 1
    fi
    
    echo "🏷️  Menandai image untuk Docker Hub..."
    FULL_IMAGE_NAME="$DOCKER_HUB_USERNAME/$DOCKER_HUB_REPOSITORY:$VERSION"
    
    if docker tag $IMAGE_NAME:$VERSION $FULL_IMAGE_NAME; then
        echo "✅ Image berhasil ditandai: $FULL_IMAGE_NAME"
    else
        echo "❌ Gagal menandai image"
        exit 1
    fi
}

# Login ke Docker Hub
login_docker_hub() {
    echo "🔐 Login ke Docker Hub..."
    echo "   Silakan masukkan username dan password Docker Hub Anda"
    
    if docker login; then
        echo "✅ Berhasil login ke Docker Hub"
    else
        echo "❌ Gagal login ke Docker Hub"
        echo "   Pastikan username dan password benar"
        exit 1
    fi
}

# Push image ke Docker Hub
push_image() {
    echo "📤 Mengupload image ke Docker Hub..."
    FULL_IMAGE_NAME="$DOCKER_HUB_USERNAME/$DOCKER_HUB_REPOSITORY:$VERSION"
    
    if docker push $FULL_IMAGE_NAME; then
        echo "✅ Image berhasil diupload ke Docker Hub!"
        echo "   URL: https://hub.docker.com/r/$DOCKER_HUB_USERNAME/$DOCKER_HUB_REPOSITORY"
        echo "   Pull command: docker pull $FULL_IMAGE_NAME"
    else
        echo "❌ Gagal mengupload image ke Docker Hub"
        exit 1
    fi
}

# Verifikasi upload
verify_upload() {
    echo "🔍 Memverifikasi upload..."
    FULL_IMAGE_NAME="$DOCKER_HUB_USERNAME/$DOCKER_HUB_REPOSITORY:$VERSION"
    
    echo "✅ Upload selesai!"
    echo ""
    echo "📋 Informasi Image:"
    echo "   Repository: $DOCKER_HUB_USERNAME/$DOCKER_HUB_REPOSITORY"
    echo "   Tag: $VERSION"
    echo "   Size: $(docker images $FULL_IMAGE_NAME --format 'table {{.Size}}' | tail -1)"
    echo ""
    echo "🚀 Cara menggunakan image ini:"
    echo "   docker pull $FULL_IMAGE_NAME"
    echo "   docker run -p 8000:80 $FULL_IMAGE_NAME"
}

# Main execution
main() {
    echo "Mulai proses upload ke Docker Hub..."
    echo ""
    
    # Cek konfigurasi
    if [ -z "$DOCKER_HUB_USERNAME" ] || [ -z "$DOCKER_HUB_REPOSITORY" ]; then
        echo "⚠️  Konfigurasi belum lengkap!"
        show_help
        exit 1
    fi
    
    # Jalankan semua step
    check_docker
    build_image
    tag_image
    login_docker_hub
    push_image
    verify_upload
    
    echo ""
    echo "🎉 Proses upload selesai!"
}

# Jalankan script
main
