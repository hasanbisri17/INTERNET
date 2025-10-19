<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;

class Setting extends Model
{
    use LogsActivity;

    protected $table = 'settings';

    protected $fillable = [
        'key',
        'value',
    ];

    /**
     * Get the activity log options for the model.
     */
    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->useLogName('settings')
            ->logFillable()
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs()
            ->setDescriptionForEvent(function (string $eventName): string {
                $verbs = [
                    'created' => 'dibuat',
                    'updated' => 'diperbarui',
                    'deleted' => 'dihapus',
                ];
                $action = $verbs[$eventName] ?? $eventName;
                $key = $this->key ?? 'Pengaturan';
                return "Pengaturan '$key' $action";
            });
    }

    /**
     * Get a setting value by key
     *
     * @param string $key
     * @param mixed $default
     * @return mixed
     */
    public static function get(string $key, $default = null)
    {
        $cacheKey = "setting_{$key}";

        return Cache::remember($cacheKey, 60 * 60, function () use ($key, $default) {
            $setting = static::where('key', $key)->first();
            return $setting ? $setting->value : $default;
        });
    }

    /**
     * Set a setting value
     *
     * @param string $key
     * @param mixed $value
     * @return Setting
     */
    public static function set(string $key, $value)
    {
        $setting = static::updateOrCreate(
            ['key' => $key],
            ['value' => $value]
        );

        // Clear the cache for this key
        Cache::forget("setting_{$key}");

        return $setting;
    }

    /**
     * Upload and set a logo image
     *
     * @param \Illuminate\Http\UploadedFile $file
     * @param string $key The setting key to store the logo path
     * @return string The path to the uploaded logo
     */
    public static function setLogo($file, $key = 'invoice_logo')
    {
        // Delete old logo if exists
        $oldLogo = static::get($key);
        if ($oldLogo && Storage::disk('public')->exists($oldLogo)) {
            Storage::disk('public')->delete($oldLogo);
        }

        // Store the new logo
        $path = $file->store('logos', 'public');
        
        // Save the path to the database
        static::set($key, $path);

        return $path;
    }

    /**
     * Get the logo URL
     *
     * @param string $key The setting key where the logo path is stored
     * @return string|null
     */
    public static function getLogo($key = 'invoice_logo')
    {
        $logo = static::get($key);
        
        if ($logo) {
            return Storage::disk('public')->url($logo);
        }
        
        return null;
    }
    
    /**
     * Get the application logo URL
     *
     * @return string|null
     */
    public static function getAppLogo()
    {
        return static::getLogo('app_logo');
    }
}