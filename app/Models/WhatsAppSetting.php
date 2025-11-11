<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class WhatsAppSetting extends Model
{
    use LogsActivity;

    protected $table = 'whatsapp_settings';

    protected $fillable = [
        'api_token',
        'basic_auth_username',
        'basic_auth_password',
        'api_url',
        'default_country_code',
        'is_active',
        'session',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    /**
     * Get the current active settings
     */
    public static function getCurrentSettings(): ?self
    {
        return static::where('is_active', true)->first();
    }

    /**
     * Update settings and ensure only one active record
     */
    public static function updateSettings(array $data): self
    {
        $settings = static::getCurrentSettings() ?? new static();
        
        // Deactivate all other settings
        if ($data['is_active'] ?? true) {
            static::where('id', '!=', $settings->id)->update(['is_active' => false]);
        }
        
        $settings->fill($data);
        $settings->save();
        
        return $settings;
    }

    public function getActivitylogOptions(): LogOptions
    {
        $verbs = [
            'created' => 'dibuat',
            'updated' => 'diperbarui',
            'deleted' => 'dihapus',
        ];

        return LogOptions::defaults()
            ->useLogName('whatsapp_settings')
            ->logOnly(['api_url', 'default_country_code', 'is_active'])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs()
            ->setDescriptionForEvent(function (string $eventName) use ($verbs) {
                $verb = $verbs[$eventName] ?? $eventName;
                return "Pengaturan WhatsApp $verb.";
            });
    }
}
