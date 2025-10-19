<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\Payments\PaymentGatewayManager;
use Illuminate\Support\Facades\Log;

class PaymentWebhookController extends Controller
{
    public function handle(string $gateway, Request $request, PaymentGatewayManager $manager)
    {
        $contentType = strtolower((string) $request->header('Content-Type'));
        if (str_contains($contentType, 'application/json')) {
            $raw = (string) $request->getContent();
            if ($raw !== '') {
                $decoded = json_decode($raw, true);
                if (is_array($decoded)) {
                    $request->merge($decoded);
                } else {
                    // Tolerant parse: normalize unquoted keys/values e.g. {external_id:INV-1,status:PAID}
                    if (preg_match('/^\s*\{.*\}\s*$/s', $raw)) {
                        $normalized = preg_replace('/([\{,]\s*)([A-Za-z0-9_\-]+)\s*:/', '$1"$2":', $raw);
                        $try1 = json_decode($normalized, true);
                        if (is_array($try1)) {
                            $request->merge($try1);
                        } else {
                            $normalizedValues = preg_replace_callback(
                                '/:\s*([^,}\s][^,}]*)\s*([,}])/',
                                function ($m) {
                                    $val = trim($m[1]);
                                    if (preg_match('/^\".*\"$/', $val)) return ':' . $val . $m[2];
                                    if (preg_match('/^-?\d+(?:\.\d+)?$/', $val)) return ':' . $val . $m[2];
                                    if (in_array(strtolower($val), ['true','false','null'], true)) return ':' . strtolower($val) . $m[2];
                                    return ': "' . $val . '"' . $m[2];
                                },
                                $normalized
                            );
                            $try2 = json_decode($normalizedValues, true);
                            if (is_array($try2)) {
                                $request->merge($try2);
                            }
                        }
                    }
                }
            }
        }

        // Debug mode (non-production only)
        $debugRequested = $request->header('X-Debug') || $request->boolean('debug');
        $isNonProd = config('app.env') !== 'production';
        if ($isNonProd && $debugRequested) {
            return response()->json([
                'gateway' => $gateway,
                'content_type' => $contentType,
                'all' => $request->all(),
                'json_all' => $request->json()->all(),
                'raw' => $request->getContent(),
                'headers' => $request->headers->all(),
            ]);
        }

        if ($isNonProd) {
            try {
                Log::info('webhook.debug.before_handle', [
                    'gateway' => $gateway,
                    'content_type' => $contentType,
                    'all' => $request->all(),
                    'json_all' => $request->json()->all(),
                    'raw_len' => strlen((string) $request->getContent()),
                    'raw_head' => substr((string) $request->getContent(), 0, 200),
                    'headers' => $request->headers->all(),
                ]);
            } catch (\Throwable $e) {
                // ignore
            }
        }

        return $manager->handleWebhook($gateway, $request);
    }
}