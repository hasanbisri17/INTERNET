<?php

namespace App\Services\Payments\Adapters;

use App\Models\Payment;
use Illuminate\Http\Request;
use App\Services\Payments\Contracts\PaymentGatewayAdapterInterface;

class DuitkuAdapter implements PaymentGatewayAdapterInterface
{
    public function getName(): string
    {
        return 'duitku';
    }

    public function cancel(Payment $payment): bool
    {
        // Stub: call Duitku cancel API if needed
        return true;
    }

    public function parseWebhook(Request $request): array
    {
        $data = $request->all();
        $invoice = $this->first($data, ['merchantOrderId', 'invoice', 'invoice_number', 'order_id']);
        $gatewayRef = $this->first($data, ['reference', 'merchantCode', 'transactionId']);

        $raw = strtolower((string) ($data['resultCode'] ?? $data['status'] ?? ''));
        $status = 'pending';
        if (in_array($raw, ['00', 'success', 'paid', 'settlement'], true)) {
            $status = 'paid';
        } elseif (in_array($raw, ['01', 'expired', 'expire'], true)) {
            $status = 'expired';
        } elseif (in_array($raw, ['02', 'canceled', 'cancel'], true)) {
            $status = 'canceled';
        } elseif (in_array($raw, ['03', 'failed', 'deny'], true)) {
            $status = 'failed';
        }

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