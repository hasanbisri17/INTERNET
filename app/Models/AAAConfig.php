<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class AAAConfig extends Model
{
    use HasFactory, LogsActivity;
    
    protected $table = 'aaa_configs';

    protected $fillable = [
        'name',
        'api_url',
        'api_username',
        'api_password',
        'api_key',
        'connection_type',
        'is_active',
        'timeout',
        'captive_portal_url',
        'enable_captive_portal',
        'captive_portal_template',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'enable_captive_portal' => 'boolean',
    ];

    protected $hidden = [
        'api_password',
        'api_key',
    ];

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly([
                'name',
                'api_url',
                'api_username',
                'connection_type',
                'is_active',
                'timeout',
                'captive_portal_url',
                'enable_captive_portal',
                'captive_portal_template',
            ])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }
}