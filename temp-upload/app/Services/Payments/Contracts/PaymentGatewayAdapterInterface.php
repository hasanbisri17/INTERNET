<?php

namespace App\Services\Payments\Contracts;

use App\Models\Payment;
use Illuminate\Http\Request;

interface PaymentGatewayAdapterInterface
{
    /**
     * Unique gateway name (lowercase, e.g. "xendit", "midtrans").
     */
    public function getName(): string;

    /**
     * Ask gateway to cancel/void the payment/invoice if supported.
     * Should return true if the remote cancellation request was accepted/succeeded,
     * or false if not supported/failed (manager will handle local state gracefully).
     */
    public function cancel(Payment $payment): bool;

    /**
     * Parse incoming webhook request and return a normalized payload.
     * Must return an associative array with keys:
     * - invoice (string): local invoice_number or any value we can map to Payment
     * - status  (string): one of: paid, canceled, failed, expired, pending
     * - gateway_ref (string|null): provider reference/transaction id
     * - payload (array): original payload to be stored for auditing
     */
    public function parseWebhook(Request $request): array;
}