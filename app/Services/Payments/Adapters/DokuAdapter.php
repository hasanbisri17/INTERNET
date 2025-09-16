<?php

namespace App\Services\Payments\Adapters;

use App\Models\Payment;
use Illuminate\Http\Request;
use App\Services\Payments\Contracts\PaymentGatewayAdapterInterface;

class DokuAdapter implements PaymentGatewayAdapterInterface
{
    public function getName(): string
    {
        return 'doku';
    }

    public function cancel(Payment $payment): bool
    {
        // Stub: call DOKU API if required
        return true;
    }

    public function parseWebhook(Request $request): array
    {
        $data = $request->all();
        $invoice = $this->first($data, ['invoice', 'invoice_number', 'order_id']);
        $gatewayRef = $this->first($data, ['transaction_id', 'reference']);

        $raw = strtolower((string) ($data['status'] ?? $data['trxstatus'] ?? ''));
        $status = 'pending';
        if (str_contains($raw, 'success') || $raw === '00') $status = 'paid';
        elseif (str_contains($raw, 'expire')) $status = 'expired';
        elseif (str_contains($raw, 'cancel')) $status = 'canceled';
        elseif (str_contains($raw, 'fail') || str_contains($raw, 'deny')) $status = 'failed';

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