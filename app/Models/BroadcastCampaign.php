<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class BroadcastCampaign extends Model
{
    use HasFactory, LogsActivity;

    protected $fillable = [
        'title',
        'message',
        'media_path',
        'media_type',
        'recipient_type',
        'recipient_ids',
        'total_recipients',
        'success_count',
        'failed_count',
        'status',
        'created_by',
        'sent_at',
    ];

    protected $casts = [
        'recipient_ids' => 'array',
        'sent_at' => 'datetime',
    ];

    /**
     * Get the user who created this broadcast
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Get all messages sent in this campaign
     */
    public function messages(): HasMany
    {
        return $this->hasMany(WhatsAppMessage::class, 'broadcast_campaign_id');
    }

    /**
     * Get status badge color
     */
    public function getStatusColorAttribute(): string
    {
        return match($this->status) {
            'completed' => 'success',
            'processing' => 'info',
            'pending' => 'warning',
            'failed' => 'danger',
            default => 'secondary',
        };
    }

    /**
     * Get status label
     */
    public function getStatusLabelAttribute(): string
    {
        return match($this->status) {
            'completed' => 'Selesai',
            'processing' => 'Diproses',
            'pending' => 'Menunggu',
            'failed' => 'Gagal',
            default => $this->status,
        };
    }

    /**
     * Get recipient type label
     */
    public function getRecipientTypeLabelAttribute(): string
    {
        return match($this->recipient_type) {
            'all' => 'Semua Pelanggan',
            'active' => 'Pelanggan Aktif',
            'custom' => 'Pilihan Manual',
            default => $this->recipient_type,
        };
    }

    /**
     * Get media URL
     */
    public function getMediaUrlAttribute(): ?string
    {
        if (!$this->media_path) {
            return null;
        }

        return asset('storage/' . $this->media_path);
    }

    /**
     * Calculate success rate percentage
     */
    public function getSuccessRateAttribute(): float
    {
        if ($this->total_recipients == 0) {
            return 0;
        }

        return round(($this->success_count / $this->total_recipients) * 100, 2);
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->useLogName('broadcast_campaigns')
            ->logOnly(['title', 'status', 'total_recipients', 'success_count', 'failed_count'])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs()
            ->setDescriptionForEvent(function (string $eventName) {
                $verbs = [
                    'created' => 'dibuat',
                    'updated' => 'diperbarui',
                    'deleted' => 'dihapus',
                ];
                $verb = $verbs[$eventName] ?? $eventName;
                return "Broadcast campaign '{$this->title}' {$verb}.";
            });
    }
}

