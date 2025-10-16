<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;

class DunningStep extends Model
{
    use HasFactory, LogsActivity;

    protected $fillable = [
        'dunning_schedule_id',
        'payment_id',
        'step_name',
        'days_after_due',
        'action_type', // notification, penalty, suspend
        'action_config',
        'executed_at',
        'status', // pending, executed, skipped
    ];

    protected $casts = [
        'action_config' => 'json',
        'executed_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function dunningSchedule(): BelongsTo
    {
        return $this->belongsTo(DunningSchedule::class);
    }

    public function payment(): BelongsTo
    {
        return $this->belongsTo(Payment::class);
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->useLogName('dunning_steps')
            ->logFillable()
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }
}