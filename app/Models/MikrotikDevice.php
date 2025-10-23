<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class MikrotikDevice extends Model
{
    use HasFactory, LogsActivity;

    protected $table = 'mikrotik_devices';

    protected $fillable = [
        'name',
        'ip_address',
        'port',
        'username',
        'password',
        'use_ssl',
        'is_active',
        'description',
        'additional_config',
    ];

    protected $casts = [
        'port' => 'integer',
        'use_ssl' => 'boolean',
        'is_active' => 'boolean',
        'additional_config' => 'json',
    ];

    public function getConnectionUrl(): string
    {
        $protocol = $this->use_ssl ? 'ssl' : 'tcp';
        return "{$protocol}://{$this->ip_address}:{$this->port}";
    }

    /**
     * Get the customers for this mikrotik device
     */
    public function customers(): HasMany
    {
        return $this->hasMany(Customer::class);
    }

    /**
     * Get the queues for this mikrotik device
     */
    public function queues(): HasMany
    {
        return $this->hasMany(MikrotikQueue::class);
    }

    /**
     * Get the monitoring logs for this mikrotik device
     */
    public function monitoringLogs(): HasMany
    {
        return $this->hasMany(MikrotikMonitoringLog::class);
    }

    /**
     * Get the latest monitoring log
     */
    public function latestMonitoringLog(): HasOne
    {
        return $this->hasOne(MikrotikMonitoringLog::class)->latestOfMany('checked_at');
    }

    /**
     * Get the auto isolir config for this mikrotik device
     */
    public function autoIsolirConfig(): HasOne
    {
        return $this->hasOne(AutoIsolirConfig::class);
    }

    /**
     * Check if device is online based on latest monitoring
     */
    public function isOnline(): bool
    {
        $latestLog = $this->latestMonitoringLog;
        return $latestLog && $latestLog->status === 'online';
    }

    /**
     * Activity log configuration
     */
    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->useLogName('mikrotik_devices')
            ->logOnly(['name', 'ip_address', 'port', 'is_active', 'description'])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs()
            ->setDescriptionForEvent(function (string $eventName) {
                $verbs = [
                    'created' => 'dibuat',
                    'updated' => 'diperbarui',
                    'deleted' => 'dihapus',
                ];
                $verb = $verbs[$eventName] ?? $eventName;
                $name = $this->name ?? 'Perangkat Mikrotik';
                return "{$name} {$verb}";
            });
    }
}