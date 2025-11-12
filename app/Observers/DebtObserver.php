<?php

namespace App\Observers;

use App\Models\Debt;
use App\Models\CashTransaction;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

class DebtObserver
{
    /**
     * Handle the Debt "deleting" event.
     * This runs BEFORE the debt is deleted, so we can still access the relationship.
     */
    public function deleting(Debt $debt): void
    {
        DB::transaction(function () use ($debt) {
            try {
                // Void the cash transaction (expense) that was created when debt was created
                if ($debt->cash_transaction_id) {
                    $cashTransaction = CashTransaction::find($debt->cash_transaction_id);
                    if ($cashTransaction) {
                        $cashTransaction->update([
                            'voided_at' => now(),
                            'voided_by' => auth()->id(),
                            'void_reason' => 'Hutang dihapus - KAS dikembalikan',
                        ]);
                        
                        Log::info("Cash transaction voided for deleted debt", [
                            'cash_transaction_id' => $cashTransaction->id,
                            'debt_id' => $debt->id,
                            'amount' => $cashTransaction->amount,
                        ]);
                    }
                }
            } catch (\Exception $e) {
                Log::error("Error handling debt deletion", [
                    'debt_id' => $debt->id,
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString(),
                ]);
                throw $e; // Re-throw to prevent deletion if there's an error
            }
        });
    }
}
