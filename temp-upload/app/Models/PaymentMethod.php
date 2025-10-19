<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class PaymentMethod extends Model
{
    use LogsActivity;

    protected $fillable = [
        'name',
        'code',
        'type',
        'provider',
        'account_number',
        'account_name',
        'instructions',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    const TYPES = [
        'cash' => 'Cash',
        'bank_transfer' => 'Bank Transfer',
        'e_wallet' => 'E-Wallet'
    ];

    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }

    public function getActivitylogOptions(): LogOptions
    {
        $verbs = [
            'created' => 'dibuat',
            'updated' => 'diperbarui',
            'deleted' => 'dihapus',
        ];

        return LogOptions::defaults()
            ->useLogName('payment_methods')
            ->logFillable()
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs()
            ->setDescriptionForEvent(function (string $eventName) use ($verbs) {
                $name = $this->name ?? ("Metode Pembayaran #{$this->id}");
                $verb = $verbs[$eventName] ?? $eventName;
                return "$name $verb.";
            });
    }
}
