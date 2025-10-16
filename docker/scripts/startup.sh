#!/bin/bash

# Startup script untuk Internet Management System
# Auto-setup seperti Portainer, WAHA, dll
# Support untuk localhost dan VPS deployment

set -e

echo "🚀 Starting Internet Management System..."

# Function untuk logging
log() {
    echo "[$(date '+%Y-%m-%d %H:%M:%S')] $1"
}

# Function untuk error handling
error_exit() {
    log "ERROR: $1"
    exit 1
}

# Function untuk detect VPS mode
detect_vps_mode() {
    if [ "$VPS_MODE" = "true" ]; then
        log "VPS mode detected"
        
        # Set APP_URL berdasarkan VPS_DOMAIN atau VPS_IP
        if [ ! -z "$VPS_DOMAIN" ] && [ "$VPS_DOMAIN" != "your-domain.com" ]; then
            APP_URL="https://$VPS_DOMAIN:${APP_PORT:-8080}"
            SANCTUM_STATEFUL_DOMAINS="$VPS_DOMAIN:${APP_PORT:-8080}"
            SESSION_DOMAIN="$VPS_DOMAIN"
        elif [ ! -z "$VPS_IP" ] && [ "$VPS_IP" != "192.168.1.100" ]; then
            APP_URL="http://$VPS_IP:${APP_PORT:-8080}"
            SANCTUM_STATEFUL_DOMAINS="$VPS_IP:${APP_PORT:-8080}"
            SESSION_DOMAIN="$VPS_IP"
        else
            # Fallback ke localhost
            APP_URL="http://localhost:${APP_PORT:-8080}"
            SANCTUM_STATEFUL_DOMAINS="localhost:${APP_PORT:-8080}"
            SESSION_DOMAIN="localhost"
        fi
        
        log "VPS Configuration:"
        log "  APP_URL: $APP_URL"
        log "  SANCTUM_STATEFUL_DOMAINS: $SANCTUM_STATEFUL_DOMAINS"
        log "  SESSION_DOMAIN: $SESSION_DOMAIN"
    else
        log "Localhost mode detected"
        APP_URL="http://localhost:${APP_PORT:-8080}"
        SANCTUM_STATEFUL_DOMAINS="localhost:${APP_PORT:-8080}"
        SESSION_DOMAIN="localhost"
    fi
}

# Check if this is first run
if [ ! -f /var/www/html/storage/.installed ]; then
    log "First run detected. Setting up application..."
    
    # Detect VPS mode
    detect_vps_mode
    
    # Generate APP_KEY if not exists
    if [ -z "$APP_KEY" ] || [ "$APP_KEY" = "base64:" ]; then
        log "Generating application key..."
        APP_KEY=$(php -r "echo 'base64:' . base64_encode(random_bytes(32));")
    fi
    
    # Create .env file if not exists
    if [ ! -f /var/www/html/.env ]; then
        log "Creating environment file..."
        cp /var/www/html/.env.example /var/www/html/.env
        
        # Update .env with environment variables
        if [ ! -z "$APP_KEY" ]; then
            sed -i "s/APP_KEY=.*/APP_KEY=$APP_KEY/" /var/www/html/.env
        fi
        
        if [ ! -z "$APP_URL" ]; then
            sed -i "s|APP_URL=.*|APP_URL=$APP_URL|" /var/www/html/.env
        fi
        
        if [ ! -z "$DB_CONNECTION" ]; then
            sed -i "s/DB_CONNECTION=.*/DB_CONNECTION=$DB_CONNECTION/" /var/www/html/.env
        fi
        
        if [ ! -z "$DB_DATABASE" ]; then
            sed -i "s|DB_DATABASE=.*|DB_DATABASE=$DB_DATABASE|" /var/www/html/.env
        fi
        
        if [ ! -z "$CACHE_DRIVER" ]; then
            sed -i "s/CACHE_DRIVER=.*/CACHE_DRIVER=$CACHE_DRIVER/" /var/www/html/.env
        fi
        
        if [ ! -z "$SESSION_DRIVER" ]; then
            sed -i "s/SESSION_DRIVER=.*/SESSION_DRIVER=$SESSION_DRIVER/" /var/www/html/.env
        fi
        
        if [ ! -z "$MAIL_FROM_ADDRESS" ]; then
            sed -i "s/MAIL_FROM_ADDRESS=.*/MAIL_FROM_ADDRESS=$MAIL_FROM_ADDRESS/" /var/www/html/.env
        fi
        
        if [ ! -z "$MAIL_FROM_NAME" ]; then
            sed -i "s/MAIL_FROM_NAME=.*/MAIL_FROM_NAME=$MAIL_FROM_NAME/" /var/www/html/.env
        fi
        
        if [ ! -z "$APP_TIMEZONE" ]; then
            sed -i "s/APP_TIMEZONE=.*/APP_TIMEZONE=$APP_TIMEZONE/" /var/www/html/.env
        fi
        
        if [ ! -z "$SANCTUM_STATEFUL_DOMAINS" ]; then
            sed -i "s/SANCTUM_STATEFUL_DOMAINS=.*/SANCTUM_STATEFUL_DOMAINS=$SANCTUM_STATEFUL_DOMAINS/" /var/www/html/.env
        fi
        
        if [ ! -z "$SESSION_DOMAIN" ]; then
            sed -i "s/SESSION_DOMAIN=.*/SESSION_DOMAIN=$SESSION_DOMAIN/" /var/www/html/.env
        fi
        
        # VPS specific settings
        if [ "$VPS_MODE" = "true" ]; then
            log "Applying VPS-specific settings..."
            # Set production settings
            sed -i "s/APP_ENV=.*/APP_ENV=production/" /var/www/html/.env
            sed -i "s/APP_DEBUG=.*/APP_DEBUG=false/" /var/www/html/.env
            sed -i "s/LOG_LEVEL=.*/LOG_LEVEL=error/" /var/www/html/.env
        fi
    fi
    
    # Clear config cache
    log "Clearing configuration cache..."
    php artisan config:clear || true
    
    # Run migrations
    log "Running database migrations..."
    php artisan migrate --force || error_exit "Migration failed"
    
    # Run seeders
    log "Seeding database..."
    php artisan db:seed --force || error_exit "Seeding failed"
    
    # Create storage symlink
    log "Creating storage symlink..."
    php artisan storage:link || true
    
    # Optimize application
    log "Optimizing application..."
    php artisan optimize || true
    
    # Mark as installed
    touch /var/www/html/storage/.installed
    log "Application setup completed!"
    
else
    log "Application already installed. Starting normally..."
fi

# Start Apache in background
log "Starting Apache web server..."
apache2-foreground &

# Wait for Apache to start
sleep 5

# Check if Apache is running
if ! pgrep apache2 > /dev/null; then
    error_exit "Apache failed to start"
fi

log "Apache started successfully"

# Start supervisor for background tasks
log "Starting supervisor..."
/usr/bin/supervisord -c /etc/supervisor/conf.d/supervisord.conf &

# Wait for supervisor to start
sleep 2

log "✅ Internet Management System is ready!"
log "🌐 Access the application at: $APP_URL"
log "👤 Default admin credentials:"
log "   Email: admin@example.com"
log "   Password: password"
log ""
log "⚠️  Please change the default password after first login!"

# Keep container running
wait