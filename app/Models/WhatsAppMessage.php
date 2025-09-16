<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class WhatsAppMessage extends Model
{
    use HasFactory, LogsActivity;

    protected $table = 'whats_app_messages';

    protected $fillable = [
        'customer_id',
        'payment_id',
        'message_type',
        'message',
        'status',
        'response',
        'sent_at',
        'scheduled_at',
    ];

    protected $casts = [
        'sent_at' => 'datetime',
        'scheduled_at' => 'datetime',
        'response' => 'array',
    ];

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function payment(): BelongsTo
    {
        return $this->belongsTo(Payment::class);
    }

    /**
     * Get status badge color
     */
    public function getStatusColorAttribute(): string
    {
        return match($this->status) {
            'sent' => 'success',
            'pending' => 'warning',
            'failed' => 'danger',
            default => 'secondary',
        };
    }

    /**
     * Get message type label
     */
    public function getMessageTypeLabelAttribute(): string
    {
        return match($this->message_type) {
            'billing.new' => 'Tagihan Baru',
            'billing.reminder' => 'Pengingat Tagihan',
            'billing.overdue' => 'Tagihan Terlambat',
            'billing.paid' => 'Konfirmasi Pembayaran',
            'broadcast' => 'Broadcast',
            default => $this->message_type,
        };
    }

    public function getActivitylogOptions(): LogOptions
    {
        $verbs = [
            'created' => 'dibuat',
            'updated' => 'diperbarui',
            'deleted' => 'dihapus',
        ];

        return LogOptions::defaults()
            ->useLogName('whats_app_messages')
            ->logOnly(['status', 'message_type', 'sent_at', 'scheduled_at'])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs()
            ->setDescriptionForEvent(function (string $eventName) use ($verbs) {
                $target = $this->customer->name ?? ("Pelanggan #{$this->customer_id}");
                $verb = $verbs[$eventName] ?? $eventName;
                $type = $this->message_type ?? '-';
                $status = $this->status ?? '-';
                return "Pesan WhatsApp ($type) untuk $target $verb. Status: $status.";
            });
    }
}
