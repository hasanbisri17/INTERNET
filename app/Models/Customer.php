<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;

class Customer extends Model
{
    use HasFactory, LogsActivity;

    protected $fillable = [
        'name',
        'email',
        'phone',
        'address',
        'internet_package_id',
    ];

    public function internetPackage(): BelongsTo
    {
        return $this->belongsTo(InternetPackage::class);
    }

    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->useLogName('customers')
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
                $name = $this->name ?? 'Pelanggan';
                return "$name $action";
            });
    }
}
