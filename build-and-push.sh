#!/bin/bash

# Script untuk build dan push Docker image ke Docker Hub
# Usage: ./build-and-push.sh [image-name] [tag]

set -e

# Default values
IMAGE_NAME=${1:-"internet-management"}
TAG=${2:-"latest"}
FULL_IMAGE_NAME="${IMAGE_NAME}:${TAG}"

echo "🚀 Building Docker image: ${FULL_IMAGE_NAME}"

# Build the image
docker build -t ${FULL_IMAGE_NAME} .

echo "✅ Image built successfully!"

# Ask if user wants to push to Docker Hub
read -p "Do you want to push this image to Docker Hub? (y/N): " -n 1 -r
echo
if [[ $REPLY =~ ^[Yy]$ ]]; then
    echo "📤 Pushing image to Docker Hub..."
    
    # Login to Docker Hub (if not already logged in)
    if ! docker info | grep -q "Username:"; then
        echo "Please login to Docker Hub first:"
        docker login
    fi
    
    # Push the image
    docker push ${FULL_IMAGE_NAME}
    
    echo "✅ Image pushed successfully!"
    echo "🐳 You can now pull and run this image on any Docker server with:"
    echo "   # First run MySQL:"
    echo "   docker run -d --name internet-mysql -e MYSQL_ROOT_PASSWORD=password -e MYSQL_DATABASE=internet_management -p 3306:3306 mysql:8.0"
    echo "   # Then run the app:"
    echo "   docker run -d -p 1217:1217 --name internet-app ${FULL_IMAGE_NAME}"
    echo ""
    echo "📋 Or use docker-compose:"
    echo "   docker-compose up -d"
else
    echo "ℹ️  Image built locally. You can push it later with:"
    echo "   docker push ${FULL_IMAGE_NAME}"
fi

echo "🎉 Done!"
