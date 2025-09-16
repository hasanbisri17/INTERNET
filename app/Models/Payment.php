<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Barryvdh\DomPDF\Facade\Pdf;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;

class Payment extends Model
{
    use HasFactory, LogsActivity;

    protected $fillable = [
        'customer_id',
        'internet_package_id',
        'payment_method_id',
        'invoice_number',
        'amount',
        'status',
        'due_date',
        'payment_date',
        'notes',
        'gateway',
        'gateway_invoice_id',
        'gateway_payment_id',
        'gateway_status',
        'canceled_at',
        'canceled_by',
        'canceled_reason',
    ];

    protected $casts = [
        'due_date' => 'datetime',
        'payment_date' => 'datetime',
        'canceled_at' => 'datetime',
    ];

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function internetPackage(): BelongsTo
    {
        return $this->belongsTo(InternetPackage::class);
    }

    public function paymentMethod(): BelongsTo
    {
        return $this->belongsTo(PaymentMethod::class);
    }

    public function cashTransactions(): HasMany
    {
        return $this->hasMany(CashTransaction::class);
    }

    public static function generateInvoiceNumber(): string
    {
        $maxAttempts = 5;
        $attempt = 1;
        
        while ($attempt <= $maxAttempts) {
            try {
                return DB::transaction(function () {
                    $prefix = 'INV';
                    $year = date('Y');
                    $month = date('m');
                    
                    // Get the highest invoice number for this month
                    $lastPayment = self::whereYear('created_at', $year)
                        ->whereMonth('created_at', $month)
                        ->where('invoice_number', 'LIKE', "{$prefix}-{$year}{$month}-%")
                        ->orderBy('invoice_number', 'desc')
                        ->lockForUpdate()
                        ->first();
                    
                    // Extract the sequence number or start from 0
                    $lastNumber = 0;
                    if ($lastPayment) {
                        preg_match('/(\d+)$/', $lastPayment->invoice_number, $matches);
                        $lastNumber = isset($matches[1]) ? intval($matches[1]) : 0;
                    }
                    
                    // Format: INV-YYYYMM-XXXX
                    $nextNumber = $lastNumber + 1;
                    $invoiceNumber = sprintf('%s-%s%s-%04d', $prefix, $year, $month, $nextNumber);
                    
                    // Double check uniqueness
                    if (self::where('invoice_number', $invoiceNumber)->exists()) {
                        throw new \Exception('Invoice number already exists');
                    }
                    
                    return $invoiceNumber;
                });
            } catch (\Exception $e) {
                if ($attempt === $maxAttempts) {
                    throw $e;
                }
                $attempt++;
                // Add a small random delay before retrying
                usleep(rand(100000, 500000)); // 0.1 to 0.5 seconds
            }
        }
        
        throw new \Exception('Failed to generate unique invoice number after ' . $maxAttempts . ' attempts');
    }

    public function generatePDF(): string
    {
        $pdf = Pdf::loadView('invoice', ['payment' => $this]);
        $filename = 'invoices/' . $this->invoice_number . '.pdf';

        Storage::disk('public')->put($filename, $pdf->output());

        return $filename;
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->useLogName('payments')
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
                $inv = $this->invoice_number ?? 'N/A';
                return "Pembayaran $inv $action";
            });
    }
}
