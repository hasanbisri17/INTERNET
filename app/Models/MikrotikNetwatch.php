<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class MikrotikNetwatch extends Model
{
    use HasFactory, LogsActivity;

    protected $table = 'mikrotik_netwatch';

    protected $fillable = [
        'mikrotik_device_id',
        'netwatch_id',
        'host',
        'interval',
        'timeout',
        'status',
        'since',
        'up_script',
        'down_script',
        'comment',
        'is_disabled',
        'is_synced',
        'last_synced_at',
    ];

    protected $casts = [
        'is_disabled' => 'boolean',
        'is_synced' => 'boolean',
        'since' => 'datetime',
        'last_synced_at' => 'datetime',
    ];

    /**
     * Get the mikrotik device that owns the netwatch
     */
    public function mikrotikDevice(): BelongsTo
    {
        return $this->belongsTo(MikrotikDevice::class);
    }

    /**
     * Get status badge color
     */
    public function getStatusBadgeColor(): string
    {
        return match($this->status) {
            'up' => 'success',
            'down' => 'danger',
            'unknown' => 'warning',
            default => 'gray',
        };
    }

    /**
     * Get status label
     */
    public function getStatusLabel(): string
    {
        return match($this->status) {
            'up' => '🟢 Up',
            'down' => '🔴 Down',
            'unknown' => '🟡 Unknown',
            default => ucfirst($this->status ?? 'unknown'),
        };
    }

    /**
     * Activity log configuration
     */
    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->useLogName('mikrotik_netwatch')
            ->logOnly(['host', 'status', 'is_disabled'])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs()
            ->setDescriptionForEvent(function (string $eventName) {
                $verbs = [
                    'created' => 'dibuat',
                    'updated' => 'diperbarui',
                    'deleted' => 'dihapus',
                ];
                $verb = $verbs[$eventName] ?? $eventName;
                $name = $this->host ?? 'Netwatch';
                return "Netwatch {$name} {$verb}";
            });
    }
}

