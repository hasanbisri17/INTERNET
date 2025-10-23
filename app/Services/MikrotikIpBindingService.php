<?php

namespace App\Services;

use App\Models\MikrotikDevice;
use App\Models\MikrotikIpBinding;
use RouterOS\Query;
use Exception;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class MikrotikIpBindingService
{
    protected MikrotikApiService $api;

    public function __construct()
    {
        $this->api = new MikrotikApiService();
    }

    /**
     * Update IP Binding type in Mikrotik
     *
     * @param MikrotikDevice $device
     * @param MikrotikIpBinding $binding
     * @param string $newType
     * @return array
     */
    public function updateBindingType(MikrotikDevice $device, MikrotikIpBinding $binding, string $newType): array
    {
        try {
            if (!$binding->binding_id) {
                return [
                    'success' => false,
                    'message' => 'Binding ID not found. Please sync first.',
                ];
            }

            $client = $this->api->getClient($device);

            $query = (new Query('/ip/hotspot/ip-binding/set'))
                ->equal('.id', $binding->binding_id)
                ->equal('type', $newType);

            $response = $client->query($query)->read();

            if ($response === []) {
                // Use withoutEvents to prevent observer loop
                $binding->withoutEvents(function () use ($binding, $newType) {
                    $binding->update([
                        'type' => $newType,
                        'is_synced' => true,
                        'last_synced_at' => now(),
                    ]);
                });

                $this->clearCache($device);

                return [
                    'success' => true,
                    'message' => "Type berhasil diubah ke {$newType}",
                ];
            }

            // Get detailed error message
            $errorMessage = 'Update failed';
            if (isset($response['!trap'])) {
                $errorMessage = $response['!trap'][0]['message'] ?? 'Unknown error from MikroTik';
            } elseif (isset($response['after']['message'])) {
                $errorMessage = $response['after']['message'];
            }

            return [
                'success' => false,
                'message' => $errorMessage,
            ];

        } catch (Exception $e) {
            $this->logError('updateBindingType', $e, $device);
            return [
                'success' => false,
                'message' => 'Error: ' . $e->getMessage(),
            ];
        }
    }

    /**
     * Enable/Disable IP Binding in Mikrotik
     *
     * @param MikrotikDevice $device
     * @param MikrotikIpBinding $binding
     * @param bool $disabled
     * @return array
     */
    public function toggleBinding(MikrotikDevice $device, MikrotikIpBinding $binding, bool $disabled): array
    {
        try {
            if (!$binding->binding_id) {
                return [
                    'success' => false,
                    'message' => 'Binding ID not found. Please sync first.',
                ];
            }

            $client = $this->api->getClient($device);

            $endpoint = $disabled ? '/ip/hotspot/ip-binding/disable' : '/ip/hotspot/ip-binding/enable';
            $query = (new Query($endpoint))
                ->equal('.id', $binding->binding_id);

            $response = $client->query($query)->read();

            if ($response === []) {
                // Use withoutEvents to prevent observer loop
                $binding->withoutEvents(function () use ($binding, $disabled) {
                    $binding->update([
                        'is_disabled' => $disabled,
                        'is_synced' => true,
                        'last_synced_at' => now(),
                    ]);
                });

                $this->clearCache($device);

                $status = $disabled ? 'dinonaktifkan' : 'diaktifkan';
                return [
                    'success' => true,
                    'message' => "IP Binding berhasil {$status}",
                ];
            }

            return [
                'success' => false,
                'message' => $response['after']['message'] ?? 'Toggle failed',
            ];

        } catch (Exception $e) {
            $this->logError('toggleBinding', $e, $device);
            return [
                'success' => false,
                'message' => 'Error: ' . $e->getMessage(),
            ];
        }
    }

    /**
     * Sync all IP Bindings from Mikrotik to database
     *
     * @param MikrotikDevice $device
     * @return array
     */
    public function syncAllBindings(MikrotikDevice $device): array
    {
        try {
            $result = $this->api->getIpBindings($device);

            if (!$result['success']) {
                return $result;
            }

            $synced = 0;
            $updated = 0;
            $created = 0;

            foreach ($result['data'] as $bindingData) {
                // Use withoutEvents to prevent observer from triggering during sync
                // This prevents infinite loop
                $binding = MikrotikIpBinding::withoutEvents(function () use ($device, $bindingData) {
                    return MikrotikIpBinding::updateOrCreate(
                        [
                            'mikrotik_device_id' => $device->id,
                            'binding_id' => $bindingData['id'],
                        ],
                        [
                            'mac_address' => $bindingData['mac_address'],
                            'address' => $bindingData['address'],
                            'to_address' => $bindingData['to_address'],
                            'server' => $bindingData['server'],
                            'type' => $bindingData['type'],
                            'comment' => $bindingData['comment'],
                            'is_disabled' => $bindingData['disabled'],
                            'is_synced' => true,
                            'last_synced_at' => now(),
                        ]
                    );
                });

                if ($binding->wasRecentlyCreated) {
                    $created++;
                } else {
                    $updated++;
                }
                $synced++;
            }

            $this->clearCache($device);

            return [
                'success' => true,
                'message' => "Berhasil sync {$synced} IP Bindings ({$created} baru, {$updated} diperbarui)",
                'synced' => $synced,
                'created' => $created,
                'updated' => $updated,
            ];

        } catch (Exception $e) {
            $this->logError('syncAllBindings', $e, $device);
            return [
                'success' => false,
                'message' => $e->getMessage(),
            ];
        }
    }

    /**
     * Create IP Binding in Mikrotik
     *
     * @param MikrotikDevice $device
     * @param MikrotikIpBinding $binding
     * @return array
     */
    public function createBinding(MikrotikDevice $device, MikrotikIpBinding $binding): array
    {
        try {
            $client = $this->api->getClient($device);

            $query = new Query('/ip/hotspot/ip-binding/add');

            if ($binding->mac_address) {
                $query->equal('mac-address', $binding->mac_address);
            }

            if ($binding->address) {
                $query->equal('address', $binding->address);
            }

            if ($binding->to_address) {
                $query->equal('to-address', $binding->to_address);
            }

            if ($binding->server) {
                $query->equal('server', $binding->server);
            }

            if ($binding->type) {
                $query->equal('type', $binding->type);
            }

            if ($binding->comment) {
                $query->equal('comment', $binding->comment);
            }

            $response = $client->query($query)->read();

            if (isset($response['after']['ret'])) {
                // Use withoutEvents to prevent observer loop
                $binding->withoutEvents(function () use ($binding, $response) {
                    $binding->update([
                        'binding_id' => $response['after']['ret'],
                        'is_synced' => true,
                        'last_synced_at' => now(),
                    ]);
                });

                $this->clearCache($device);

                return [
                    'success' => true,
                    'message' => 'IP Binding created successfully',
                    'binding_id' => $response['after']['ret'],
                ];
            }

            // Get detailed error message
            $errorMessage = 'Failed to create IP Binding';
            if (isset($response['!trap'])) {
                $errorMessage = $response['!trap'][0]['message'] ?? 'Unknown error from MikroTik';
            } elseif (isset($response['after']['message'])) {
                $errorMessage = $response['after']['message'];
            }

            return [
                'success' => false,
                'message' => $errorMessage,
            ];

        } catch (Exception $e) {
            $this->logError('createBinding', $e, $device);
            return [
                'success' => false,
                'message' => 'Error: ' . $e->getMessage(),
            ];
        }
    }

    /**
     * Delete IP Binding from Mikrotik
     *
     * @param MikrotikDevice $device
     * @param MikrotikIpBinding $binding
     * @return array
     */
    public function deleteBinding(MikrotikDevice $device, MikrotikIpBinding $binding): array
    {
        try {
            if (!$binding->binding_id) {
                return [
                    'success' => false,
                    'message' => 'Binding ID not found',
                ];
            }

            $client = $this->api->getClient($device);

            $query = (new Query('/ip/hotspot/ip-binding/remove'))
                ->equal('.id', $binding->binding_id);

            $response = $client->query($query)->read();

            $this->clearCache($device);

            if ($response === []) {
                return [
                    'success' => true,
                    'message' => 'IP Binding deleted successfully',
                ];
            }

            return [
                'success' => false,
                'message' => $response['after']['message'] ?? 'Delete failed',
            ];

        } catch (Exception $e) {
            $this->logError('deleteBinding', $e, $device);
            return [
                'success' => false,
                'message' => $e->getMessage(),
            ];
        }
    }

    /**
     * Clear cache for device
     *
     * @param MikrotikDevice $device
     * @return void
     */
    protected function clearCache(MikrotikDevice $device): void
    {
        Cache::forget("mikrotik_ip_bindings_{$device->id}");
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

            Log::error("MikrotikIpBindingService::{$method}", $context);
        }
    }
}

