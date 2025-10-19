# Optimized Dockerfile untuk EasyPanel VPS Deployment dengan MySQL
# Internet Management System - Laravel Application

FROM php:8.2-apache

# Metadata
LABEL maintainer="Internet Management System"
LABEL description="Internet Management System for EasyPanel with MySQL"
LABEL version="1.0"

# Set environment variables
ENV DEBIAN_FRONTEND=noninteractive
ENV APACHE_DOCUMENT_ROOT=/var/www/html/public

# Install system dependencies
RUN apt-get update && apt-get install -y \
    git \
    curl \
    libpng-dev \
    libonig-dev \
    libxml2-dev \
    libzip-dev \
    zip \
    unzip \
    libfreetype6-dev \
    libjpeg62-turbo-dev \
    libmcrypt-dev \
    libgd-dev \
    supervisor \
    cron \
    nodejs \
    npm \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install pdo pdo_mysql mbstring exif pcntl bcmath gd zip \
    && apt-get clean \
    && rm -rf /var/lib/apt/lists/*

# Install Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Enable Apache mod_rewrite
RUN a2enmod rewrite

# Set working directory
WORKDIR /var/www/html

# Copy composer files first (for better caching)
COPY composer.json composer.lock ./

# Install PHP dependencies
RUN composer install --no-dev --optimize-autoloader --no-interaction --no-scripts

# Copy package.json and install Node.js dependencies
COPY package.json package-lock.json ./
RUN npm ci --only=production

# Copy application files
COPY . .

# Complete composer installation
RUN composer run-script post-install-cmd

# Build assets
RUN npm run build

# Create necessary directories
RUN mkdir -p storage/app storage/framework/cache storage/framework/sessions storage/framework/views storage/logs bootstrap/cache

# Set proper permissions
RUN chown -R www-data:www-data /var/www/html \
    && chmod -R 755 storage bootstrap/cache

# Copy Apache configuration
COPY docker/apache/standalone.conf /etc/apache2/sites-available/000-default.conf

# Copy supervisor configuration
COPY docker/supervisor/standalone.conf /etc/supervisor/conf.d/supervisord.conf

# Copy startup script
COPY docker/scripts/startup.sh /usr/local/bin/startup.sh
RUN chmod +x /usr/local/bin/startup.sh

# Create health check script
COPY docker/scripts/healthcheck.sh /usr/local/bin/healthcheck.sh
RUN chmod +x /usr/local/bin/healthcheck.sh

# Expose port 80
EXPOSE 80

# Health check
HEALTHCHECK --interval=30s --timeout=10s --start-period=60s --retries=3 \
    CMD /usr/local/bin/healthcheck.sh

# Start application
CMD ["/usr/local/bin/startup.sh"]
