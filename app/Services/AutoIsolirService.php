<?php

namespace App\Services;

use App\Models\Customer;
use App\Models\MikrotikDevice;
use App\Models\AutoIsolirConfig;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use Exception;

class AutoIsolirService
{
    public function __construct()
    {
        // Auto isolir now works via IP Binding suspension
        // See SuspendViaIpBindingService for implementation
    }

    /**
     * Process auto isolir for all devices
     *
     * @return array
     */
    public function processAllDevices(): array
    {
        $devices = MikrotikDevice::where('is_active', true)
            ->whereHas('autoIsolirConfig', function ($query) {
                $query->where('enabled', true);
            })
            ->get();

        $results = [];

        foreach ($devices as $device) {
            $results[$device->id] = $this->processDevice($device);
        }

        return [
            'success' => true,
            'message' => 'Auto isolir processed for all devices',
            'results' => $results,
        ];
    }

    /**
     * Process auto isolir for specific device
     *
     * @param MikrotikDevice $device
     * @return array
     */
    public function processDevice(MikrotikDevice $device): array
    {
        $config = $device->autoIsolirConfig;

        if (!$config || !$config->enabled) {
            return [
                'success' => false,
                'message' => 'Auto isolir not enabled for this device',
            ];
        }

        $isolated = 0;
        $restored = 0;
        $errors = [];

        // Get expired customers
        $expiredCustomers = $this->getExpiredCustomers($device, $config);

        foreach ($expiredCustomers as $customer) {
            try {
                $result = $this->isolateCustomer($device, $customer, $config);
                if ($result['success']) {
                    $isolated++;
                } else {
                    $errors[] = "Customer {$customer->id}: {$result['message']}";
                }
            } catch (Exception $e) {
                $errors[] = "Customer {$customer->id}: {$e->getMessage()}";
            }
        }

        // Auto restore paid customers
        if ($config->auto_restore) {
            $paidCustomers = $this->getPaidCustomers($device);

            foreach ($paidCustomers as $customer) {
                try {
                    $result = $this->restoreCustomer($device, $customer);
                    if ($result['success']) {
                        $restored++;
                    } else {
                        $errors[] = "Customer {$customer->id}: {$result['message']}";
                    }
                } catch (Exception $e) {
                    $errors[] = "Customer {$customer->id}: {$e->getMessage()}";
                }
            }
        }

        return [
            'success' => true,
            'isolated' => $isolated,
            'restored' => $restored,
            'errors' => $errors,
        ];
    }

    /**
     * Get expired customers for device
     *
     * @param MikrotikDevice $device
     * @param AutoIsolirConfig $config
     * @return Collection
     */
    protected function getExpiredCustomers(MikrotikDevice $device, AutoIsolirConfig $config): Collection
    {
        $graceDate = Carbon::now()->subDays($config->grace_period_days);

        return Customer::where('mikrotik_device_id', $device->id)
            ->where('status', 'active')
            ->where('is_isolated', false)
            ->whereNotNull('due_date')
            ->where('due_date', '<', $graceDate)
            ->get();
    }

    /**
     * Get paid customers that should be restored
     *
     * @param MikrotikDevice $device
     * @return Collection
     */
    protected function getPaidCustomers(MikrotikDevice $device): Collection
    {
        return Customer::where('mikrotik_device_id', $device->id)
            ->where('status', 'active')
            ->where('is_isolated', true)
            ->where(function ($query) {
                $query->whereNull('due_date')
                    ->orWhere('due_date', '>=', Carbon::now());
            })
            ->get();
    }

    /**
     * Isolate customer
     *
     * @param MikrotikDevice $device
     * @param Customer $customer
     * @param AutoIsolirConfig $config
     * @return array
     */
    public function isolateCustomer(MikrotikDevice $device, Customer $customer, AutoIsolirConfig $config): array
    {
        try {
            // Update customer status
            // Note: Actual network suspension should be done via SuspendViaIpBindingService
            $customer->update([
                'is_isolated' => true,
                'isolated_at' => now(),
                'status' => 'suspended',
            ]);

            // Send notification if enabled
            if ($config->send_notification) {
                // TODO: Implement notification logic
                // You can integrate with your WhatsApp service here
            }

            $this->logActivity('isolate', $customer, $device);

            return [
                'success' => true,
                'message' => 'Customer isolated successfully',
            ];

        } catch (Exception $e) {
            $this->logError('isolateCustomer', $e, $customer);
            return [
                'success' => false,
                'message' => $e->getMessage(),
            ];
        }
    }

    /**
     * Restore customer
     *
     * @param MikrotikDevice $device
     * @param Customer $customer
     * @return array
     */
    public function restoreCustomer(MikrotikDevice $device, Customer $customer): array
    {
        try {
            // Update customer status
            // Note: Actual network restoration should be done via SuspendViaIpBindingService
            $customer->update([
                'is_isolated' => false,
                'isolated_at' => null,
                'status' => 'active',
            ]);

            $this->logActivity('restore', $customer, $device);

            return [
                'success' => true,
                'message' => 'Customer restored successfully',
            ];

        } catch (Exception $e) {
            $this->logError('restoreCustomer', $e, $customer);
            return [
                'success' => false,
                'message' => $e->getMessage(),
            ];
        }
    }

    /**
     * Get customers needing warning notification
     *
     * @param MikrotikDevice $device
     * @param AutoIsolirConfig $config
     * @return Collection
     */
    public function getCustomersNeedingWarning(MikrotikDevice $device, AutoIsolirConfig $config): Collection
    {
        $warningDate = Carbon::now()->addDays($config->warning_days);

        return Customer::where('mikrotik_device_id', $device->id)
            ->where('status', 'active')
            ->where('is_isolated', false)
            ->whereNotNull('due_date')
            ->whereDate('due_date', '=', $warningDate->toDateString())
            ->get();
    }

    /**
     * Log activity
     *
     * @param string $action
     * @param Customer $customer
     * @param MikrotikDevice $device
     * @return void
     */
    protected function logActivity(string $action, Customer $customer, MikrotikDevice $device): void
    {
        Log::channel(config('mikrotik.logging.channel', 'daily'))->info(
            "Auto Isolir: {$action}",
            [
                'customer_id' => $customer->id,
                'customer_name' => $customer->name,
                'device_id' => $device->id,
                'device_name' => $device->name,
                'action' => $action,
                'timestamp' => now()->toDateTimeString(),
            ]
        );
    }

    /**
     * Log error
     *
     * @param string $method
     * @param Exception $exception
     * @param Customer|null $customer
     * @return void
     */
    protected function logError(string $method, Exception $exception, ?Customer $customer = null): void
    {
        $context = [
            'method' => $method,
            'message' => $exception->getMessage(),
        ];

        if ($customer) {
            $context['customer'] = [
                'id' => $customer->id,
                'name' => $customer->name,
            ];
        }

        Log::error("AutoIsolirService::{$method}", $context);
    }
}

