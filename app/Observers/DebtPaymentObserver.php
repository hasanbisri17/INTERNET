<?php

namespace App\Observers;

use App\Models\DebtPayment;
use App\Models\CashTransaction;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class DebtPaymentObserver
{
    /**
     * Handle the DebtPayment "deleting" event.
     * This runs BEFORE the payment is deleted, so we can still access the relationship.
     */
    public function deleting(DebtPayment $debtPayment): void
    {
        DB::transaction(function () use ($debtPayment) {
            try {
                // Get the debt
                $debt = $debtPayment->debt;
                
                if (!$debt) {
                    Log::warning("DebtPayment deleted but debt not found", [
                        'debt_payment_id' => $debtPayment->id,
                    ]);
                    return;
                }

                // Void cash transaction (income) yang dibuat saat pembayaran
                // Income ini mengembalikan KAS, jadi saat payment dihapus, income harus di-void
                Log::info("Attempting to void cash transaction for debt payment", [
                    'debt_payment_id' => $debtPayment->id,
                    'cash_transaction_id' => $debtPayment->cash_transaction_id,
                ]);
                
                if ($debtPayment->cash_transaction_id) {
                    $cashTransaction = CashTransaction::find($debtPayment->cash_transaction_id);
                    if ($cashTransaction) {
                        // Check if already voided
                        if ($cashTransaction->voided_at) {
                            Log::warning("Cash transaction already voided", [
                                'cash_transaction_id' => $cashTransaction->id,
                                'voided_at' => $cashTransaction->voided_at,
                            ]);
                        } else {
                            $voidedAt = now();
                            $voidedBy = auth()->id();
                            $voidReason = 'Pembayaran hutang dihapus - KAS dikembalikan';
                            
                            $updated = $cashTransaction->update([
                                'voided_at' => $voidedAt,
                                'voided_by' => $voidedBy,
                                'void_reason' => $voidReason,
                            ]);
                            
                            // Refresh to verify the update
                            $cashTransaction->refresh();
                            
                            if ($updated && $cashTransaction->voided_at) {
                                Log::info("Cash transaction voided successfully for deleted debt payment", [
                                    'cash_transaction_id' => $cashTransaction->id,
                                    'debt_payment_id' => $debtPayment->id,
                                    'amount' => $cashTransaction->amount,
                                    'type' => $cashTransaction->type,
                                    'voided_at' => $cashTransaction->voided_at,
                                ]);
                            } else {
                                Log::error("Failed to void cash transaction - update returned true but voided_at is still null", [
                                    'cash_transaction_id' => $cashTransaction->id,
                                    'debt_payment_id' => $debtPayment->id,
                                    'update_result' => $updated,
                                    'voided_at_after_update' => $cashTransaction->voided_at,
                                ]);
                            }
                        }
                    } else {
                        Log::warning("Cash transaction not found for debt payment", [
                            'cash_transaction_id' => $debtPayment->cash_transaction_id,
                            'debt_payment_id' => $debtPayment->id,
                        ]);
                    }
                } else {
                    Log::warning("Debt payment has no cash_transaction_id", [
                        'debt_payment_id' => $debtPayment->id,
                    ]);
                }

                // Delete proof of payment file if exists
                if ($debtPayment->proof_of_payment) {
                    try {
                        Storage::disk('public')->delete($debtPayment->proof_of_payment);
                        Log::info("Proof of payment file deleted", [
                            'file' => $debtPayment->proof_of_payment,
                        ]);
                    } catch (\Exception $e) {
                        Log::warning("Failed to delete proof of payment file", [
                            'file' => $debtPayment->proof_of_payment,
                            'error' => $e->getMessage(),
                        ]);
                    }
                }

                // Reduce paid_amount from debt
                $debt->paid_amount = max(0, $debt->paid_amount - $debtPayment->amount);
                $debt->updateStatus(); // This will update status based on new paid_amount
                $debt->save();

                Log::info("Debt payment deleted and paid_amount reduced", [
                    'debt_payment_id' => $debtPayment->id,
                    'debt_id' => $debt->id,
                    'payment_amount' => $debtPayment->amount,
                    'new_paid_amount' => $debt->paid_amount,
                    'new_status' => $debt->status,
                ]);
            } catch (\Exception $e) {
                Log::error("Error handling debt payment deletion", [
                    'debt_payment_id' => $debtPayment->id,
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString(),
                ]);
                throw $e; // Re-throw to prevent deletion if there's an error
            }
        });
    }
}
