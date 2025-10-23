<?php

namespace App\Services;

use App\Models\MikrotikDevice;
use RouterOS\Client;
use RouterOS\Config;
use RouterOS\Query;
use Illuminate\Support\Facades\Log;
use Exception;

class MikrotikApiService
{
    protected int $timeout;
    protected int $attempts;
    protected int $delay;

    public function __construct()
    {
        $this->timeout = config('mikrotik.connection.timeout', 5);
        $this->attempts = config('mikrotik.connection.attempts', 3);
        $this->delay = config('mikrotik.connection.delay', 1);
    }

    /**
     * Get RouterOS Client connection
     *
     * @param MikrotikDevice $device
     * @return Client
     * @throws Exception
     */
    public function getClient(MikrotikDevice $device): Client
    {
        if (!$device) {
            throw new Exception('Mikrotik device not found.');
        }

        if (!$device->is_active) {
            throw new Exception('Mikrotik device is not active.');
        }

        $config = (new Config())
            ->set('timeout', $this->timeout)
            ->set('host', $device->ip_address)
            ->set('port', (int) $device->port)
            ->set('pass', $device->password)
            ->set('user', $device->username)
            ->set('ssl', (bool) $device->use_ssl);

        return new Client($config);
    }

    /**
     * Test connection to Mikrotik device
     *
     * @param MikrotikDevice $device
     * @return array
     */
    public function testConnection(MikrotikDevice $device): array
    {
        try {
            $client = $this->getClient($device);
            $query = new Query('/system/resource/print');
            $response = $client->query($query)->read();

            if (!empty($response)) {
                return [
                    'success' => true,
                    'message' => 'Connection successful',
                    'data' => $response[0] ?? [],
                ];
            }

            return [
                'success' => false,
                'message' => 'No response from device',
            ];
        } catch (Exception $e) {
            $this->logError('testConnection', $e, $device);
            return [
                'success' => false,
                'message' => $e->getMessage(),
            ];
        }
    }

    /**
     * Get system resource information
     *
     * @param MikrotikDevice $device
     * @return array
     */
    public function getSystemResource(MikrotikDevice $device): array
    {
        try {
            $client = $this->getClient($device);
            $query = new Query('/system/resource/print');
            $response = $client->query($query)->read();

            return [
                'success' => true,
                'data' => $response[0] ?? [],
            ];
        } catch (Exception $e) {
            $this->logError('getSystemResource', $e, $device);
            return [
                'success' => false,
                'message' => $e->getMessage(),
            ];
        }
    }

    /**
     * Get system identity
     *
     * @param MikrotikDevice $device
     * @return array
     */
    public function getSystemIdentity(MikrotikDevice $device): array
    {
        try {
            $client = $this->getClient($device);
            $query = new Query('/system/identity/print');
            $response = $client->query($query)->read();

            return [
                'success' => true,
                'data' => $response[0] ?? [],
            ];
        } catch (Exception $e) {
            $this->logError('getSystemIdentity', $e, $device);
            return [
                'success' => false,
                'message' => $e->getMessage(),
            ];
        }
    }

    /**
     * Execute custom query
     *
     * @param MikrotikDevice $device
     * @param string $endpoint
     * @param array $params
     * @return array
     */
    public function executeQuery(MikrotikDevice $device, string $endpoint, array $params = []): array
    {
        try {
            $client = $this->getClient($device);
            $query = new Query($endpoint);

            foreach ($params as $key => $value) {
                if ($key === 'where') {
                    foreach ($value as $field => $fieldValue) {
                        $query->where($field, $fieldValue);
                    }
                } elseif ($key === 'equal') {
                    foreach ($value as $field => $fieldValue) {
                        $query->equal($field, $fieldValue);
                    }
                }
            }

            $response = $client->query($query)->read();

            return [
                'success' => true,
                'data' => $response,
            ];
        } catch (Exception $e) {
            $this->logError('executeQuery', $e, $device);
            return [
                'success' => false,
                'message' => $e->getMessage(),
            ];
        }
    }

    /**
     * Get IP Pools from Mikrotik
     *
     * @param MikrotikDevice $device
     * @return array
     */
    public function getIpPools(MikrotikDevice $device): array
    {
        try {
            $client = $this->getClient($device);
            $query = new Query('/ip/pool/print');
            $response = $client->query($query)->read();

            $pools = [];
            foreach ($response as $pool) {
                $pools[] = [
                    'id' => $pool['.id'] ?? '',
                    'name' => $pool['name'] ?? '',
                    'ranges' => $pool['ranges'] ?? '',
                ];
            }

            return [
                'success' => true,
                'data' => $pools,
            ];
        } catch (Exception $e) {
            $this->logError('getIpPools', $e, $device);
            return [
                'success' => false,
                'message' => $e->getMessage(),
                'data' => [],
            ];
        }
    }

    /**
     * Get Queue Trees from Mikrotik
     *
     * @param MikrotikDevice $device
     * @return array
     */
    public function getQueueTrees(MikrotikDevice $device): array
    {
        try {
            $client = $this->getClient($device);
            $query = new Query('/queue/tree/print');
            $response = $client->query($query)->read();

            $queues = [];
            foreach ($response as $queue) {
                $queues[] = [
                    'id' => $queue['.id'] ?? '',
                    'name' => $queue['name'] ?? '',
                    'parent' => $queue['parent'] ?? 'global',
                ];
            }

            return [
                'success' => true,
                'data' => $queues,
            ];
        } catch (Exception $e) {
            $this->logError('getQueueTrees', $e, $device);
            return [
                'success' => false,
                'message' => $e->getMessage(),
                'data' => [],
            ];
        }
    }

    /**
     * Get Queue Simple from Mikrotik
     *
     * @param MikrotikDevice $device
     * @return array
     */
    public function getQueueSimple(MikrotikDevice $device): array
    {
        try {
            $client = $this->getClient($device);
            $query = new Query('/queue/simple/print');
            $response = $client->query($query)->read();

            $queues = [];
            foreach ($response as $queue) {
                $queues[] = [
                    'id' => $queue['.id'] ?? '',
                    'name' => $queue['name'] ?? '',
                    'target' => $queue['target'] ?? '',
                ];
            }

            return [
                'success' => true,
                'data' => $queues,
            ];
        } catch (Exception $e) {
            $this->logError('getQueueSimple', $e, $device);
            return [
                'success' => false,
                'message' => $e->getMessage(),
                'data' => [],
            ];
        }
    }

    /**
     * Get IP Bindings from Mikrotik Hotspot
     *
     * @param MikrotikDevice $device
     * @return array
     */
    public function getIpBindings(MikrotikDevice $device): array
    {
        try {
            $client = $this->getClient($device);
            $query = new Query('/ip/hotspot/ip-binding/print');
            $response = $client->query($query)->read();

            $bindings = [];
            foreach ($response as $binding) {
                $bindings[] = [
                    'id' => $binding['.id'] ?? '',
                    'mac_address' => $binding['mac-address'] ?? null,
                    'address' => $binding['address'] ?? null,
                    'to_address' => $binding['to-address'] ?? null,
                    'server' => $binding['server'] ?? 'all',
                    'type' => $binding['type'] ?? 'regular',
                    'comment' => $binding['comment'] ?? null,
                    'disabled' => ($binding['disabled'] ?? 'false') === 'true',
                ];
            }

            return [
                'success' => true,
                'data' => $bindings,
            ];
        } catch (Exception $e) {
            $this->logError('getIpBindings', $e, $device);
            return [
                'success' => false,
                'message' => $e->getMessage(),
                'data' => [],
            ];
        }
    }

    /**
     * Get single IP Binding from Mikrotik
     *
     * @param MikrotikDevice $device
     * @param string $bindingId
     * @return array
     */
    public function getIpBinding(MikrotikDevice $device, string $bindingId): array
    {
        try {
            $client = $this->getClient($device);
            $query = (new Query('/ip/hotspot/ip-binding/print'))
                ->where('.id', $bindingId);
            $response = $client->query($query)->read();

            if (!empty($response)) {
                return [
                    'success' => true,
                    'data' => $response[0],
                ];
            }

            return [
                'success' => false,
                'message' => 'IP Binding not found',
            ];
        } catch (Exception $e) {
            $this->logError('getIpBinding', $e, $device);
            return [
                'success' => false,
                'message' => $e->getMessage(),
            ];
        }
    }

    /**
     * Get Netwatch entries from Mikrotik
     *
     * @param MikrotikDevice $device
     * @return array
     */
    public function getNetwatch(MikrotikDevice $device): array
    {
        try {
            $client = $this->getClient($device);
            $query = new Query('/tool/netwatch/print');
            $response = $client->query($query)->read();

            $netwatch = [];
            foreach ($response as $entry) {
                $netwatch[] = [
                    'id' => $entry['.id'] ?? '',
                    'host' => $entry['host'] ?? null,
                    'interval' => $entry['interval'] ?? '00:01:00',
                    'timeout' => $entry['timeout'] ?? '1000ms',
                    'status' => $entry['status'] ?? 'unknown',
                    'since' => $entry['since'] ?? null,
                    'up_script' => $entry['up-script'] ?? null,
                    'down_script' => $entry['down-script'] ?? null,
                    'comment' => $entry['comment'] ?? null,
                    'disabled' => ($entry['disabled'] ?? 'false') === 'true',
                ];
            }

            return [
                'success' => true,
                'data' => $netwatch,
            ];
        } catch (Exception $e) {
            $this->logError('getNetwatch', $e, $device);
            return [
                'success' => false,
                'message' => $e->getMessage(),
                'data' => [],
            ];
        }
    }

    /**
     * Get single Netwatch entry from Mikrotik
     *
     * @param MikrotikDevice $device
     * @param string $netwatchId
     * @return array
     */
    public function getNetwatchEntry(MikrotikDevice $device, string $netwatchId): array
    {
        try {
            $client = $this->getClient($device);
            $query = (new Query('/tool/netwatch/print'))
                ->where('.id', $netwatchId);
            $response = $client->query($query)->read();

            if (!empty($response)) {
                return [
                    'success' => true,
                    'data' => $response[0],
                ];
            }

            return [
                'success' => false,
                'message' => 'Netwatch entry not found',
            ];
        } catch (Exception $e) {
            $this->logError('getNetwatchEntry', $e, $device);
            return [
                'success' => false,
                'message' => $e->getMessage(),
            ];
        }
    }

    /**
     * Log error to file
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
                'file' => $exception->getFile(),
                'line' => $exception->getLine(),
            ];

            if ($device) {
                $context['device'] = [
                    'id' => $device->id,
                    'name' => $device->name,
                    'ip' => $device->ip_address,
                ];
            }

            Log::channel(config('mikrotik.logging.channel', 'daily'))
                ->error("MikrotikApiService::{$method}", $context);
        }
    }
}

