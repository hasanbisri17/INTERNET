#!/bin/bash

# Script untuk menjalankan aplikasi di server Docker
# Usage: ./run-on-server.sh [image-name] [tag]

set -e

# Default values
IMAGE_NAME=${1:-"internet-management"}
TAG=${2:-"latest"}
FULL_IMAGE_NAME="${IMAGE_NAME}:${TAG}"

echo "🐳 Pulling Docker image: ${FULL_IMAGE_NAME}"

# Pull the latest image
docker pull ${FULL_IMAGE_NAME}

echo "🛑 Stopping existing containers (if any)..."
docker stop internet-app 2>/dev/null || true
docker rm internet-app 2>/dev/null || true
docker stop internet-mysql 2>/dev/null || true
docker rm internet-mysql 2>/dev/null || true

echo "🚀 Starting new container..."

# Create necessary directories
mkdir -p ./storage
mkdir -p ./public

# Run MySQL container
echo "🗄️  Starting MySQL database..."
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
echo "⏳ Waiting for MySQL to be ready..."
sleep 30

# Run the application container
echo "🚀 Starting application..."
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
    ${FULL_IMAGE_NAME}

echo "✅ Container started successfully!"
echo "🌐 Application is running at: http://localhost:1217"
echo ""
echo "📋 Useful commands:"
echo "   View logs: docker logs -f internet-app"
echo "   Stop app:  docker stop internet-app"
echo "   Start app: docker start internet-app"
echo "   Remove app: docker rm -f internet-app"
echo ""
echo "🎉 Done!"
