<?php

namespace App\Services;

use App\Models\MikrotikDevice;
use App\Models\MikrotikMonitoringLog;
use RouterOS\Query;
use Exception;
use Illuminate\Support\Facades\Log;

class MikrotikMonitoringService
{
    protected MikrotikApiService $api;

    public function __construct()
    {
        $this->api = new MikrotikApiService();
    }

    /**
     * Check and log device status
     *
     * @param MikrotikDevice $device
     * @return array
     */
    public function checkDeviceStatus(MikrotikDevice $device): array
    {
        try {
            $client = $this->api->getClient($device);
            
            // Get system resource
            $resourceQuery = new Query('/system/resource/print');
            $resource = $client->query($resourceQuery)->read();

            if (empty($resource)) {
                return $this->logOfflineStatus($device, 'No response from device');
            }

            $resourceData = $resource[0];

            // Get active PPP users count
            $activeUsersQuery = new Query('/ppp/active/print');
            $activeUsers = $client->query($activeUsersQuery)->read();
            $activeUsersCount = count($activeUsers);

            // Create monitoring log
            $log = MikrotikMonitoringLog::create([
                'mikrotik_device_id' => $device->id,
                'status' => 'online',
                'uptime' => $resourceData['uptime'] ?? null,
                'cpu_load' => $resourceData['cpu-load'] ?? null,
                'free_memory' => $resourceData['free-memory'] ?? null,
                'total_memory' => $resourceData['total-memory'] ?? null,
                'free_hdd' => $resourceData['free-hdd-space'] ?? null,
                'total_hdd' => $resourceData['total-hdd-space'] ?? null,
                'active_users' => $activeUsersCount,
                'version' => $resourceData['version'] ?? null,
                'board_name' => $resourceData['board-name'] ?? null,
                'checked_at' => now(),
            ]);

            return [
                'success' => true,
                'status' => 'online',
                'message' => 'Device is online',
                'data' => $log,
            ];

        } catch (Exception $e) {
            return $this->logOfflineStatus($device, $e->getMessage());
        }
    }

    /**
     * Log offline status
     *
     * @param MikrotikDevice $device
     * @param string $errorMessage
     * @return array
     */
    protected function logOfflineStatus(MikrotikDevice $device, string $errorMessage): array
    {
        $log = MikrotikMonitoringLog::create([
            'mikrotik_device_id' => $device->id,
            'status' => 'offline',
            'error_message' => $errorMessage,
            'checked_at' => now(),
        ]);

        return [
            'success' => false,
            'status' => 'offline',
            'message' => $errorMessage,
            'data' => $log,
        ];
    }

    /**
     * Get device statistics
     *
     * @param MikrotikDevice $device
     * @return array
     */
    public function getDeviceStatistics(MikrotikDevice $device): array
    {
        try {
            $client = $this->api->getClient($device);

            // System Resource
            $resourceQuery = new Query('/system/resource/print');
            $resource = $client->query($resourceQuery)->read();
            $resourceData = $resource[0] ?? [];

            // Active Users
            $activeUsersQuery = new Query('/ppp/active/print');
            $activeUsers = $client->query($activeUsersQuery)->read();

            // Total Secrets
            $secretsQuery = new Query('/ppp/secret/print');
            $secrets = $client->query($secretsQuery)->read();

            // Interface stats
            $interfaceQuery = new Query('/interface/print');
            $interfaces = $client->query($interfaceQuery)->read();

            return [
                'success' => true,
                'data' => [
                    'resource' => $resourceData,
                    'active_users' => count($activeUsers),
                    'total_secrets' => count($secrets),
                    'interfaces' => $interfaces,
                ],
            ];

        } catch (Exception $e) {
            $this->logError('getDeviceStatistics', $e, $device);
            return [
                'success' => false,
                'message' => $e->getMessage(),
            ];
        }
    }

    /**
     * Get monitoring history
     *
     * @param MikrotikDevice $device
     * @param int $days
     * @return array
     */
    public function getMonitoringHistory(MikrotikDevice $device, int $days = 7): array
    {
        $logs = MikrotikMonitoringLog::where('mikrotik_device_id', $device->id)
            ->where('checked_at', '>=', now()->subDays($days))
            ->orderBy('checked_at', 'desc')
            ->get();

        return [
            'success' => true,
            'data' => $logs,
        ];
    }

    /**
     * Clean old monitoring logs
     *
     * @param int $days
     * @return array
     */
    public function cleanOldLogs(int $days = 30): array
    {
        try {
            $deleted = MikrotikMonitoringLog::where('checked_at', '<', now()->subDays($days))
                ->delete();

            return [
                'success' => true,
                'message' => "Deleted {$deleted} old monitoring logs",
                'deleted' => $deleted,
            ];

        } catch (Exception $e) {
            $this->logError('cleanOldLogs', $e);
            return [
                'success' => false,
                'message' => $e->getMessage(),
            ];
        }
    }

    /**
     * Get device uptime
     *
     * @param MikrotikDevice $device
     * @return array
     */
    public function getDeviceUptime(MikrotikDevice $device): array
    {
        try {
            $client = $this->api->getClient($device);
            $query = new Query('/system/resource/print');
            $response = $client->query($query)->read();

            if (!empty($response)) {
                return [
                    'success' => true,
                    'uptime' => $response[0]['uptime'] ?? 'Unknown',
                ];
            }

            return [
                'success' => false,
                'message' => 'Unable to get uptime',
            ];

        } catch (Exception $e) {
            $this->logError('getDeviceUptime', $e, $device);
            return [
                'success' => false,
                'message' => $e->getMessage(),
            ];
        }
    }

    /**
     * Log error
     *
     * @param string $method
     * @param Exception $exception
     * @param MikrotikDevice|null $device
     * @return void
     */
    protected function logError(string $method, Exception $exception, ?MikrotikDevice $device = null): void
    {
        if (config('mikrotik.logging.enabled', true)) {
            $context = [
                'method' => $method,
                'message' => $exception->getMessage(),
            ];

            if ($device) {
                $context['device'] = [
                    'id' => $device->id,
                    'name' => $device->name,
                ];
            }

            Log::error("MikrotikMonitoringService::{$method}", $context);
        }
    }
}

