#!/bin/bash

# Script untuk membuat ZIP file untuk EasyPanel Upload
# Internet Management System

echo "🚀 Creating ZIP file for EasyPanel upload..."

# Create temporary directory
mkdir -p temp-upload

# Copy essential files
echo "📁 Copying essential files..."
cp -r app temp-upload/
cp -r config temp-upload/
cp -r database temp-upload/
cp -r resources temp-upload/
cp -r routes temp-upload/
cp -r public temp-upload/
cp -r bootstrap temp-upload/
cp -r storage temp-upload/
cp -r docker temp-upload/
cp composer.json temp-upload/
cp composer.lock temp-upload/
cp package.json temp-upload/
cp package-lock.json temp-upload/
cp artisan temp-upload/
cp .env.example temp-upload/

# Copy Dockerfile.mysql as Dockerfile
cp Dockerfile.mysql temp-upload/Dockerfile

# Remove unnecessary files from storage
rm -rf temp-upload/storage/logs/*
rm -rf temp-upload/storage/framework/cache/*
rm -rf temp-upload/storage/framework/sessions/*
rm -rf temp-upload/storage/framework/views/*
rm -rf temp-upload/bootstrap/cache/*

# Create .gitkeep files
touch temp-upload/storage/logs/.gitkeep
touch temp-upload/storage/framework/cache/.gitkeep
touch temp-upload/storage/framework/sessions/.gitkeep
touch temp-upload/storage/framework/views/.gitkeep
touch temp-upload/bootstrap/cache/.gitkeep

echo "✅ Files prepared for upload"
echo "📦 Upload the 'temp-upload' folder to EasyPanel"
echo "🔧 Or create a ZIP file from the 'temp-upload' folder"
echo ""
echo "📋 Next steps:"
echo "1. Upload the temp-upload folder to EasyPanel"
echo "2. Set Build Path to 'Dockerfile'"
echo "3. Configure environment variables"
echo "4. Deploy"

