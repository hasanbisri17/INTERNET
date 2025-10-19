#!/bin/bash

# Script untuk membuat package deployment yang lengkap
# Usage: ./create-deployment-package.sh

set -e

PACKAGE_DIR="deployment-package"
VERSION=$(date +%Y%m%d_%H%M%S)

echo "📦 Creating deployment package..."

# Create package directory
mkdir -p ${PACKAGE_DIR}

# Copy necessary files
echo "📋 Copying files..."
cp docker-compose.yml ${PACKAGE_DIR}/
cp run-on-server.sh ${PACKAGE_DIR}/
cp setup-app.sh ${PACKAGE_DIR}/
cp README_DOCKER.md ${PACKAGE_DIR}/README.md
cp DOCKER_DEPLOYMENT_GUIDE.md ${PACKAGE_DIR}/

# Create deployment script
cat > ${PACKAGE_DIR}/deploy.sh << 'EOF'
#!/bin/bash

# Quick deployment script
# Usage: ./deploy.sh [image-name] [tag]

set -e

IMAGE_NAME=${1:-"internet-management"}
TAG=${2:-"latest"}
FULL_IMAGE_NAME="${IMAGE_NAME}:${TAG}"

echo "🚀 Deploying Internet Management Application..."
echo "Image: ${FULL_IMAGE_NAME}"
echo "Port: 1217"
echo ""

# Run the application
./run-on-server.sh ${FULL_IMAGE_NAME}

# Setup the application
./setup-app.sh

echo ""
echo "🎉 Deployment completed!"
echo "🌐 Application is running at: http://localhost:1217"
echo "📋 Default login: admin@example.com / password"
echo ""
echo "⚠️  Please change the default password after first login!"
EOF

chmod +x ${PACKAGE_DIR}/deploy.sh

# Create environment template
cat > ${PACKAGE_DIR}/.env.template << 'EOF'
APP_NAME="Internet Management"
APP_ENV=production
APP_KEY=base64:your-app-key-here
APP_DEBUG=false
APP_URL=http://localhost:1217

DB_CONNECTION=sqlite
DB_DATABASE=/var/www/html/database/database.sqlite

CACHE_DRIVER=file
SESSION_DRIVER=file
QUEUE_CONNECTION=sync

MAIL_MAILER=log
MAIL_FROM_ADDRESS="admin@example.com"
MAIL_FROM_NAME="Internet Management"
EOF

# Create package info
cat > ${PACKAGE_DIR}/PACKAGE_INFO.txt << EOF
Internet Management - Docker Deployment Package
Version: ${VERSION}
Created: $(date)
Port: 1217

Files included:
- docker-compose.yml: Docker Compose configuration
- run-on-server.sh: Script to run application on server
- setup-app.sh: Script to setup application
- deploy.sh: Quick deployment script
- README.md: Documentation
- DOCKER_DEPLOYMENT_GUIDE.md: Detailed deployment guide
- .env.template: Environment variables template

Quick start:
1. ./deploy.sh your-dockerhub-username/internet-management latest
2. Access: http://localhost:1217
3. Login: admin@example.com / password
EOF

# Create zip package
echo "📦 Creating zip package..."
zip -r "internet-management-docker-${VERSION}.zip" ${PACKAGE_DIR}/

echo "✅ Deployment package created!"
echo "📁 Package: internet-management-docker-${VERSION}.zip"
echo "📁 Directory: ${PACKAGE_DIR}/"
echo ""
echo "📋 Package contents:"
ls -la ${PACKAGE_DIR}/
echo ""
echo "🚀 To deploy:"
echo "1. Extract the zip file on your server"
echo "2. Run: ./deploy.sh your-dockerhub-username/internet-management latest"
echo "3. Access: http://localhost:1217"