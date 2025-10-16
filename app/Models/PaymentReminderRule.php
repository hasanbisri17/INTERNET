<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class PaymentReminderRule extends Model
{
    use HasFactory, LogsActivity;

    protected $fillable = [
        'name',
        'days_before_due',
        'whatsapp_template_id',
        'is_active',
        'send_time',
        'priority',
        'description',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'send_time' => 'datetime',
        'days_before_due' => 'integer',
        'priority' => 'integer',
    ];

    /**
     * Relationship dengan WhatsAppTemplate
     */
    public function whatsappTemplate(): BelongsTo
    {
        return $this->belongsTo(WhatsAppTemplate::class);
    }

    /**
     * Scope untuk mendapatkan rules yang aktif
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope untuk mendapatkan rules berdasarkan prioritas
     */
    public function scopeOrdered($query)
    {
        return $query->orderBy('priority', 'asc')
                     ->orderBy('days_before_due', 'desc'); // dari yang paling jauh ke yang paling dekat
    }

    /**
     * Helper untuk mendapatkan label timing
     */
    public function getTimingLabelAttribute(): string
    {
        if ($this->days_before_due < 0) {
            return abs($this->days_before_due) . ' hari sebelum jatuh tempo';
        } elseif ($this->days_before_due == 0) {
            return 'Tepat jatuh tempo';
        } else {
            return $this->days_before_due . ' hari setelah jatuh tempo (overdue)';
        }
    }

    /**
     * Helper untuk cek apakah ini reminder sebelum jatuh tempo
     */
    public function isBeforeDue(): bool
    {
        return $this->days_before_due < 0;
    }

    /**
     * Helper untuk cek apakah ini reminder tepat jatuh tempo
     */
    public function isOnDue(): bool
    {
        return $this->days_before_due == 0;
    }

    /**
     * Helper untuk cek apakah ini reminder overdue
     */
    public function isOverdue(): bool
    {
        return $this->days_before_due > 0;
    }

    /**
     * Activity log configuration
     */
    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->useLogName('payment_reminder_rules')
            ->logOnly(['name', 'days_before_due', 'is_active', 'whatsapp_template_id'])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs()
            ->setDescriptionForEvent(function (string $eventName) {
                $verbs = [
                    'created' => 'dibuat',
                    'updated' => 'diperbarui',
                    'deleted' => 'dihapus',
                ];
                $verb = $verbs[$eventName] ?? $eventName;
                $name = $this->name ?? 'Aturan reminder';
                return "{$name} {$verb}";
            });
    }
}
