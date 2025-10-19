<?php

namespace App\Services;

use App\Models\MikrotikDevice;
use Exception;
use Illuminate\Support\Facades\Log;
use RouterOS\Client;
use RouterOS\Config;
use RouterOS\Query;

class MikrotikService
{
    protected ?Client $client = null;
    protected ?MikrotikDevice $device = null;

    /**
     * Connect to a MikroTik device
     *
     * @param MikrotikDevice|int $device MikrotikDevice model or device ID
     * @return bool Connection success
     */
    public function connect($device): bool
    {
        try {
            if (is_numeric($device)) {
                $device = MikrotikDevice::findOrFail($device);
            }

            $this->device = $device;

            $config = (new Config())
                ->set('host', $device->ip_address)
                ->set('port', $device->port)
                ->set('user', $device->username)
                ->set('pass', $device->password)
                ->set('ssl', $device->use_ssl)
                ->set('timeout', 5) // Timeout dalam detik
                ->set('attempts', 2); // Jumlah percobaan koneksi

            $this->client = new Client($config);
            
            // Test connection with a simple query
            $this->client->query('/system/identity/print')->read();
            
            return true;
        } catch (Exception $e) {
            Log::error('MikroTik connection error: ' . $e->getMessage(), [
                'device_id' => $device->id ?? null,
                'ip_address' => $device->ip_address ?? null,
            ]);
            
            $this->client = null;
            return false;
        }
    }

    /**
     * Disconnect from the MikroTik device
     */
    public function disconnect(): void
    {
        $this->client = null;
        $this->device = null;
    }

    /**
     * Execute a command on the MikroTik device
     *
     * @param string $command Command path (e.g. '/ip/address/print')
     * @param array $params Command parameters
     * @return array Response data
     * @throws Exception If not connected or command fails
     */
    public function execute(string $command, array $params = []): array
    {
        if (!$this->client) {
            throw new Exception('Not connected to MikroTik device');
        }

        try {
            $query = new Query($command);
            
            foreach ($params as $key => $value) {
                if (is_numeric($key)) {
                    $query->add($value);
                } else {
                    $query->where($key, $value);
                }
            }
            
            return $this->client->query($query)->read();
        } catch (Exception $e) {
            Log::error('MikroTik command error: ' . $e->getMessage(), [
                'device_id' => $this->device->id ?? null,
                'command' => $command,
                'params' => $params,
            ]);
            
            throw $e;
        }
    }

    /**
     * Get system information
     *
     * @return array System information
     */
    public function getSystemInfo(): array
    {
        return $this->execute('/system/resource/print');
    }

    /**
     * Get all interfaces
     *
     * @return array Interfaces list
     */
    public function getInterfaces(): array
    {
        return $this->execute('/interface/print');
    }

    /**
     * Get a specific interface by name
     *
     * @param string $interfaceName Name of the interface
     * @return array|null Interface data or null if not found
     */
    public function getInterface(string $interfaceName): ?array
    {
        try {
            if (!$this->client) {
                return null;
            }

            $query = new Query('/interface/print');
            $query->where('name', $interfaceName);
            $response = $this->client->query($query)->read();
            
            return $response[0] ?? null;
        } catch (Exception $e) {
            Log::error('Error getting interface: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Get all IP addresses
     *
     * @return array IP addresses list
     */
    public function getIpAddresses(): array
    {
        return $this->execute('/ip/address/print');
    }

    /**
     * Get all DHCP leases
     *
     * @return array DHCP leases list
     */
    public function getDhcpLeases(): array
    {
        return $this->execute('/ip/dhcp-server/lease/print');
    }

    /**
     * Get all hotspot users
     *
     * @return array Hotspot users list
     */
    public function getHotspotUsers(): array
    {
        return $this->execute('/ip/hotspot/user/print');
    }

    /**
     * Add a hotspot user
     *
     * @param string $name Username
     * @param string $password Password
     * @param array $options Additional options
     * @return array Response data
     */
    public function addHotspotUser(string $name, string $password, array $options = []): array
    {
        $params = array_merge([
            'name' => $name,
            'password' => $password,
        ], $options);
        
        return $this->execute('/ip/hotspot/user/add', $params);
    }

    /**
     * Remove a hotspot user
     *
     * @param string $id User ID
     * @return array Response data
     */
    public function removeHotspotUser(string $id): array
    {
        return $this->execute('/ip/hotspot/user/remove', [
            '.id' => $id,
        ]);
    }

}