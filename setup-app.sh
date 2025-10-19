#!/bin/bash

# Script untuk setup awal aplikasi di container
# Usage: ./setup-app.sh

set -e

echo "🔧 Setting up application..."

# Check if container is running
if ! docker ps | grep -q internet-app; then
    echo "❌ Container 'internet-app' is not running. Please start it first."
    exit 1
fi

echo "📦 Installing dependencies..."
docker exec internet-app composer install --no-dev --optimize-autoloader

echo "🗄️  Setting up database..."
# Wait for database connection
echo "⏳ Waiting for database connection..."
sleep 10

# Run migrations
docker exec internet-app php artisan migrate --force

echo "🌱 Seeding database..."
docker exec internet-app php artisan db:seed --force

echo "🔑 Generating application key..."
docker exec internet-app php artisan key:generate --force

echo "📁 Setting up storage..."
docker exec internet-app php artisan storage:link

echo "🗂️  Setting permissions..."
docker exec internet-app chown -R www-data:www-data /var/www/html/storage
docker exec internet-app chmod -R 755 /var/www/html/storage

echo "✅ Application setup completed!"
echo "🌐 You can now access the application at: http://localhost:1217"
echo "🗄️  MySQL database is running on port 3306"
echo ""
echo "📋 Database credentials:"
echo "   Host: localhost:3306"
echo "   Database: internet_management"
echo "   Username: root"
echo "   Password: password"
echo ""
echo "📋 Default login credentials:"
echo "   Email: admin@example.com"
echo "   Password: password"
echo ""
echo "⚠️  Please change the default password after first login!"
