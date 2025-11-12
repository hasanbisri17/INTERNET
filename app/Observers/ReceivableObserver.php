<?php

namespace App\Observers;

use App\Models\Receivable;
use Illuminate\Support\Facades\Log;

class ReceivableObserver
{
    /**
     * Handle the Receivable "deleting" event.
     * For receivables, we don't create cash transaction when creating receivable,
     * so we don't need to void anything when deleting.
     * Cash transactions are created only when receiving payment (income).
     */
    public function deleting(Receivable $receivable): void
    {
        // Receivable deletion doesn't need to void cash transaction
        // because cash transaction is only created when payment is received (income)
        // If receivable is deleted, payments will be deleted via cascade
        // and ReceivablePaymentObserver will handle voiding the cash transactions from payments
    }
}
