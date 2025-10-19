<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Process;

class UpdateController extends Controller
{
    private $dockerHubImage = 'habis12/internet-management';
    private $currentVersion;

    public function __construct()
    {
        $this->currentVersion = config('app.version', '1.0.0');
    }

    /**
     * Check for available updates
     */
    public function checkUpdate()
    {
        try {
            $latestVersion = $this->getLatestVersionFromDockerHub();
            
            if (!$latestVersion) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unable to check for updates'
                ]);
            }

            $isUpdateAvailable = version_compare($latestVersion, $this->currentVersion, '>');
            
            return response()->json([
                'success' => true,
                'current_version' => $this->currentVersion,
                'latest_version' => $latestVersion,
                'update_available' => $isUpdateAvailable,
                'message' => $isUpdateAvailable ? 'Update available' : 'You are up to date'
            ]);

        } catch (\Exception $e) {
            Log::error('Update check failed: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to check for updates'
            ]);
        }
    }

    /**
     * Perform the update
     */
    public function performUpdate()
    {
        try {
            // Check if running in Docker
            if (!$this->isRunningInDocker()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Update only available in Docker environment'
                ]);
            }

            // Start update process in background
            $this->startUpdateProcess();

            return response()->json([
                'success' => true,
                'message' => 'Update process started. Please wait...'
            ]);

        } catch (\Exception $e) {
            Log::error('Update failed: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Update failed: ' . $e->getMessage()
            ]);
        }
    }

    /**
     * Get update status
     */
    public function getUpdateStatus()
    {
        $statusFile = storage_path('app/update_status.json');
        
        if (!file_exists($statusFile)) {
            return response()->json([
                'success' => true,
                'status' => 'idle',
                'message' => 'No update in progress'
            ]);
        }

        $status = json_decode(file_get_contents($statusFile), true);
        
        return response()->json([
            'success' => true,
            'status' => $status['status'] ?? 'idle',
            'message' => $status['message'] ?? 'No update in progress',
            'progress' => $status['progress'] ?? 0
        ]);
    }

    /**
     * Get latest version from Docker Hub
     */
    private function getLatestVersionFromDockerHub()
    {
        try {
            $response = Http::timeout(10)->get("https://hub.docker.com/v2/repositories/{$this->dockerHubImage}/tags/");
            
            if (!$response->successful()) {
                return null;
            }

            $data = $response->json();
            $tags = $data['results'] ?? [];

            // Find latest tag (excluding 'latest')
            foreach ($tags as $tag) {
                $tagName = $tag['name'];
                if ($tagName !== 'latest' && preg_match('/^\d+\.\d+\.\d+$/', $tagName)) {
                    return $tagName;
                }
            }

            // If no versioned tags found, check if 'latest' is newer
            foreach ($tags as $tag) {
                if ($tag['name'] === 'latest') {
                    return 'latest';
                }
            }

            return null;

        } catch (\Exception $e) {
            Log::error('Failed to fetch Docker Hub tags: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Check if running in Docker
     */
    private function isRunningInDocker()
    {
        return file_exists('/.dockerenv') || 
               (file_exists('/proc/1/cgroup') && 
                strpos(file_get_contents('/proc/1/cgroup'), 'docker') !== false);
    }

    /**
     * Start update process
     */
    private function startUpdateProcess()
    {
        $updateScript = base_path('scripts/update.sh');
        
        // Create update script if not exists
        if (!file_exists($updateScript)) {
            $this->createUpdateScript($updateScript);
        }

        // Start background process
        Process::start("bash {$updateScript}");
    }

    /**
     * Create update script
     */
    private function createUpdateScript($scriptPath)
    {
        $script = <<<'SCRIPT'
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
SCRIPT;

        file_put_contents($scriptPath, $script);
        chmod($scriptPath, 0755);
    }
}
