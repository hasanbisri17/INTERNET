<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class DunningConfig extends Model
{
    use HasFactory, LogsActivity;
    
    protected $table = 'dunning_configs';

    protected $fillable = [
        'name',
        'description',
        'is_active',
        'grace_period_days',
        'auto_suspend',
        'suspend_after_days',
        'auto_unsuspend_on_payment',
        'n8n_enabled',
        'n8n_webhook_url',
        'n8n_trigger_after_days',
        'n8n_auto_unsuspend',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'auto_suspend' => 'boolean',
        'auto_unsuspend_on_payment' => 'boolean',
        'n8n_enabled' => 'boolean',
        'n8n_auto_unsuspend' => 'boolean',
    ];

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly([
                'name',
                'description',
                'is_active',
                'grace_period_days',
                'auto_suspend',
                'suspend_after_days',
                'auto_unsuspend_on_payment',
                'n8n_enabled',
                'n8n_webhook_url',
                'n8n_trigger_after_days',
                'n8n_auto_unsuspend',
            ])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }

    public function dunningSchedules()
    {
        return $this->hasMany(DunningSchedule::class);
    }
}