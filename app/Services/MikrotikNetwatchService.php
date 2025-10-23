<?php

namespace App\Services;

use App\Models\MikrotikDevice;
use App\Models\MikrotikNetwatch;
use Exception;
use Illuminate\Support\Facades\Log;
use RouterOS\Client;
use RouterOS\Query;

class MikrotikNetwatchService
{
    protected MikrotikApiService $apiService;

    public function __construct()
    {
        $this->apiService = new MikrotikApiService();
    }

    /**
     * Sync all netwatch entries from MikroTik
     *
     * @param MikrotikDevice|null $device
     * @return array
     */
    public function syncAllNetwatch(?MikrotikDevice $device = null): array
    {
        try {
            $devices = $device ? [$device] : MikrotikDevice::where('is_active', true)->get();
            $synced = 0;
            $errors = [];

            foreach ($devices as $dev) {
                $result = $this->apiService->getNetwatch($dev);

                if (!$result['success']) {
                    $errors[] = "Device {$dev->name}: {$result['message']}";
                    continue;
                }

                foreach ($result['data'] as $netwatchData) {
                    try {
                        // Parse 'since' timestamp from MikroTik format
                        $sinceTimestamp = null;
                        if (!empty($netwatchData['since'])) {
                            try {
                                // MikroTik format: "oct/22/2025 16:38:00"
                                // Convert to standard format
                                $sinceTimestamp = \Carbon\Carbon::parse($netwatchData['since']);
                            } catch (\Exception $e) {
                                // If parsing fails, just set to null
                                $sinceTimestamp = null;
                            }
                        }

                        // Use withoutEvents to prevent observer from triggering during sync
                        // This prevents infinite loop
                        $netwatch = MikrotikNetwatch::withoutEvents(function () use ($dev, $netwatchData, $sinceTimestamp) {
                            return MikrotikNetwatch::updateOrCreate(
                                [
                                    'mikrotik_device_id' => $dev->id,
                                    'netwatch_id' => $netwatchData['id'],
                                ],
                                [
                                    'host' => $netwatchData['host'],
                                    'interval' => $netwatchData['interval'],
                                    'timeout' => $netwatchData['timeout'],
                                    'status' => $netwatchData['status'],
                                    'since' => $sinceTimestamp,
                                    'up_script' => $netwatchData['up_script'],
                                    'down_script' => $netwatchData['down_script'],
                                    'comment' => $netwatchData['comment'],
                                    'is_disabled' => $netwatchData['disabled'],
                                    'is_synced' => true,
                                    'last_synced_at' => now(),
                                ]
                            );
                        });

                        $synced++;
                    } catch (Exception $e) {
                        $errors[] = "Host {$netwatchData['host']}: {$e->getMessage()}";
                        Log::error('Error syncing netwatch', [
                            'device' => $dev->name,
                            'host' => $netwatchData['host'],
                            'error' => $e->getMessage(),
                        ]);
                    }
                }
            }

            return [
                'success' => true,
                'synced' => $synced,
                'errors' => $errors,
                'message' => "Berhasil sync {$synced} netwatch entries" . (count($errors) > 0 ? " dengan " . count($errors) . " error" : ""),
            ];
        } catch (Exception $e) {
            Log::error('Error in syncAllNetwatch', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return [
                'success' => false,
                'message' => $e->getMessage(),
            ];
        }
    }

    /**
     * Create netwatch entry in MikroTik
     *
     * @param MikrotikDevice $device
     * @param MikrotikNetwatch $netwatch
     * @return array
     */
    public function createNetwatch(MikrotikDevice $device, MikrotikNetwatch $netwatch): array
    {
        try {
            $client = $this->apiService->getClient($device);
            
            $query = new Query('/tool/netwatch/add');
            $query->equal('host', trim($netwatch->host));
            $query->equal('interval', trim($netwatch->interval));
            $query->equal('timeout', trim($netwatch->timeout));
            
            if ($netwatch->up_script) {
                $query->equal('up-script', trim($netwatch->up_script));
            }
            
            if ($netwatch->down_script) {
                $query->equal('down-script', trim($netwatch->down_script));
            }
            
            if ($netwatch->comment) {
                $query->equal('comment', trim($netwatch->comment));
            }
            
            $query->equal('disabled', $netwatch->is_disabled ? 'yes' : 'no');
            
            $response = $client->query($query)->read();

            // Check for errors in response
            if (isset($response['!trap'])) {
                $errorMessage = $response['!trap'][0]['message'] ?? 'Unknown error from MikroTik';
                throw new Exception($errorMessage);
            }

            if (isset($response['after']['message'])) {
                throw new Exception($response['after']['message']);
            }

            if (isset($response['after']['ret'])) {
                // Use withoutEvents to prevent observer loop
                $netwatch->withoutEvents(function () use ($netwatch, $response) {
                    $netwatch->update([
                        'netwatch_id' => $response['after']['ret'],
                        'is_synced' => true,
                        'last_synced_at' => now(),
                    ]);
                });

                return [
                    'success' => true,
                    'message' => 'Netwatch berhasil dibuat di MikroTik',
                ];
            }

            return [
                'success' => false,
                'message' => 'Gagal membuat netwatch di MikroTik',
            ];
        } catch (Exception $e) {
            Log::error('Error creating netwatch in MikroTik', [
                'device' => $device->name,
                'host' => $netwatch->host,
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'message' => $e->getMessage(),
            ];
        }
    }

    /**
     * Update netwatch entry in MikroTik
     *
     * @param MikrotikDevice $device
     * @param MikrotikNetwatch $netwatch
     * @return array
     */
    public function updateNetwatch(MikrotikDevice $device, MikrotikNetwatch $netwatch): array
    {
        try {
            if (!$netwatch->netwatch_id) {
                throw new Exception('Netwatch ID tidak ditemukan. Silakan sync terlebih dahulu.');
            }

            $client = $this->apiService->getClient($device);
            
            $query = new Query('/tool/netwatch/set');
            $query->equal('.id', $netwatch->netwatch_id);
            $query->equal('host', trim($netwatch->host));
            $query->equal('interval', trim($netwatch->interval));
            $query->equal('timeout', trim($netwatch->timeout));
            
            if ($netwatch->up_script) {
                $query->equal('up-script', trim($netwatch->up_script));
            } else {
                $query->equal('up-script', '');
            }
            
            if ($netwatch->down_script) {
                $query->equal('down-script', trim($netwatch->down_script));
            } else {
                $query->equal('down-script', '');
            }
            
            if ($netwatch->comment) {
                $query->equal('comment', trim($netwatch->comment));
            } else {
                $query->equal('comment', '');
            }
            
            $query->equal('disabled', $netwatch->is_disabled ? 'yes' : 'no');
            
            $response = $client->query($query)->read();

            // Check for errors
            if (isset($response['!trap'])) {
                $errorMessage = $response['!trap'][0]['message'] ?? 'Unknown error from MikroTik';
                throw new Exception($errorMessage);
            }

            if (isset($response['after']['message'])) {
                throw new Exception($response['after']['message']);
            }

            // If response is empty array, it means success
            if ($response === []) {
                // Use withoutEvents to prevent observer loop
                $netwatch->withoutEvents(function () use ($netwatch) {
                    $netwatch->update([
                        'is_synced' => true,
                        'last_synced_at' => now(),
                    ]);
                });

                return [
                    'success' => true,
                    'message' => 'Netwatch berhasil diupdate di MikroTik',
                ];
            }

            return [
                'success' => false,
                'message' => 'Gagal mengupdate netwatch di MikroTik',
            ];
        } catch (Exception $e) {
            Log::error('Error updating netwatch in MikroTik', [
                'device' => $device->name,
                'host' => $netwatch->host,
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'message' => $e->getMessage(),
            ];
        }
    }

    /**
     * Delete netwatch entry from MikroTik
     *
     * @param MikrotikDevice $device
     * @param MikrotikNetwatch $netwatch
     * @return array
     */
    public function deleteNetwatch(MikrotikDevice $device, MikrotikNetwatch $netwatch): array
    {
        try {
            if (!$netwatch->netwatch_id) {
                // If no netwatch_id, just delete from database
                return [
                    'success' => true,
                    'message' => 'Netwatch dihapus dari database (tidak ada di MikroTik)',
                ];
            }

            $client = $this->apiService->getClient($device);
            
            $query = new Query('/tool/netwatch/remove');
            $query->equal('.id', $netwatch->netwatch_id);
            
            $response = $client->query($query)->read();

            // Check for errors
            if (isset($response['!trap'])) {
                $errorMessage = $response['!trap'][0]['message'] ?? 'Unknown error from MikroTik';
                throw new Exception($errorMessage);
            }

            if (isset($response['after']['message'])) {
                throw new Exception($response['after']['message']);
            }

            return [
                'success' => true,
                'message' => 'Netwatch berhasil dihapus dari MikroTik',
            ];
        } catch (Exception $e) {
            Log::error('Error deleting netwatch from MikroTik', [
                'device' => $device->name,
                'host' => $netwatch->host,
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'message' => $e->getMessage(),
            ];
        }
    }

    /**
     * Toggle netwatch enabled/disabled status
     *
     * @param MikrotikDevice $device
     * @param MikrotikNetwatch $netwatch
     * @param bool $disabled
     * @return array
     */
    public function toggleNetwatch(MikrotikDevice $device, MikrotikNetwatch $netwatch, bool $disabled): array
    {
        try {
            if (!$netwatch->netwatch_id) {
                throw new Exception('Netwatch ID tidak ditemukan. Silakan sync terlebih dahulu.');
            }

            $client = $this->apiService->getClient($device);
            
            $query = new Query('/tool/netwatch/set');
            $query->equal('.id', $netwatch->netwatch_id);
            $query->equal('disabled', $disabled ? 'yes' : 'no');
            
            $response = $client->query($query)->read();

            // If response is empty array, it means success
            if ($response === []) {
                // Use withoutEvents to prevent observer loop
                $netwatch->withoutEvents(function () use ($netwatch, $disabled) {
                    $netwatch->update([
                        'is_disabled' => $disabled,
                        'is_synced' => true,
                        'last_synced_at' => now(),
                    ]);
                });

                $status = $disabled ? 'disabled' : 'enabled';
                return [
                    'success' => true,
                    'message' => "Netwatch berhasil {$status}",
                ];
            }

            return [
                'success' => false,
                'message' => 'Gagal mengubah status netwatch',
            ];
        } catch (Exception $e) {
            Log::error('Error toggling netwatch', [
                'device' => $device->name,
                'host' => $netwatch->host,
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'message' => $e->getMessage(),
            ];
        }
    }
}

