<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class MikrotikQueue extends Model
{
    use HasFactory, SoftDeletes, LogsActivity;

    protected $fillable = [
        'mikrotik_device_id',
        'customer_id',
        'queue_id',
        'name',
        'target',
        'type',
        'max_limit',
        'limit_at',
        'burst_limit',
        'burst_threshold',
        'burst_time',
        'priority',
        'parent',
        'comment',
        'disabled',
        'is_synced',
        'last_synced_at',
    ];

    protected $casts = [
        'disabled' => 'boolean',
        'is_synced' => 'boolean',
        'last_synced_at' => 'datetime',
    ];

    /**
     * Get the mikrotik device that owns the queue
     */
    public function mikrotikDevice(): BelongsTo
    {
        return $this->belongsTo(MikrotikDevice::class);
    }

    /**
     * Get the customer that owns the queue
     */
    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    /**
     * Check if the queue is active (not disabled)
     */
    public function isActive(): bool
    {
        return !$this->disabled;
    }

    /**
     * Enable the queue
     */
    public function enable(): void
    {
        $this->update(['disabled' => false]);
    }

    /**
     * Disable the queue
     */
    public function disable(): void
    {
        $this->update(['disabled' => true]);
    }

    /**
     * Parse max limit to array [upload, download]
     */
    public function getMaxLimitArray(): array
    {
        if (!$this->max_limit) {
            return ['0', '0'];
        }
        
        $parts = explode('/', $this->max_limit);
        return [
            'upload' => $parts[0] ?? '0',
            'download' => $parts[1] ?? '0',
        ];
    }

    /**
     * Activity log configuration
     */
    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->useLogName('mikrotik_queues')
            ->logOnly(['name', 'target', 'max_limit', 'disabled'])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs()
            ->setDescriptionForEvent(function (string $eventName) {
                $verbs = [
                    'created' => 'dibuat',
                    'updated' => 'diperbarui',
                    'deleted' => 'dihapus',
                ];
                $verb = $verbs[$eventName] ?? $eventName;
                $name = $this->name ?? 'Queue Mikrotik';
                return "{$name} {$verb}";
            });
    }
}

