<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class CustomerPortalConfig extends Model
{
    use HasFactory, LogsActivity;
    
    protected $table = 'customer_portal_configs';

    protected $fillable = [
        'portal_name',
        'portal_logo',
        'portal_theme',
        'enable_registration',
        'require_email_verification',
        'enable_password_reset',
        'enable_payment_feature',
        'enable_ticket_feature',
        'enable_usage_stats',
        'visible_menu_items',
    ];

    protected $casts = [
        'enable_registration' => 'boolean',
        'require_email_verification' => 'boolean',
        'enable_password_reset' => 'boolean',
        'enable_payment_feature' => 'boolean',
        'enable_ticket_feature' => 'boolean',
        'enable_usage_stats' => 'boolean',
        'visible_menu_items' => 'array',
    ];

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly([
                'portal_name',
                'portal_logo',
                'portal_theme',
                'enable_registration',
                'require_email_verification',
                'enable_password_reset',
                'enable_payment_feature',
                'enable_ticket_feature',
                'enable_usage_stats',
                'visible_menu_items',
            ])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }
}