<?php

namespace App\Observers;

use App\Models\MikrotikIpBinding;
use App\Services\MikrotikIpBindingService;
use Illuminate\Support\Facades\Log;

class MikrotikIpBindingObserver
{
    protected MikrotikIpBindingService $service;

    public function __construct()
    {
        $this->service = new MikrotikIpBindingService();
    }

    /**
     * Handle the MikrotikIpBinding "created" event.
     *
     * @param  \App\Models\MikrotikIpBinding  $binding
     * @return void
     */
    public function created(MikrotikIpBinding $binding): void
    {
        // Skip observer if this is from sync (to prevent infinite loop)
        if ($this->shouldSkipSync($binding)) {
            return;
        }

        // Auto sync to MikroTik after create
        if ($binding->mikrotikDevice && $binding->mikrotikDevice->is_active) {
            try {
                Log::info("Auto-sync: Creating IP Binding to MikroTik", [
                    'binding_id' => $binding->id,
                    'address' => $binding->address,
                    'type' => $binding->type,
                ]);

                $result = $this->service->createBinding(
                    $binding->mikrotikDevice,
                    $binding
                );

                if ($result['success']) {
                    Log::info("Auto-sync: IP Binding created successfully", [
                        'binding_id' => $binding->id,
                        'mikrotik_binding_id' => $result['binding_id'] ?? null,
                    ]);
                } else {
                    Log::warning("Auto-sync: Failed to create IP Binding", [
                        'binding_id' => $binding->id,
                        'error' => $result['message'],
                    ]);
                }
            } catch (\Exception $e) {
                Log::error("Auto-sync: Exception while creating IP Binding", [
                    'binding_id' => $binding->id,
                    'error' => $e->getMessage(),
                ]);
            }
        }
    }

    /**
     * Handle the MikrotikIpBinding "updated" event.
     *
     * @param  \App\Models\MikrotikIpBinding  $binding
     * @return void
     */
    public function updated(MikrotikIpBinding $binding): void
    {
        // Skip observer if this is from sync (to prevent infinite loop)
        if ($this->shouldSkipSync($binding)) {
            return;
        }

        // Auto sync to MikroTik after update
        if ($binding->mikrotikDevice && $binding->mikrotikDevice->is_active && $binding->binding_id) {
            try {
                // Check what changed
                $dirty = $binding->getDirty();
                
                Log::info("Auto-sync: Updating IP Binding to MikroTik", [
                    'binding_id' => $binding->id,
                    'mikrotik_binding_id' => $binding->binding_id,
                    'changed_fields' => array_keys($dirty),
                ]);

                // If type changed, update it
                if (isset($dirty['type'])) {
                    $result = $this->service->updateBindingType(
                        $binding->mikrotikDevice,
                        $binding,
                        $binding->type
                    );

                    if ($result['success']) {
                        Log::info("Auto-sync: Type updated successfully", [
                            'binding_id' => $binding->id,
                            'new_type' => $binding->type,
                        ]);
                    } else {
                        Log::warning("Auto-sync: Failed to update type", [
                            'binding_id' => $binding->id,
                            'error' => $result['message'],
                        ]);
                    }
                }

                // If disabled status changed, toggle it
                if (isset($dirty['is_disabled'])) {
                    $result = $this->service->toggleBinding(
                        $binding->mikrotikDevice,
                        $binding,
                        $binding->is_disabled
                    );

                    if ($result['success']) {
                        Log::info("Auto-sync: Disabled status updated successfully", [
                            'binding_id' => $binding->id,
                            'is_disabled' => $binding->is_disabled,
                        ]);
                    } else {
                        Log::warning("Auto-sync: Failed to update disabled status", [
                            'binding_id' => $binding->id,
                            'error' => $result['message'],
                        ]);
                    }
                }
            } catch (\Exception $e) {
                Log::error("Auto-sync: Exception while updating IP Binding", [
                    'binding_id' => $binding->id,
                    'error' => $e->getMessage(),
                ]);
            }
        }
    }

    /**
     * Handle the MikrotikIpBinding "deleted" event.
     *
     * @param  \App\Models\MikrotikIpBinding  $binding
     * @return void
     */
    public function deleted(MikrotikIpBinding $binding): void
    {
        // Skip observer if this is from sync (to prevent infinite loop)
        if ($this->shouldSkipSync($binding)) {
            return;
        }

        // Auto delete from MikroTik
        if ($binding->mikrotikDevice && $binding->mikrotikDevice->is_active && $binding->binding_id) {
            try {
                Log::info("Auto-sync: Deleting IP Binding from MikroTik", [
                    'binding_id' => $binding->id,
                    'mikrotik_binding_id' => $binding->binding_id,
                ]);

                $result = $this->service->deleteBinding(
                    $binding->mikrotikDevice,
                    $binding
                );

                if ($result['success']) {
                    Log::info("Auto-sync: IP Binding deleted successfully", [
                        'binding_id' => $binding->id,
                    ]);
                } else {
                    Log::warning("Auto-sync: Failed to delete IP Binding", [
                        'binding_id' => $binding->id,
                        'error' => $result['message'],
                    ]);
                }
            } catch (\Exception $e) {
                Log::error("Auto-sync: Exception while deleting IP Binding", [
                    'binding_id' => $binding->id,
                    'error' => $e->getMessage(),
                ]);
            }
        }
    }

    /**
     * Check if observer should skip sync
     * Prevents infinite loop when syncing from MikroTik
     *
     * @param  \App\Models\MikrotikIpBinding  $binding
     * @return bool
     */
    protected function shouldSkipSync(MikrotikIpBinding $binding): bool
    {
        // If binding already has binding_id and is_synced, it means this is from sync from MikroTik
        // Skip to prevent loop
        if ($binding->binding_id && $binding->is_synced) {
            // Check if only sync-related fields changed (not user changes)
            $dirty = $binding->getDirty();
            
            // If only is_synced or last_synced_at changed, skip
            $syncOnlyFields = ['is_synced', 'last_synced_at', 'updated_at'];
            $changedFields = array_keys($dirty);
            $nonSyncFields = array_diff($changedFields, $syncOnlyFields);
            
            // If no non-sync fields changed, this is from sync operation, skip observer
            if (empty($nonSyncFields)) {
                Log::info("Observer: Skipping auto-sync (sync operation detected)", [
                    'binding_id' => $binding->id,
                    'changed_fields' => $changedFields,
                ]);
                return true;
            }
        }

        return false;
    }
}

