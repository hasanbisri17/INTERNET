# ============================================================================
# Internet Management System - Production Dockerfile for EasyPanel
# ============================================================================
# Multi-stage build untuk optimasi ukuran image
# Stack: PHP 8.2 + Nginx + Supervisor + Laravel 12
# Port: 1217
# ============================================================================

# =========================
# Stage 1: Composer Dependencies
# =========================
FROM composer:2.7 AS composer-deps

WORKDIR /app

# Copy composer files
COPY composer.json composer.lock ./

# Install dependencies without dev
RUN composer install \
    --no-dev \
    --no-scripts \
    --no-autoloader \
    --prefer-dist \
    --ignore-platform-reqs

# =========================
# Stage 2: Node.js Assets
# =========================
FROM node:20-alpine AS node-assets

WORKDIR /app

# Copy package files
COPY package.json package-lock.json ./

# Install dependencies
RUN npm ci --legacy-peer-deps

# Copy source files for building
COPY resources ./resources
COPY vite.config.js postcss.config.js tailwind.config.js ./

# Build assets
RUN npm run build

# =========================
# Stage 3: Production Image
# =========================
FROM php:8.2-fpm-alpine AS production

LABEL maintainer="Internet Management System"
LABEL description="ISP Management System with Laravel, Filament, and Mikrotik Integration"
LABEL version="1.0.0"

# Arguments
ARG APP_ENV=production
ARG APP_DEBUG=false

# Environment variables
ENV APP_ENV=${APP_ENV} \
    APP_DEBUG=${APP_DEBUG} \
    TZ=Asia/Jakarta \
    COMPOSER_ALLOW_SUPERUSER=1 \
    # PHP settings
    PHP_MEMORY_LIMIT=512M \
    PHP_MAX_EXECUTION_TIME=300 \
    PHP_UPLOAD_MAX_FILESIZE=100M \
    PHP_POST_MAX_SIZE=100M \
    # Application
    APP_PORT=1217

# Set timezone
RUN apk add --no-cache tzdata \
    && cp /usr/share/zoneinfo/$TZ /etc/localtime \
    && echo $TZ > /etc/timezone

# Install system dependencies
RUN apk add --no-cache \
    # Nginx
    nginx \
    # Supervisor
    supervisor \
    # Cron
    dcron \
    # Tools
    curl \
    wget \
    git \
    zip \
    unzip \
    bash \
    # Image processing
    libpng-dev \
    libjpeg-turbo-dev \
    freetype-dev \
    libwebp-dev \
    # Database
    mysql-client \
    # XML
    libxml2-dev \
    # Intl
    icu-dev \
    # ZIP
    libzip-dev \
    # Other
    oniguruma-dev \
    linux-headers \
    # For health check
    fcgi

# Install PHP extensions
RUN docker-php-ext-configure gd \
    --with-freetype \
    --with-jpeg \
    --with-webp \
    && docker-php-ext-install -j$(nproc) \
    pdo \
    pdo_mysql \
    mysqli \
    gd \
    bcmath \
    opcache \
    pcntl \
    intl \
    zip \
    exif \
    xml \
    mbstring

# Install Redis extension
RUN apk add --no-cache --virtual .build-deps $PHPIZE_DEPS \
    && pecl install redis \
    && docker-php-ext-enable redis \
    && apk del .build-deps

# Set working directory
WORKDIR /var/www/html

# Copy Composer from official image
COPY --from=composer:2.7 /usr/bin/composer /usr/bin/composer

# Copy Composer dependencies from stage 1
COPY --from=composer-deps /app/vendor ./vendor

# Copy application files
COPY --chown=www-data:www-data . .

# Copy built assets from Node stage
COPY --from=node-assets /app/public/build ./public/build

# Generate optimized autoload
RUN composer dump-autoload --optimize --no-dev --classmap-authoritative

# ============================================================================
# CONFIGURATION FILES
# ============================================================================

# Create PHP configuration
RUN cat > /usr/local/etc/php/conf.d/99-app.ini << 'EOF'
[PHP]
engine = On
short_open_tag = Off
precision = 14
output_buffering = 4096
implicit_flush = Off
serialize_precision = -1
zend.enable_gc = On
expose_php = Off

max_execution_time = 300
max_input_time = 60
memory_limit = 512M
error_reporting = E_ALL & ~E_DEPRECATED & ~E_STRICT
display_errors = Off
display_startup_errors = Off
log_errors = On
log_errors_max_len = 1024

post_max_size = 100M
file_uploads = On
upload_max_filesize = 100M
max_file_uploads = 20

allow_url_fopen = On
allow_url_include = Off
default_socket_timeout = 60

[Date]
date.timezone = Asia/Jakarta

[Session]
session.save_handler = files
session.use_strict_mode = 0
session.use_cookies = 1
session.use_only_cookies = 1
session.cookie_httponly = 1
session.gc_maxlifetime = 1440

[OPcache]
opcache.enable = 1
opcache.enable_cli = 1
opcache.memory_consumption = 256
opcache.interned_strings_buffer = 16
opcache.max_accelerated_files = 10000
opcache.revalidate_freq = 0
opcache.validate_timestamps = 0
opcache.save_comments = 1
opcache.fast_shutdown = 1
EOF

# Create PHP-FPM pool configuration
RUN cat > /usr/local/etc/php-fpm.d/www.conf << 'EOF'
[www]
user = www-data
group = www-data
listen = 127.0.0.1:9000
listen.owner = www-data
listen.group = www-data

pm = dynamic
pm.max_children = 50
pm.start_servers = 5
pm.min_spare_servers = 5
pm.max_spare_servers = 35
pm.max_requests = 500

clear_env = no
catch_workers_output = yes
decorate_workers_output = no

php_admin_value[error_log] = /var/log/php-fpm.log
php_admin_flag[log_errors] = on
EOF

# Create Nginx configuration
RUN cat > /etc/nginx/http.d/default.conf << 'EOF'
server {
    listen 1217;
    listen [::]:1217;
    server_name _;
    root /var/www/html/public;
    index index.php index.html;

    charset utf-8;
    client_max_body_size 100M;

    # Security headers
    add_header X-Frame-Options "SAMEORIGIN" always;
    add_header X-XSS-Protection "1; mode=block" always;
    add_header X-Content-Type-Options "nosniff" always;
    add_header Referrer-Policy "no-referrer-when-downgrade" always;

    # Gzip compression
    gzip on;
    gzip_vary on;
    gzip_min_length 1024;
    gzip_proxied any;
    gzip_types text/plain text/css text/xml text/javascript application/x-javascript application/xml application/javascript application/json;
    gzip_disable "MSIE [1-6]\.";

    # Handle Laravel routes
    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    # Handle PHP files
    location ~ \.php$ {
        fastcgi_split_path_info ^(.+\.php)(/.+)$;
        fastcgi_pass 127.0.0.1:9000;
        fastcgi_index index.php;
        include fastcgi_params;
        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
        fastcgi_param PATH_INFO $fastcgi_path_info;
        fastcgi_hide_header X-Powered-By;
        fastcgi_read_timeout 300;
        fastcgi_buffers 16 16k;
        fastcgi_buffer_size 32k;
    }

    # Handle static files
    location ~* \.(js|css|png|jpg|jpeg|gif|ico|svg|woff|woff2|ttf|eot)$ {
        expires 1y;
        add_header Cache-Control "public, immutable";
        access_log off;
        try_files $uri =404;
    }

    # Health check endpoint
    location = /up {
        access_log off;
        add_header Content-Type text/plain;
        return 200 'OK';
    }

    # Deny access to sensitive files
    location ~ /\. {
        deny all;
    }

    location ~ ^/(storage|bootstrap/cache) {
        deny all;
    }

    # Deny access to PHP files in storage
    location ~* ^/storage/.*\.php$ {
        deny all;
    }
}
EOF

# Create Supervisor configuration
RUN mkdir -p /etc/supervisor.d && cat > /etc/supervisor.d/app.ini << 'EOF'
[supervisord]
nodaemon=true
user=root
logfile=/var/log/supervisor/supervisord.log
pidfile=/var/run/supervisord.pid
loglevel=warn

[program:nginx]
command=nginx -g "daemon off;"
autostart=true
autorestart=true
priority=10
stdout_logfile=/dev/stdout
stdout_logfile_maxbytes=0
stderr_logfile=/dev/stderr
stderr_logfile_maxbytes=0

[program:php-fpm]
command=php-fpm -F
autostart=true
autorestart=true
priority=5
stdout_logfile=/dev/stdout
stdout_logfile_maxbytes=0
stderr_logfile=/dev/stderr
stderr_logfile_maxbytes=0

[program:laravel-queue]
process_name=%(program_name)s_%(process_num)02d
command=php /var/www/html/artisan queue:work --sleep=3 --tries=3 --max-time=3600 --memory=256
directory=/var/www/html
autostart=true
autorestart=true
stopasgroup=true
killasgroup=true
user=www-data
numprocs=2
priority=20
redirect_stderr=true
stdout_logfile=/var/log/supervisor/queue.log
stopwaitsecs=60

[program:laravel-schedule]
command=/bin/sh -c "while true; do php /var/www/html/artisan schedule:run --verbose --no-interaction >> /var/log/supervisor/schedule.log 2>&1; sleep 60; done"
directory=/var/www/html
autostart=true
autorestart=true
user=www-data
priority=30
stdout_logfile=/dev/stdout
stdout_logfile_maxbytes=0
stderr_logfile=/dev/stderr
stderr_logfile_maxbytes=0
EOF

# Create startup script
RUN cat > /usr/local/bin/startup.sh << 'STARTUPEOF'
#!/bin/bash
set -e

echo "=================================================="
echo "🚀 Starting Internet Management System..."
echo "=================================================="

log() {
    echo "[$(date '+%Y-%m-%d %H:%M:%S')] $1"
}

# Create required directories
log "Creating required directories..."
mkdir -p /var/log/supervisor
mkdir -p /var/log/nginx
touch /var/log/supervisor/schedule.log /var/log/supervisor/queue.log
chown -R www-data:www-data /var/log/supervisor
chmod -R 775 /var/log/supervisor
mkdir -p /var/www/html/storage/app/public
mkdir -p /var/www/html/storage/framework/{cache,sessions,views}
mkdir -p /var/www/html/storage/logs
mkdir -p /var/www/html/bootstrap/cache

# Set permissions
log "Setting permissions..."
chown -R www-data:www-data /var/www/html/storage
chown -R www-data:www-data /var/www/html/bootstrap/cache
chmod -R 775 /var/www/html/storage
chmod -R 775 /var/www/html/bootstrap/cache

# Wait for database to be ready (if using MySQL)
if [ ! -z "$DB_HOST" ] && [ "$DB_HOST" != "localhost" ] && [ "$DB_HOST" != "127.0.0.1" ]; then
    log "Waiting for database connection..."
    max_attempts=30
    attempt=0
    while [ $attempt -lt $max_attempts ]; do
        if php -r "try { new PDO('mysql:host=' . getenv('DB_HOST') . ';port=' . (getenv('DB_PORT') ?: 3306), getenv('DB_USERNAME'), getenv('DB_PASSWORD')); echo 'OK'; } catch (Exception \$e) { exit(1); }" 2>/dev/null; then
            log "Database connection established!"
            break
        fi
        attempt=$((attempt + 1))
        log "Database not ready, attempt $attempt/$max_attempts..."
        sleep 2
    done
    
    if [ $attempt -eq $max_attempts ]; then
        log "WARNING: Could not connect to database. Proceeding anyway..."
    fi
fi

# Generate APP_KEY if not set
if [ -z "$APP_KEY" ] || [ "$APP_KEY" = "base64:" ]; then
    log "Generating application key..."
    php artisan key:generate --force --no-interaction
fi

# Clear and cache configuration
log "Optimizing application..."
php artisan config:clear
php artisan route:clear
php artisan view:clear

# Cache for production
if [ "$APP_ENV" = "production" ]; then
    log "Caching configuration for production..."
    php artisan config:cache
    php artisan route:cache
    php artisan view:cache
    php artisan event:cache
    php artisan filament:cache-components
fi

# Run migrations
if [ "$RUN_MIGRATIONS" = "true" ] || [ ! -f /var/www/html/storage/.migrated ]; then
    log "Running database migrations..."
    if php artisan migrate --force --no-interaction; then
        touch /var/www/html/storage/.migrated
        log "Migrations completed successfully!"
    else
        log "WARNING: Migration failed. Check database connection."
    fi
fi

# Run seeders on first run
if [ "$RUN_SEEDERS" = "true" ] || [ ! -f /var/www/html/storage/.seeded ]; then
    log "Running database seeders..."
    if php artisan db:seed --force --no-interaction; then
        touch /var/www/html/storage/.seeded
        log "Seeders completed successfully!"
    else
        log "WARNING: Seeding failed."
    fi
fi

# Create storage symlink
log "Creating storage symlink..."
php artisan storage:link --force 2>/dev/null || true

# Final permissions fix
chown -R www-data:www-data /var/www/html/storage
chown -R www-data:www-data /var/www/html/bootstrap/cache

echo "=================================================="
log "✅ Internet Management System is ready!"
echo "=================================================="
log "🌐 Application URL: ${APP_URL:-http://localhost:1217}"
log "📁 Admin Panel: ${APP_URL:-http://localhost:1217}/admin"
log "❤️  Health Check: ${APP_URL:-http://localhost:1217}/health"
echo ""
log "👤 Default Credentials:"
log "   Email: admin@example.com"
log "   Password: password"
echo ""
log "⚠️  Please change the default password after first login!"
echo "=================================================="

# Start Supervisor (manages nginx, php-fpm, queue worker, scheduler)
exec /usr/bin/supervisord -n -c /etc/supervisor.d/app.ini
STARTUPEOF

RUN chmod +x /usr/local/bin/startup.sh

# Create healthcheck script
RUN cat > /usr/local/bin/healthcheck.sh << 'HEALTHEOF'
#!/bin/bash

# Check if PHP-FPM is running
if ! pgrep -x "php-fpm" > /dev/null; then
    echo "PHP-FPM is not running"
    exit 1
fi

# Check if Nginx is running
if ! pgrep -x "nginx" > /dev/null; then
    echo "Nginx is not running"
    exit 1
fi

# Check HTTP response
response=$(curl -sf http://127.0.0.1:1217/health 2>/dev/null)
if [ $? -ne 0 ]; then
    # Try the /up endpoint as fallback
    response=$(curl -sf http://127.0.0.1:1217/up 2>/dev/null)
    if [ $? -ne 0 ]; then
        echo "Application is not responding"
        exit 1
    fi
fi

echo "Health check passed"
exit 0
HEALTHEOF

RUN chmod +x /usr/local/bin/healthcheck.sh

# ============================================================================
# FINAL SETUP
# ============================================================================

# Remove development files
RUN rm -rf \
    /var/www/html/.git \
    /var/www/html/.github \
    /var/www/html/node_modules \
    /var/www/html/tests \
    /var/www/html/.env.example \
    /var/www/html/phpunit.xml \
    /var/www/html/*.md \
    /var/www/html/*.sh \
    /var/www/html/docker \
    /var/www/html/temp-upload \
    /var/www/html/.cursor \
    /var/www/html/.trae

# Set final permissions
RUN chown -R www-data:www-data /var/www/html \
    && chmod -R 755 /var/www/html \
    && chmod -R 775 /var/www/html/storage \
    && chmod -R 775 /var/www/html/bootstrap/cache

# Expose port
EXPOSE 1217

# Health check
HEALTHCHECK --interval=30s --timeout=10s --start-period=60s --retries=3 \
    CMD /usr/local/bin/healthcheck.sh

# Default environment variables
ENV APP_NAME="Internet Management" \
    APP_ENV=production \
    APP_DEBUG=false \
    APP_URL=http://localhost:1217 \
    LOG_CHANNEL=stack \
    LOG_LEVEL=error \
    DB_CONNECTION=mysql \
    DB_HOST=mysql \
    DB_PORT=3306 \
    DB_DATABASE=internet_management \
    DB_USERNAME=root \
    DB_PASSWORD=password \
    CACHE_DRIVER=file \
    SESSION_DRIVER=file \
    QUEUE_CONNECTION=database \
    MAIL_MAILER=log \
    RUN_MIGRATIONS=true \
    RUN_SEEDERS=true

# Start application
CMD ["/usr/local/bin/startup.sh"]
