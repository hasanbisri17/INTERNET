<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class PaymentReminder extends Model
{
    use LogsActivity;

    protected $fillable = [
        'payment_id',
        'reminder_rule_id',
        'whatsapp_message_id',
        'reminder_type',
        'reminder_date',
        'status',
        'sent_at',
        'error_message',
    ];

    protected $casts = [
        'reminder_date' => 'date',
        'sent_at' => 'datetime',
    ];

    /**
     * Reminder type constants
     */
    const TYPE_H_MINUS_3 = 'h_minus_3';  // 3 days before due date
    const TYPE_H_MINUS_1 = 'h_minus_1';  // 1 day before due date
    const TYPE_H_ZERO = 'h_zero';        // On due date
    const TYPE_OVERDUE = 'overdue';      // After due date

    /**
     * Get the payment that owns the reminder
     */
    public function payment(): BelongsTo
    {
        return $this->belongsTo(Payment::class);
    }

    /**
     * Get the reminder rule used for this reminder
     */
    public function reminderRule(): BelongsTo
    {
        return $this->belongsTo(PaymentReminderRule::class);
    }

    /**
     * Get the WhatsApp message associated with this reminder
     */
    public function whatsappMessage(): BelongsTo
    {
        return $this->belongsTo(WhatsAppMessage::class, 'whatsapp_message_id');
    }

    /**
     * Scope untuk reminder yang perlu dikirim hari ini
     */
    public function scopeDueToday($query)
    {
        return $query->where('reminder_date', today())
            ->where('status', 'pending');
    }

    /**
     * Scope untuk reminder berdasarkan type
     */
    public function scopeOfType($query, string $type)
    {
        return $query->where('reminder_type', $type);
    }

    /**
     * Mark reminder as sent
     */
    public function markAsSent(int $whatsappMessageId = null): void
    {
        $this->status = 'sent';
        $this->sent_at = now();
        if ($whatsappMessageId) {
            $this->whatsapp_message_id = $whatsappMessageId;
        }
        $this->save();
    }

    /**
     * Mark reminder as failed
     */
    public function markAsFailed(string $errorMessage): void
    {
        $this->status = 'failed';
        $this->error_message = $errorMessage;
        $this->save();
    }

    /**
     * Get reminder type label
     */
    public function getReminderTypeLabelAttribute(): string
    {
        return match($this->reminder_type) {
            self::TYPE_H_MINUS_3 => 'Reminder H-3',
            self::TYPE_H_MINUS_1 => 'Reminder H-1',
            self::TYPE_H_ZERO => 'Reminder Jatuh Tempo',
            self::TYPE_OVERDUE => 'Tagihan Terlambat',
            default => $this->reminder_type,
        };
    }

    /**
     * Activity log configuration
     */
    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->useLogName('payment_reminders')
            ->logOnly(['status', 'reminder_type', 'sent_at'])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs()
            ->setDescriptionForEvent(function (string $eventName) {
                $customer = $this->payment?->customer?->name ?? 'N/A';
                $invoice = $this->payment?->invoice_number ?? 'N/A';
                $type = $this->reminder_type_label;
                
                if ($eventName === 'created') {
                    return "Reminder '{$type}' dijadwalkan untuk {$customer} ({$invoice})";
                } elseif ($eventName === 'updated' && $this->status === 'sent') {
                    return "Reminder '{$type}' terkirim ke {$customer} ({$invoice})";
                } elseif ($eventName === 'updated' && $this->status === 'failed') {
                    return "Reminder '{$type}' gagal dikirim ke {$customer} ({$invoice})";
                }
                
                return "Reminder '{$type}' diperbarui untuk {$customer} ({$invoice})";
            });
    }
}

