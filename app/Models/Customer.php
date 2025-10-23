<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;

class Customer extends Model
{
    use HasFactory, LogsActivity;

    protected $fillable = [
        'name',
        'email',
        'phone',
        'address',
        'internet_package_id',
        'connection_type',
        'customer_id',
        'mikrotik_device_id',
        'mikrotik_queue_id',
        'installation_date',
        'activation_date',
        'due_date',
        'status',
        'is_isolated',
        'isolated_at',
        'static_ip',
        'mac_address',
    ];

    public function internetPackage(): BelongsTo
    {
        return $this->belongsTo(InternetPackage::class);
    }

    public function mikrotikDevice(): BelongsTo
    {
        return $this->belongsTo(MikrotikDevice::class);
    }

    public function mikrotikQueue(): BelongsTo
    {
        return $this->belongsTo(MikrotikQueue::class);
    }

    /**
     * Get IP Bindings for the customer
     */
    public function ipBindings(): HasMany
    {
        return $this->hasMany(MikrotikIpBinding::class);
    }

    /**
     * Check if customer is active
     */
    public function isActive(): bool
    {
        return $this->status === 'active';
    }

    /**
     * Check if customer is isolated
     */
    public function isIsolated(): bool
    {
        return (bool) $this->is_isolated;
    }

    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'installation_date' => 'date',
        'activation_date' => 'date',
        'due_date' => 'date',
        'is_isolated' => 'boolean',
        'isolated_at' => 'datetime',
    ];

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->useLogName('customers')
            ->logFillable()
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs()
            ->setDescriptionForEvent(function (string $eventName): string {
                $verbs = [
                    'created' => 'dibuat',
                    'updated' => 'diperbarui',
                    'deleted' => 'dihapus',
                ];
                $action = $verbs[$eventName] ?? $eventName;
                $name = $this->name ?? 'Pelanggan';
                return "$name $action";
            });
    }
}
