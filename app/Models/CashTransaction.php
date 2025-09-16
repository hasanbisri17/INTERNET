<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;

class CashTransaction extends Model
{
    use LogsActivity;

    protected $fillable = [
        'date',
        'type',
        'amount',
        'description',
        'category_id',
        'payment_id',
        'voided_at',
        'voided_by',
        'void_reason',
    ];

    protected $casts = [
        'date' => 'date',
        'amount' => 'decimal:2',
        'voided_at' => 'datetime',
    ];

    public function scopeIncome($query)
    {
        return $query->where('type', 'income');
    }

    public function scopeExpense($query)
    {
        return $query->where('type', 'expense');
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(TransactionCategory::class, 'category_id');
    }

    public function payment(): BelongsTo
    {
        return $this->belongsTo(Payment::class);
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->useLogName('cash_transactions')
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
                $typeMap = [
                    'income' => 'pemasukan',
                    'expense' => 'pengeluaran',
                ];
                $type = $typeMap[$this->type ?? ''] ?? 'transaksi kas';
                $amt = number_format((float)($this->amount ?? 0), 2);
                return ucfirst($type) . " $action (jumlah: $amt)";
            });
    }
}
