#!/bin/bash

# Script untuk deploy ke EasyPanel
# Usage: ./easypanel-deploy.sh

set -e

echo "🚀 EasyPanel Deployment Guide"
echo "=============================="
echo ""

echo "📋 Step 1: Create Project in EasyPanel"
echo "1. Login to EasyPanel dashboard"
echo "2. Click 'New Project'"
echo "3. Select 'Docker'"
echo "4. Name: internet-management"
echo ""

echo "📋 Step 2: Configure Docker Image"
echo "Image Source: Docker Hub"
echo "Image Name: habis12/internet-management:latest"
echo "Tag: latest"
echo ""

echo "📋 Step 3: Environment Variables"
echo "Copy and paste these environment variables:"
echo ""

# Generate random APP_KEY
APP_KEY=$(openssl rand -base64 32)

cat << EOF
APP_NAME=Internet Management
APP_ENV=production
APP_KEY=base64:${APP_KEY}
APP_DEBUG=false
APP_URL=https://your-domain.com
DB_CONNECTION=mysql
DB_HOST=mysql
DB_PORT=3306
DB_DATABASE=internet_management
DB_USERNAME=root
DB_PASSWORD=your-secure-password
CACHE_DRIVER=file
SESSION_DRIVER=file
QUEUE_CONNECTION=sync
MAIL_MAILER=log
MAIL_FROM_ADDRESS=admin@your-domain.com
MAIL_FROM_NAME=Internet Management
LOG_CHANNEL=stack
LOG_LEVEL=info
EOF

echo ""
echo "📋 Step 4: Database Setup"
echo "Option A: Create MySQL service in EasyPanel"
echo "  - Service Type: MySQL"
echo "  - Version: 8.0"
echo "  - Database: internet_management"
echo "  - Username: root"
echo "  - Password: your-secure-password"
echo ""
echo "Option B: Use external MySQL database"
echo "  - Update DB_HOST to external database host"
echo "  - Update DB_PASSWORD to external database password"
echo ""

echo "📋 Step 5: Deploy"
echo "1. Click 'Deploy' button"
echo "2. Wait for deployment to complete"
echo "3. Check logs for any errors"
echo ""

echo "📋 Step 6: Setup Application"
echo "After deployment, run these commands in EasyPanel terminal:"
echo ""

cat << 'EOF'
# Access container
docker exec -it internet-management-container bash

# Install dependencies
composer install --no-dev --optimize-autoloader

# Generate app key
php artisan key:generate --force

# Run migrations
php artisan migrate --force

# Seed database
php artisan db:seed --force

# Setup storage
php artisan storage:link

# Set permissions
chown -R www-data:www-data /var/www/html/storage
chmod -R 755 /var/www/html/storage
EOF

echo ""
echo "📋 Step 7: Verify Deployment"
echo "1. Check health endpoint: https://your-domain.com/health"
echo "2. Access application: https://your-domain.com"
echo "3. Default login: admin@example.com / password"
echo ""

echo "✅ Deployment completed!"
echo "🌐 Your application should be accessible at: https://your-domain.com"
echo ""

echo "📞 Need help?"
echo "- Check EasyPanel logs for errors"
echo "- Verify environment variables"
echo "- Test database connection"
echo "- Contact EasyPanel support"
