<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class AutoIsolirConfig extends Model
{
    use HasFactory, LogsActivity;

    protected $fillable = [
        'mikrotik_device_id',
        'enabled',
        'grace_period_days',
        'auto_restore',
        'send_notification',
        'warning_days',
        'isolir_queue_name',
        'isolir_speed',
        'notes',
    ];

    protected $casts = [
        'enabled' => 'boolean',
        'auto_restore' => 'boolean',
        'send_notification' => 'boolean',
        'grace_period_days' => 'integer',
        'warning_days' => 'integer',
    ];

    /**
     * Get the mikrotik device that owns the config
     */
    public function mikrotikDevice(): BelongsTo
    {
        return $this->belongsTo(MikrotikDevice::class);
    }

    /**
     * Check if auto isolir is enabled
     */
    public function isEnabled(): bool
    {
        return $this->enabled;
    }

    /**
     * Activity log configuration
     */
    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->useLogName('auto_isolir_configs')
            ->logOnly(['enabled', 'grace_period_days', 'warning_days'])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs()
            ->setDescriptionForEvent(function (string $eventName) {
                $verbs = [
                    'created' => 'dibuat',
                    'updated' => 'diperbarui',
                    'deleted' => 'dihapus',
                ];
                $verb = $verbs[$eventName] ?? $eventName;
                return "Konfigurasi auto isolir {$verb}";
            });
    }
}

