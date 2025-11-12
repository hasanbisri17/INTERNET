<?php

namespace App\Observers;

use App\Models\ReceivablePayment;
use App\Models\CashTransaction;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class ReceivablePaymentObserver
{
    /**
     * Handle the ReceivablePayment "deleting" event.
     * This runs BEFORE the payment is deleted, so we can still access the relationship.
     */
    public function deleting(ReceivablePayment $receivablePayment): void
    {
        DB::transaction(function () use ($receivablePayment) {
            try {
                // Get the receivable
                $receivable = $receivablePayment->receivable;
                
                if (!$receivable) {
                    Log::warning("ReceivablePayment deleted but receivable not found", [
                        'receivable_payment_id' => $receivablePayment->id,
                    ]);
                    return;
                }

                // Void cash transaction (income) yang dibuat saat pembayaran
                // Income ini menambah KAS, jadi saat payment dihapus, income harus di-void
                Log::info("Attempting to void cash transaction for receivable payment", [
                    'receivable_payment_id' => $receivablePayment->id,
                    'cash_transaction_id' => $receivablePayment->cash_transaction_id,
                ]);
                
                if ($receivablePayment->cash_transaction_id) {
                    $cashTransaction = CashTransaction::find($receivablePayment->cash_transaction_id);
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
                            $voidReason = 'Pembayaran piutang dihapus - KAS dikembalikan';
                            
                            $updated = $cashTransaction->update([
                                'voided_at' => $voidedAt,
                                'voided_by' => $voidedBy,
                                'void_reason' => $voidReason,
                            ]);
                            
                            // Refresh to verify the update
                            $cashTransaction->refresh();
                            
                            if ($updated && $cashTransaction->voided_at) {
                                Log::info("Cash transaction voided successfully for deleted receivable payment", [
                                    'cash_transaction_id' => $cashTransaction->id,
                                    'receivable_payment_id' => $receivablePayment->id,
                                    'amount' => $cashTransaction->amount,
                                    'type' => $cashTransaction->type,
                                    'voided_at' => $cashTransaction->voided_at,
                                ]);
                            } else {
                                Log::error("Failed to void cash transaction - update returned true but voided_at is still null", [
                                    'cash_transaction_id' => $cashTransaction->id,
                                    'receivable_payment_id' => $receivablePayment->id,
                                    'update_result' => $updated,
                                    'voided_at_after_update' => $cashTransaction->voided_at,
                                ]);
                            }
                        }
                    } else {
                        Log::warning("Cash transaction not found for receivable payment", [
                            'cash_transaction_id' => $receivablePayment->cash_transaction_id,
                            'receivable_payment_id' => $receivablePayment->id,
                        ]);
                    }
                } else {
                    Log::warning("Receivable payment has no cash_transaction_id", [
                        'receivable_payment_id' => $receivablePayment->id,
                    ]);
                }

                // Delete proof of payment file if exists
                if ($receivablePayment->proof_of_payment) {
                    try {
                        Storage::disk('public')->delete($receivablePayment->proof_of_payment);
                        Log::info("Proof of payment file deleted", [
                            'file' => $receivablePayment->proof_of_payment,
                        ]);
                    } catch (\Exception $e) {
                        Log::warning("Failed to delete proof of payment file", [
                            'file' => $receivablePayment->proof_of_payment,
                            'error' => $e->getMessage(),
                        ]);
                    }
                }

                // Reduce paid_amount from receivable
                $receivable->paid_amount = max(0, $receivable->paid_amount - $receivablePayment->amount);
                $receivable->updateStatus(); // This will update status based on new paid_amount
                $receivable->save();

                Log::info("Receivable payment deleted and paid_amount reduced", [
                    'receivable_payment_id' => $receivablePayment->id,
                    'receivable_id' => $receivable->id,
                    'payment_amount' => $receivablePayment->amount,
                    'new_paid_amount' => $receivable->paid_amount,
                    'new_status' => $receivable->status,
                ]);
            } catch (\Exception $e) {
                Log::error("Error handling receivable payment deletion", [
                    'receivable_payment_id' => $receivablePayment->id,
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString(),
                ]);
                throw $e; // Re-throw to prevent deletion if there's an error
            }
        });
    }
}
