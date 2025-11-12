<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;
use Carbon\Carbon;

class Receivable extends Model
{
    use LogsActivity;

    protected $fillable = [
        'debtor_type',
        'debtor_customer_id',
        'debtor_user_id',
        'debtor_name',
        'debtor_contact',
        'amount',
        'paid_amount',
        'due_date',
        'description',
        'status',
        'created_by',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'paid_amount' => 'decimal:2',
        'due_date' => 'date',
    ];

    /**
     * Get the user who created this receivable
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Get the debtor customer (if debtor_type is 'customer')
     */
    public function debtorCustomer(): BelongsTo
    {
        return $this->belongsTo(Customer::class, 'debtor_customer_id');
    }

    /**
     * Get the debtor user (if debtor_type is 'user')
     */
    public function debtorUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'debtor_user_id');
    }

    /**
     * Get all payments for this receivable
     */
    public function payments(): HasMany
    {
        return $this->hasMany(ReceivablePayment::class);
    }

    /**
     * Get remaining amount
     */
    public function getRemainingAmountAttribute(): float
    {
        return max(0, $this->amount - $this->paid_amount);
    }

    /**
     * Get debtor display name (from relationship or manual input)
     */
    public function getDebtorDisplayNameAttribute(): string
    {
        if ($this->debtor_type === 'customer' && $this->debtorCustomer) {
            return $this->debtorCustomer->name;
        }
        if ($this->debtor_type === 'user' && $this->debtorUser) {
            return $this->debtorUser->name;
        }
        return $this->debtor_name ?? '-';
    }

    /**
     * Get debtor display contact (from relationship or manual input)
     */
    public function getDebtorDisplayContactAttribute(): ?string
    {
        if ($this->debtor_type === 'customer' && $this->debtorCustomer) {
            return $this->debtorCustomer->phone;
        }
        return $this->debtor_contact;
    }

    /**
     * Check if receivable is overdue
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
            ->useLogName('receivables')
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
                $debtor = $this->debtor_display_name ?? 'Unknown';
                $amount = number_format($this->amount ?? 0, 2);
                return "Piutang {$verb} untuk {$debtor} (Rp {$amount})";
            });
    }
}
