<?php

namespace App\Services\Payments\Adapters;

use App\Models\Payment;
use Illuminate\Http\Request;
use App\Services\Payments\Contracts\PaymentGatewayAdapterInterface;

class MidtransAdapter implements PaymentGatewayAdapterInterface
{
    public function getName(): string
    {
        return 'midtrans';
    }

    public function cancel(Payment $payment): bool
    {
        // Stub: call Midtrans cancel API by order_id if needed
        return true;
    }

    public function parseWebhook(Request $request): array
    {
        $data = $request->all();
        if (empty($data)) {
            $decoded = json_decode($request->getContent(), true);
            if (is_array($decoded)) {
                $data = $decoded;
            }
        }
        $invoice = $this->first($data, ['order_id', 'invoice', 'invoice_number']);
        $gatewayRef = $this->first($data, ['transaction_id']);

        $trxStatus = strtolower((string) ($data['transaction_status'] ?? ''));
        $status = match ($trxStatus) {
            'capture', 'settlement' => 'paid',
            'cancel' => 'canceled',
            'deny', 'failure' => 'failed',
            'expire', 'expired' => 'expired',
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