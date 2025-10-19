<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class InternetPackage extends Model
{
    use HasFactory, LogsActivity;

    public function customers(): HasMany
    {
        return $this->hasMany(Customer::class);
    }

    protected $fillable = [
        'name',
        'price',
        'speed',
        'description',
        'is_active',
    ];

    protected $casts = [
        'price' => 'decimal:2',
        'is_active' => 'boolean',
    ];

    public function getActivitylogOptions(): LogOptions
    {
        $verbs = [
            'created' => 'dibuat',
            'updated' => 'diperbarui',
            'deleted' => 'dihapus',
        ];

        return LogOptions::defaults()
            ->useLogName('internet_packages')
            ->logFillable()
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs()
            ->setDescriptionForEvent(function (string $eventName) use ($verbs) {
                $name = $this->name ?? ("Paket Internet #{$this->id}");
                $verb = $verbs[$eventName] ?? $eventName;
                return "$name $verb.";
            });
    }
}
