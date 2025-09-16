<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class TransactionCategory extends Model
{
    use LogsActivity;

    protected $fillable = [
        'name',
        'type',
        'description',
    ];

    public function transactions(): HasMany
    {
        return $this->hasMany(CashTransaction::class, 'category_id');
    }

    public function scopeIncome($query)
    {
        return $query->where('type', 'income');
    }

    public function scopeExpense($query)
    {
        return $query->where('type', 'expense');
    }

    public function getActivitylogOptions(): LogOptions
    {
        $verbs = [
            'created' => 'dibuat',
            'updated' => 'diperbarui',
            'deleted' => 'dihapus',
        ];

        return LogOptions::defaults()
            ->useLogName('transaction_categories')
            ->logFillable()
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs()
            ->setDescriptionForEvent(function (string $eventName) use ($verbs) {
                $name = $this->name ?? ("Kategori Transaksi #{$this->id}");
                $verb = $verbs[$eventName] ?? $eventName;
                return "Kategori Transaksi $name $verb.";
            });
    }
}
