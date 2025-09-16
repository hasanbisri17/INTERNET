<?php

namespace App\Services\Payments\Adapters;

use App\Models\Payment;
use Illuminate\Http\Request;
use App\Services\Payments\Contracts\PaymentGatewayAdapterInterface;

class TripayAdapter implements PaymentGatewayAdapterInterface
{
    public function getName(): string
    {
        return 'tripay';
    }

    public function cancel(Payment $payment): bool
    {
        // Stub: call Tripay API if required
        return true;
    }

    public function parseWebhook(Request $request): array
    {
        $data = $request->all();
        $invoice = $this->first($data, ['merchant_ref', 'invoice', 'invoice_number', 'order_id']);
        $gatewayRef = $this->first($data, ['reference', 'transaction_id', 'trx_id']);

        $raw = strtolower((string) ($data['status'] ?? ''));
        $status = match ($raw) {
            'success', 'paid', 'settlement' => 'paid',
            'expired', 'expire' => 'expired',
            'failed', 'deny' => 'failed',
            'canceled', 'cancel' => 'canceled',
            default => 'pending',
        };

        return [
            'invoice' => (string) $invoice,
            'status' => $status,
            'gateway_ref' => $gatewayRef,
            'payload' => $data,
        ];
    }

    private function first(array $data, array $keys): ?string
    {
        foreach ($keys as $k) {
            if (!isset($data[$k])) continue;
            $v = $data[$k];
            if ($v === null || $v === '') continue;
            return is_scalar($v) ? (string) $v : null;
        }
        return null;
    }
}