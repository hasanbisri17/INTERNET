<?php

namespace App\Services;

use App\Models\Customer;
use App\Models\MikrotikIpBinding;
use App\Services\WhatsAppService;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;
use Exception;

class SuspendViaIpBindingService
{
    protected WhatsAppService $whatsAppService;

    public function __construct()
    {
        $this->whatsAppService = new WhatsAppService();
    }

    /**
     * Suspend customer dengan mengubah IP Binding type dari bypassed ke regular
     *
     * @param Customer $customer
     * @return array
     */
    public function suspendCustomer(Customer $customer): array
    {
        try {
            // Get all IP Bindings milik customer dengan type 'bypassed'
            $ipBindings = $customer->ipBindings()
                ->where('type', 'bypassed')
                ->get();

            if ($ipBindings->isEmpty()) {
                return [
                    'success' => false,
                    'message' => 'Customer tidak memiliki IP Binding dengan type bypassed',
                ];
            }

            $suspended = 0;
            $errors = [];

            foreach ($ipBindings as $binding) {
                try {
                    // Update type dari bypassed ke regular
                    // Ini akan trigger observer yang auto-sync ke MikroTik
                    $binding->update([
                        'type' => 'regular',
                        'comment' => $this->updateComment($binding->comment, 'SUSPENDED'),
                    ]);

                    $suspended++;

                    Log::info("IP Binding suspended for customer", [
                        'customer_id' => $customer->id,
                        'customer_name' => $customer->name,
                        'ip_address' => $binding->address,
                        'from_type' => 'bypassed',
                        'to_type' => 'regular',
                    ]);
                } catch (Exception $e) {
                    $errors[] = "IP {$binding->address}: {$e->getMessage()}";
                    Log::error("Failed to suspend IP Binding", [
                        'customer_id' => $customer->id,
                        'ip_binding_id' => $binding->id,
                        'error' => $e->getMessage(),
                    ]);
                }
            }

            if ($suspended > 0) {
                // Update customer status
                $customer->update([
                    'is_isolated' => true,
                    'isolated_at' => now(),
                    'status' => 'suspended',
                ]);

                // Send WhatsApp notification
                $this->sendSuspendNotification($customer);

                // Log activity
                activity('suspend')
                    ->performedOn($customer)
                    ->withProperties([
                        'ip_bindings_suspended' => $suspended,
                        'method' => 'ip_binding',
                        'reason' => 'payment_overdue',
                    ])
                    ->log("Customer {$customer->name} suspended via IP Binding");

                return [
                    'success' => true,
                    'message' => "Customer suspended successfully. {$suspended} IP Binding(s) changed to regular.",
                    'suspended_count' => $suspended,
                    'errors' => $errors,
                ];
            }

            return [
                'success' => false,
                'message' => 'Failed to suspend customer',
                'errors' => $errors,
            ];

        } catch (Exception $e) {
            Log::error("Error suspending customer", [
                'customer_id' => $customer->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return [
                'success' => false,
                'message' => $e->getMessage(),
            ];
        }
    }

    /**
     * Unsuspend customer dengan mengubah IP Binding type dari regular ke bypassed
     *
     * @param Customer $customer
     * @return array
     */
    public function unsuspendCustomer(Customer $customer): array
    {
        try {
            // Get all IP Bindings milik customer dengan type 'regular'
            // Tidak perlu cek comment SUSPENDED karena bisa saja di-suspend manual
            $ipBindings = $customer->ipBindings()
                ->where('type', 'regular')
                ->get();

            if ($ipBindings->isEmpty()) {
                return [
                    'success' => false,
                    'message' => 'Customer tidak memiliki IP Binding yang perlu di-unsuspend',
                ];
            }

            $unsuspended = 0;
            $errors = [];

            foreach ($ipBindings as $binding) {
                try {
                    // Update type dari regular ke bypassed
                    // Ini akan trigger observer yang auto-sync ke MikroTik
                    $binding->update([
                        'type' => 'bypassed',
                        'comment' => $this->removeCommentMarker($binding->comment, 'SUSPENDED'),
                    ]);

                    $unsuspended++;

                    Log::info("IP Binding unsuspended for customer", [
                        'customer_id' => $customer->id,
                        'customer_name' => $customer->name,
                        'ip_address' => $binding->address,
                        'from_type' => 'regular',
                        'to_type' => 'bypassed',
                    ]);
                } catch (Exception $e) {
                    $errors[] = "IP {$binding->address}: {$e->getMessage()}";
                    Log::error("Failed to unsuspend IP Binding", [
                        'customer_id' => $customer->id,
                        'ip_binding_id' => $binding->id,
                        'error' => $e->getMessage(),
                    ]);
                }
            }

            if ($unsuspended > 0) {
                // Update customer status
                $customer->update([
                    'is_isolated' => false,
                    'isolated_at' => null,
                    'status' => 'active',
                ]);

                // Send WhatsApp notification
                $this->sendUnsuspendNotification($customer);

                // Log activity
                activity('unsuspend')
                    ->performedOn($customer)
                    ->withProperties([
                        'ip_bindings_unsuspended' => $unsuspended,
                        'method' => 'ip_binding',
                        'reason' => 'payment_received',
                    ])
                    ->log("Customer {$customer->name} unsuspended via IP Binding");

                return [
                    'success' => true,
                    'message' => "Customer unsuspended successfully. {$unsuspended} IP Binding(s) changed to bypassed.",
                    'unsuspended_count' => $unsuspended,
                    'errors' => $errors,
                ];
            }

            return [
                'success' => false,
                'message' => 'Failed to unsuspend customer',
                'errors' => $errors,
            ];

        } catch (Exception $e) {
            Log::error("Error unsuspending customer", [
                'customer_id' => $customer->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return [
                'success' => false,
                'message' => $e->getMessage(),
            ];
        }
    }

    /**
     * Get customers yang harus di-suspend (belum bayar sampai tanggal 25)
     *
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getCustomersToBeSuspended()
    {
        // Tanggal 26 = customer yang due_date sampai dengan tanggal 25 kemarin
        $yesterday = Carbon::yesterday(); // Tanggal 25 jika dijalankan tanggal 26

        return Customer::where('status', 'active')
            ->where('is_isolated', false)
            ->whereNotNull('due_date')
            ->whereDate('due_date', '<=', $yesterday)
            ->whereHas('ipBindings', function ($query) {
                $query->where('type', 'bypassed');
            })
            ->with(['ipBindings' => function ($query) {
                $query->where('type', 'bypassed');
            }])
            ->get();
    }

    /**
     * Process auto suspend untuk semua customer yang belum bayar
     *
     * @return array
     */
    public function processAutoSuspend(): array
    {
        $customers = $this->getCustomersToBeSuspended();

        if ($customers->isEmpty()) {
            return [
                'success' => true,
                'message' => 'Tidak ada customer yang perlu di-suspend',
                'suspended_count' => 0,
            ];
        }

        $suspended = 0;
        $failed = 0;
        $errors = [];

        foreach ($customers as $customer) {
            $result = $this->suspendCustomer($customer);

            if ($result['success']) {
                $suspended++;
            } else {
                $failed++;
                $errors[] = "{$customer->name}: {$result['message']}";
            }
        }

        $message = "Auto suspend completed. Suspended: {$suspended}, Failed: {$failed}";

        Log::info($message, [
            'suspended' => $suspended,
            'failed' => $failed,
            'errors' => $errors,
        ]);

        return [
            'success' => true,
            'message' => $message,
            'suspended_count' => $suspended,
            'failed_count' => $failed,
            'errors' => $errors,
        ];
    }

    /**
     * Send WhatsApp notification untuk suspend
     *
     * @param Customer $customer
     * @return void
     */
    protected function sendSuspendNotification(Customer $customer): void
    {
        try {
            if (!$customer->phone) {
                Log::warning("Customer {$customer->name} tidak memiliki nomor telepon");
                return;
            }

            // Get template from setting or use default
            $templateId = \App\Models\Setting::get('whatsapp_template_service_suspended');

            if ($templateId) {
                $template = \App\Models\WhatsAppTemplate::find($templateId);
            } else {
                $template = \App\Models\WhatsAppTemplate::findByType(\App\Models\WhatsAppTemplate::TYPE_SERVICE_SUSPENDED);
            }

            if (!$template) {
                // Fallback message if no template found
                $message = "Yth. {$customer->name},\n\n";
                $message .= "⛔ Layanan internet Anda telah dinonaktifkan karena pembayaran belum diterima hingga tanggal 25.\n\n";
                $message .= "📅 Due Date: " . ($customer->due_date ? $customer->due_date->format('d M Y') : '-') . "\n";
                $message .= "💰 Total Tagihan: " . ($customer->latestBill ? 'Rp ' . number_format($customer->latestBill->amount, 0, ',', '.') : '-') . "\n\n";
                $message .= "Silakan segera melakukan pembayaran untuk mengaktifkan kembali layanan Anda.\n\n";
                $message .= "Terima kasih.";
            } else {
                // Use template
                $amount = $customer->latestBill ? $customer->latestBill->amount : 0;
                
                $message = $template->formatMessage([
                    'customer_name' => $customer->name,
                    'due_date' => $customer->due_date ? $customer->due_date->format('d M Y') : '-',
                    'amount' => number_format($amount, 0, ',', '.'),
                ]);
            }

            $this->whatsAppService->sendMessage($customer->phone, $message);

            Log::info("Suspend notification sent to customer", [
                'customer_id' => $customer->id,
                'customer_name' => $customer->name,
                'phone' => $customer->phone,
                'template_used' => $template ? $template->name : 'fallback',
            ]);
        } catch (Exception $e) {
            Log::error("Failed to send suspend notification", [
                'customer_id' => $customer->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Send WhatsApp notification untuk unsuspend
     *
     * @param Customer $customer
     * @return void
     */
    protected function sendUnsuspendNotification(Customer $customer): void
    {
        try {
            if (!$customer->phone) {
                Log::warning("Customer {$customer->name} tidak memiliki nomor telepon");
                return;
            }

            // Get template from setting or use default
            $templateId = \App\Models\Setting::get('whatsapp_template_service_reactivated');

            if ($templateId) {
                $template = \App\Models\WhatsAppTemplate::find($templateId);
            } else {
                $template = \App\Models\WhatsAppTemplate::findByType(\App\Models\WhatsAppTemplate::TYPE_SERVICE_REACTIVATED);
            }

            if (!$template) {
                // Fallback message if no template found
                $message = "Yth. {$customer->name},\n\n";
                $message .= "✅ Layanan internet Anda telah diaktifkan kembali.\n\n";
                $message .= "Terima kasih atas pembayaran Anda. Selamat menikmati layanan internet kami.\n\n";
                $message .= "Jika ada kendala, silakan hubungi kami.\n\n";
                $message .= "Terima kasih.";
            } else {
                // Use template
                $message = $template->formatMessage([
                    'customer_name' => $customer->name,
                ]);
            }

            $this->whatsAppService->sendMessage($customer->phone, $message);

            Log::info("Unsuspend notification sent to customer", [
                'customer_id' => $customer->id,
                'customer_name' => $customer->name,
                'phone' => $customer->phone,
                'template_used' => $template ? $template->name : 'fallback',
            ]);
        } catch (Exception $e) {
            Log::error("Failed to send unsuspend notification", [
                'customer_id' => $customer->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Update comment dengan marker
     *
     * @param string|null $comment
     * @param string $marker
     * @return string
     */
    protected function updateComment(?string $comment, string $marker): string
    {
        if (!$comment) {
            return "[{$marker}]";
        }

        if (strpos($comment, "[{$marker}]") === false) {
            return "[{$marker}] " . $comment;
        }

        return $comment;
    }

    /**
     * Remove marker dari comment
     *
     * @param string|null $comment
     * @param string $marker
     * @return string|null
     */
    protected function removeCommentMarker(?string $comment, string $marker): ?string
    {
        if (!$comment) {
            return null;
        }

        $comment = str_replace("[{$marker}] ", '', $comment);
        $comment = str_replace("[{$marker}]", '', $comment);

        return trim($comment) ?: null;
    }
}

