<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MikrotikMonitoringLog extends Model
{
    use HasFactory;

    protected $fillable = [
        'mikrotik_device_id',
        'status',
        'uptime',
        'cpu_load',
        'free_memory',
        'total_memory',
        'free_hdd',
        'total_hdd',
        'active_users',
        'version',
        'board_name',
        'error_message',
        'additional_data',
        'checked_at',
    ];

    protected $casts = [
        'checked_at' => 'datetime',
        'active_users' => 'integer',
        'additional_data' => 'json',
    ];

    /**
     * Get the mikrotik device that owns the monitoring log
     */
    public function mikrotikDevice(): BelongsTo
    {
        return $this->belongsTo(MikrotikDevice::class);
    }

    /**
     * Check if the device is online
     */
    public function isOnline(): bool
    {
        return $this->status === 'online';
    }

    /**
     * Check if the device is offline
     */
    public function isOffline(): bool
    {
        return $this->status === 'offline';
    }

    /**
     * Check if there's an error
     */
    public function hasError(): bool
    {
        return $this->status === 'error';
    }

    /**
     * Get memory usage percentage
     */
    public function getMemoryUsagePercentage(): ?float
    {
        if (!$this->free_memory || !$this->total_memory) {
            return null;
        }

        $free = $this->parseSize($this->free_memory);
        $total = $this->parseSize($this->total_memory);

        if ($total == 0) {
            return null;
        }

        return round((($total - $free) / $total) * 100, 2);
    }

    /**
     * Get HDD usage percentage
     */
    public function getHddUsagePercentage(): ?float
    {
        if (!$this->free_hdd || !$this->total_hdd) {
            return null;
        }

        $free = $this->parseSize($this->free_hdd);
        $total = $this->parseSize($this->total_hdd);

        if ($total == 0) {
            return null;
        }

        return round((($total - $free) / $total) * 100, 2);
    }

    /**
     * Parse size string to bytes
     */
    private function parseSize(string $size): int
    {
        $size = trim($size);
        $unit = strtoupper(substr($size, -3));
        $value = (float) substr($size, 0, -3);

        return match($unit) {
            'KIB' => (int) ($value * 1024),
            'MIB' => (int) ($value * 1024 * 1024),
            'GIB' => (int) ($value * 1024 * 1024 * 1024),
            default => (int) $value,
        };
    }
}

