<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class MikrotikIpBinding extends Model
{
    use HasFactory, LogsActivity;

    protected $fillable = [
        'mikrotik_device_id',
        'customer_id',
        'binding_id',
        'mac_address',
        'address',
        'to_address',
        'server',
        'type',
        'comment',
        'is_disabled',
        'is_synced',
        'last_synced_at',
    ];

    protected $casts = [
        'is_disabled' => 'boolean',
        'is_synced' => 'boolean',
        'last_synced_at' => 'datetime',
    ];

    /**
     * Get the mikrotik device that owns the IP binding
     */
    public function mikrotikDevice(): BelongsTo
    {
        return $this->belongsTo(MikrotikDevice::class);
    }

    /**
     * Get the customer that owns the IP binding
     */
    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    /**
     * Get type badge color
     */
    public function getTypeBadgeColor(): string
    {
        return match($this->type) {
            'regular' => 'success',
            'bypassed' => 'warning',
            'blocked' => 'danger',
            default => 'gray',
        };
    }

    /**
     * Get type label
     */
    public function getTypeLabel(): string
    {
        return match($this->type) {
            'regular' => 'Regular',
            'bypassed' => 'Bypassed',
            'blocked' => 'Blocked',
            default => ucfirst($this->type),
        };
    }

    /**
     * Activity log configuration
     */
    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->useLogName('mikrotik_ip_bindings')
            ->logOnly(['mac_address', 'address', 'type', 'is_disabled'])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs()
            ->setDescriptionForEvent(function (string $eventName) {
                $verbs = [
                    'created' => 'dibuat',
                    'updated' => 'diperbarui',
                    'deleted' => 'dihapus',
                ];
                $verb = $verbs[$eventName] ?? $eventName;
                $name = $this->mac_address ?? $this->address ?? 'IP Binding';
                return "IP Binding {$name} {$verb}";
            });
    }
}

