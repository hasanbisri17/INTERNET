<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;
use Carbon\Carbon;

class Debt extends Model
{
    use LogsActivity;

    protected $fillable = [
        'creditor_type',
        'creditor_id',
        'creditor_name',
        'creditor_contact',
        'amount',
        'paid_amount',
        'due_date',
        'description',
        'status',
        'cash_transaction_id',
        'created_by',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'paid_amount' => 'decimal:2',
        'due_date' => 'date',
    ];

    /**
     * Get the user who created this debt
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Get the creditor user (if creditor_type is 'user')
     */
    public function creditorUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'creditor_id');
    }

    /**
     * Get all payments for this debt
     */
    public function payments(): HasMany
    {
        return $this->hasMany(DebtPayment::class);
    }

    /**
     * Get the cash transaction for this debt
     */
    public function cashTransaction(): BelongsTo
    {
        return $this->belongsTo(CashTransaction::class);
    }

    /**
     * Get remaining amount
     */
    public function getRemainingAmountAttribute(): float
    {
        return max(0, $this->amount - $this->paid_amount);
    }

    /**
     * Get creditor display name (from relationship or manual input)
     */
    public function getCreditorDisplayNameAttribute(): string
    {
        if ($this->creditor_type === 'user' && $this->creditorUser) {
            return $this->creditorUser->name;
        }
        return $this->creditor_name ?? '-';
    }

    /**
     * Check if debt is overdue
     */
    public function isOverdue(): bool
    {
        return $this->due_date < now() && $this->status !== 'paid';
    }

    /**
     * Update status based on paid amount
     */
    public function updateStatus(): void
    {
        if ($this->paid_amount >= $this->amount) {
            $this->status = 'paid';
        } elseif ($this->paid_amount > 0) {
            $this->status = 'partial';
        } elseif ($this->isOverdue()) {
            $this->status = 'overdue';
        } else {
            $this->status = 'pending';
        }
        $this->save();
    }

    /**
     * Get payment count
     */
    public function getPaymentCountAttribute(): int
    {
        return $this->payments()->count();
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->useLogName('debts')
            ->logFillable()
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs()
            ->setDescriptionForEvent(function (string $eventName): string {
                $verbs = [
                    'created' => 'dibuat',
                    'updated' => 'diperbarui',
                    'deleted' => 'dihapus',
                ];
                $verb = $verbs[$eventName] ?? $eventName;
                $creditor = $this->creditor_display_name ?? 'Unknown';
                $amount = number_format($this->amount ?? 0, 2);
                return "Hutang {$verb} untuk {$creditor} (Rp {$amount})";
            });
    }
}
