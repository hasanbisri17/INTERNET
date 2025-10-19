<?php

namespace App\Services\Payments;

use App\Models\CashTransaction;
use App\Models\Payment;
use App\Models\TransactionCategory;
use App\Services\Payments\Adapters\DokuAdapter;
use App\Services\Payments\Adapters\DuitkuAdapter;
use App\Services\Payments\Adapters\MidtransAdapter;
use App\Services\Payments\Adapters\TripayAdapter;
use App\Services\Payments\Adapters\XenditAdapter;
use App\Services\Payments\Contracts\PaymentGatewayAdapterInterface;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

class PaymentGatewayManager
{
    /** @var array<string, PaymentGatewayAdapterInterface> */
    protected array $adapters = [];

    public function __construct(?array $adapters = null)
    {
        if ($adapters) {
            foreach ($adapters as $adapter) {
                $this->registerAdapter($adapter);
            }
        } else {
            $this->registerAdapter(new XenditAdapter());
            $this->registerAdapter(new MidtransAdapter());
            $this->registerAdapter(new DokuAdapter());
            $this->registerAdapter(new DuitkuAdapter());
            $this->registerAdapter(new TripayAdapter());
        }
    }

    public function registerAdapter(PaymentGatewayAdapterInterface $adapter): void
    {
        $this->adapters[strtolower($adapter->getName())] = $adapter;
    }

    public function getAdapter(string $name): ?PaymentGatewayAdapterInterface
    {
        $key = strtolower(trim($name));
        return $this->adapters[$key] ?? null;
    }

    public function cancel(Payment $payment): bool
    {
        $gateway = (string) ($payment->gateway ?? '');
        $adapter = $this->getAdapter($gateway);
        if (!$adapter) {
            return false;
        }

        try {
            return (bool) $adapter->cancel($payment);
        } catch (\Throwable $e) {
            Log::warning('Gateway cancel failed', [
                'gateway' => $gateway,
                'payment_id' => $payment->id,
                'error' => $e->getMessage(),
            ]);
            return false;
        }
    }

    public function handleWebhook(string $gateway, Request $request): JsonResponse
    {
        $adapter = $this->getAdapter($gateway);
        if (!$adapter) {
            return response()->json(['ok' => false, 'message' => 'Unknown gateway'], 404);
        }

        try {
            $parsed = $adapter->parseWebhook($request);
        } catch (\Throwable $e) {
            Log::error('Webhook parse error', [
                'gateway' => $gateway,
                'error' => $e->getMessage(),
            ]);
            return response()->json(['ok' => false, 'message' => 'Invalid payload'], 422);
        }

        $invoice = (string) ($parsed['invoice'] ?? '');
        if ($invoice === '') {
            return response()->json(['ok' => false, 'message' => 'Missing invoice'], 422);
        }

        $payment = Payment::where('invoice_number', $invoice)->first();
        if (!$payment) {
            return response()->json(['ok' => false, 'message' => 'Payment not found'], 404);
        }

        DB::transaction(function () use ($gateway, $payment, $parsed) {
            // persist latest provider info/payload
            $payment->gateway = strtolower($gateway);
            if (!empty($parsed['gateway_ref'])) {
                $payment->gateway_ref = $parsed['gateway_ref'];
            }
            if (array_key_exists('payload', $parsed)) {
                $payment->payload = $parsed['payload'];
            }

            $status = strtolower((string) ($parsed['status'] ?? ''));

            if ($status === 'paid') {
                if ($payment->status !== 'canceled') {
                    $payment->status = 'paid';
                    if (!$payment->payment_date) {
                        $payment->payment_date = now();
                    }
                }
                $payment->save();

                // create cash income once
                if (!CashTransaction::where('payment_id', $payment->id)->exists()) {
                    $category = TransactionCategory::where('type', 'income')
                        ->where('name', 'Pembayaran Internet')
                        ->first() ?: TransactionCategory::where('type', 'income')->first();

                    CashTransaction::create([
                        'date' => now(),
                        'type' => 'income',
                        'amount' => $payment->amount,
                        'description' => 'Pembayaran invoice ' . $payment->invoice_number,
                        'category_id' => $category?->id,
                        'payment_id' => $payment->id,
                    ]);
                }
            } elseif ($status === 'canceled') {
                $payment->status = 'canceled';
                if (!$payment->canceled_at) {
                    $payment->canceled_at = now();
                }
                if (!$payment->canceled_reason) {
                    $payment->canceled_reason = 'Canceled by gateway webhook';
                }
                $payment->save();

                if (Schema::hasColumn('cash_transactions', 'voided_at')) {
                    $related = CashTransaction::where('payment_id', $payment->id)->get();
                    foreach ($related as $trx) {
                        $trx->update([
                            'voided_at' => now(),
                            'voided_by' => Auth::id(),
                            'void_reason' => 'Invoice dibatalkan via webhook',
                        ]);
                    }
                }
            } elseif (in_array($status, ['failed', 'expired', 'pending'], true)) {
                $payment->status = $status;
                $payment->save();
            } else {
                // unknown status: just store payload above
                $payment->save();
            }
        });

        return response()->json([
            'ok' => true,
            'invoice' => $payment->invoice_number,
            'status' => $payment->status,
        ]);
    }
}