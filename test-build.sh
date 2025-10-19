#!/bin/bash

# Script untuk test build Docker image
# Usage: ./test-build.sh

set -e

echo "🧪 Testing Docker build..."

# Build the image
docker build -t internet-management:test .

echo "✅ Build successful!"

# Test run the container
echo "🚀 Testing container run..."
docker run -d \
    --name internet-app-test \
    -p 1217:1217 \
    -e APP_KEY="base64:$(openssl rand -base64 32)" \
    -e APP_URL="http://localhost:1217" \
    internet-management:test

# Wait for container to start
echo "⏳ Waiting for container to start..."
sleep 10

# Test health endpoint
echo "🔍 Testing health endpoint..."
if curl -f http://localhost:1217/health > /dev/null 2>&1; then
    echo "✅ Health check passed!"
else
    echo "❌ Health check failed!"
    docker logs internet-app-test
    exit 1
fi

# Cleanup
echo "🧹 Cleaning up test container..."
docker stop internet-app-test
docker rm internet-app-test

echo "🎉 Test completed successfully!"
echo "✅ Docker image is ready for production!"
