#!/bin/bash

# Script untuk membuat Docker image production yang siap deploy
# Usage: ./create-production-image.sh [version]

set -e

VERSION=${1:-"latest"}
IMAGE_NAME="internet-management"
FULL_IMAGE_NAME="${IMAGE_NAME}:${VERSION}"

echo "🏭 Creating production Docker image: ${FULL_IMAGE_NAME}"

# Build the image
echo "🔨 Building image..."
docker build -t ${FULL_IMAGE_NAME} .

echo "✅ Image built successfully!"

# Test the image
echo "🧪 Testing image..."
./test-build.sh

if [ $? -eq 0 ]; then
    echo "✅ Image test passed!"
else
    echo "❌ Image test failed!"
    exit 1
fi

# Create production tag
if [ "$VERSION" != "latest" ]; then
    docker tag ${FULL_IMAGE_NAME} ${IMAGE_NAME}:latest
    echo "📌 Created latest tag"
fi

echo "🎉 Production image created successfully!"
echo "🐳 Image: ${FULL_IMAGE_NAME}"
echo ""
echo "📋 Next steps:"
echo "1. Push to Docker Hub:"
echo "   docker tag ${FULL_IMAGE_NAME} your-username/${IMAGE_NAME}:${VERSION}"
echo "   docker push your-username/${IMAGE_NAME}:${VERSION}"
echo ""
echo "2. Deploy on server:"
echo "   ./run-on-server.sh your-username/${IMAGE_NAME}:${VERSION}"
echo ""
echo "3. Setup application:"
echo "   ./setup-app.sh"
