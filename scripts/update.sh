#!/bin/bash

# Update script for Internet Management
set -e

STATUS_FILE="/var/www/html/storage/app/update_status.json"
IMAGE_NAME="habis12/internet-management:latest"
CONTAINER_NAME="internet-app"

# Function to update status
update_status() {
    echo "{\"status\":\"$1\",\"message\":\"$2\",\"progress\":$3,\"timestamp\":\"$(date -Iseconds)\"}" > $STATUS_FILE
}

# Start update
update_status "starting" "Starting update process..." 0

# Pull latest image
update_status "pulling" "Pulling latest image..." 25
docker pull $IMAGE_NAME

# Stop current container
update_status "stopping" "Stopping current container..." 50
docker stop $CONTAINER_NAME || true

# Remove old container
update_status "removing" "Removing old container..." 60
docker rm $CONTAINER_NAME || true

# Start new container
update_status "starting_new" "Starting new container..." 80
docker run -d \
    --name $CONTAINER_NAME \
    --restart unless-stopped \
    -p 1217:1217 \
    -v $(pwd)/storage:/var/www/html/storage \
    -v $(pwd)/public:/var/www/html/public \
    -e APP_KEY="$(grep APP_KEY .env | cut -d '=' -f2)" \
    -e APP_URL="$(grep APP_URL .env | cut -d '=' -f2)" \
    -e DB_CONNECTION=mysql \
    -e DB_HOST=host.docker.internal \
    -e DB_PORT=3306 \
    -e DB_DATABASE=internet_management \
    -e DB_USERNAME=root \
    -e DB_PASSWORD=password \
    --add-host=host.docker.internal:host-gateway \
    $IMAGE_NAME

# Wait for container to be ready
update_status "waiting" "Waiting for container to be ready..." 90
sleep 30

# Run migrations if needed
update_status "migrating" "Running database migrations..." 95
docker exec $CONTAINER_NAME php artisan migrate --force || true

# Complete
update_status "completed" "Update completed successfully!" 100

# Clean up old images
docker image prune -f

echo "Update completed successfully!"
