<?php

namespace App\Services\Payments\Adapters;

use App\Models\Payment;
use Illuminate\Http\Request;
use App\Services\Payments\Contracts\PaymentGatewayAdapterInterface;

class XenditAdapter implements PaymentGatewayAdapterInterface
{
    public function getName(): string
    {
        return 'xendit';
    }

    public function cancel(Payment $payment): bool
    {
        // Stub: In real integration, call Xendit invoice/charge cancel API here.
        return true;
    }

    public function parseWebhook(Request $request): array
    {
        // Start with whatever Laravel parsed
        $data = $request->all();

        // If Laravel populated Request::json(), prefer merging it
        $jsonAll = $request->json()->all();
        if (!empty($jsonAll)) {
            $data = array_replace($data, $jsonAll);
        }

        // Try decoding raw body and merge
        $raw = $request->getContent();
        if (is_string($raw) && $raw !== '') {
            $decoded = json_decode($raw, true);
            if (is_array($decoded)) {
                $data = array_replace($data, $decoded);
            }
        }

        // Handle edge: body parsed as a single key that is actually JSON text
        if (count($data) === 1) {
            $onlyKey = array_key_first($data);
            if (is_string($onlyKey) && str_starts_with(trim($onlyKey), '{')) {
                $maybe = json_decode($onlyKey, true);
                if (is_array($maybe)) {
                    $data = $maybe;
                }
            }
        }

        // Sometimes providers nest payload under 'data'
        $flat = array_merge($data, $data['data'] ?? []);

        $invoice = $this->first($flat, ['external_id', 'invoice_number', 'order_id', 'reference']);
        $gatewayRef = $this->first($flat, ['id', 'invoice_id', 'payment_id', 'reference']);

        $statusRaw = strtolower((string) ($flat['status'] ?? $flat['event'] ?? ''));
        $status = 'pending';
        if (str_contains($statusRaw, 'paid')) $status = 'paid';
        elseif (str_contains($statusRaw, 'settlement')) $status = 'paid';
        elseif (str_contains($statusRaw, 'expire')) $status = 'expired';
        elseif (str_contains($statusRaw, 'cancel')) $status = 'canceled';
        elseif (str_contains($statusRaw, 'fail')) $status = 'failed';

        return [
            'invoice' => (string) ($invoice ?? ''),
            'status' => $status,
            'gateway_ref' => $gatewayRef ? (string) $gatewayRef : null,
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