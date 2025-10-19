<?php

namespace App\Services;

use App\Models\DunningConfig;
use App\Models\DunningSchedule;
use App\Models\DunningStep;
use App\Models\Payment;
use App\Models\User;
use App\Services\WhatsAppService;
use Carbon\Carbon;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class DunningService
{
    protected $whatsAppService;

    public function __construct(WhatsAppService $whatsAppService)
    {
        $this->whatsAppService = $whatsAppService;
    }

    /**
     * Proses semua langkah dunning yang perlu dijalankan hari ini
     */
    public function processDunningSteps()
    {
        $today = Carbon::now();
        
        // Ambil semua pembayaran yang belum lunas
        $unpaidPayments = Payment::where('status', 'unpaid')
            ->where('due_date', '<', $today)
            ->get();
            
        foreach ($unpaidPayments as $payment) {
            $this->processDunningForPayment($payment);
        }
    }
    
    /**
     * Proses dunning untuk satu pembayaran
     */
    public function processDunningForPayment(Payment $payment)
    {
        $today = Carbon::now();
        $daysOverdue = $today->diffInDays($payment->due_date);
        
        // Cari jadwal dunning yang aktif
        $schedule = DunningSchedule::where('is_active', true)->first();
        
        if (!$schedule) {
            Log::info('Tidak ada jadwal dunning yang aktif');
            return;
        }
        
        $config = $schedule->config;
        
        // Cek setiap langkah dalam konfigurasi
        foreach ($config['steps'] as $stepConfig) {
            if ($daysOverdue >= $stepConfig['days_after_due']) {
                // Cek apakah langkah ini sudah dijalankan
                $existingStep = DunningStep::where('payment_id', $payment->id)
                    ->where('step_name', $stepConfig['name'])
                    ->first();
                    
                if (!$existingStep) {
                    // Buat langkah baru
                    $step = new DunningStep([
                        'dunning_schedule_id' => $schedule->id,
                        'payment_id' => $payment->id,
                        'step_name' => $stepConfig['name'],
                        'days_after_due' => $stepConfig['days_after_due'],
                        'action_type' => $stepConfig['action_type'],
                        'action_config' => $stepConfig['action_config'] ?? null,
                        'status' => 'pending'
                    ]);
                    
                    $step->save();
                    
                    // Jalankan aksi sesuai tipe
                    $this->executeAction($step);
                }
            }
        }
    }
    
    /**
     * Jalankan aksi sesuai tipe
     */
    protected function executeAction(DunningStep $step)
    {
        switch ($step->action_type) {
            case 'notification':
                $this->sendNotification($step);
                break;
                
            case 'penalty':
                $this->applyPenalty($step);
                break;
                
            case 'suspend':
                $this->suspendService($step);
                break;
                
            default:
                Log::warning("Tipe aksi tidak dikenal: {$step->action_type}");
                break;
        }
        
        // Update status langkah
        $step->status = 'executed';
        $step->executed_at = Carbon::now();
        $step->save();
    }
    
    /**
     * Kirim notifikasi
     */
    protected function sendNotification(DunningStep $step)
    {
        $payment = $step->payment;
        $customer = $payment->customer;
        $config = $step->action_config;
        
        if (isset($config['whatsapp_template'])) {
            $templateName = $config['whatsapp_template'];
            $parameters = [
                'customer_name' => $customer->name,
                'invoice_number' => $payment->invoice_number,
                'due_date' => $payment->due_date->format('d-m-Y'),
                'amount' => number_format($payment->amount, 0, ',', '.'),
                'days_overdue' => Carbon::now()->diffInDays($payment->due_date)
            ];
            
            $this->whatsAppService->sendTemplateMessage(
                $customer->phone,
                $templateName,
                $parameters
            );
            
            Log::info("Notifikasi WhatsApp dikirim ke {$customer->name} untuk invoice {$payment->invoice_number}");
        }
    }
    
    /**
     * Terapkan denda
     */
    protected function applyPenalty(DunningStep $step)
    {
        $payment = $step->payment;
        $config = $step->action_config;
        
        if (isset($config['penalty_amount'])) {
            $penaltyAmount = $config['penalty_amount'];
            
            // Tambahkan denda ke pembayaran
            $payment->penalty_amount = ($payment->penalty_amount ?? 0) + $penaltyAmount;
            $payment->total_amount = $payment->amount + $payment->penalty_amount;
            $payment->save();
            
            Log::info("Denda Rp " . number_format($penaltyAmount, 0, ',', '.') . " diterapkan pada invoice {$payment->invoice_number}");
        }
    }
    
    /**
     * Suspend layanan
     */
    protected function suspendService(DunningStep $step)
    {
        $payment = $step->payment;
        $customer = $payment->customer;
        
        // Implementasi suspend layanan melalui integrasi AAA
        // Ini akan diimplementasikan di AAAService
        
        Log::info("Layanan untuk {$customer->name} disuspend karena invoice {$payment->invoice_number} belum dibayar");
    }
    
    /**
     * Process dunning based on DunningConfig (for n8n integration)
     */
    public function processDunningWithConfig()
    {
        $today = Carbon::now();
        
        // Get active dunning config
        $config = DunningConfig::where('is_active', true)->first();
        
        if (!$config) {
            Log::info('Tidak ada konfigurasi dunning yang aktif');
            return [
                'success' => false,
                'message' => 'Tidak ada konfigurasi dunning yang aktif',
                'total_processed' => 0,
                'total_triggered' => 0,
                'total_notified' => 0,
            ];
        }
        
        // Find all unpaid payments that are overdue
        $unpaidPayments = Payment::where('status', 'pending')
            ->where('due_date', '<', $today)
            ->with('customer')
            ->get();
        
        $totalProcessed = 0;
        $totalTriggered = 0;
        $totalNotified = 0;
        
        foreach ($unpaidPayments as $payment) {
            $daysOverdue = $today->diffInDays($payment->due_date);
            
            // Check if should trigger n8n webhook & send WhatsApp notification
            if ($config->n8n_enabled && $daysOverdue >= $config->n8n_trigger_after_days) {
                
                // Check if already suspended (to avoid duplicate notifications)
                $alreadySuspended = \DB::table('dunning_suspensions')
                    ->where('payment_id', $payment->id)
                    ->where('suspended_at', '>=', $today->subDays(1))
                    ->exists();
                
                if (!$alreadySuspended) {
                    // 1. Trigger n8n webhook for suspend
                    $result = $this->triggerN8nWebhook($payment, $config, 'suspend');
                    
                    if ($result['success']) {
                        $totalTriggered++;
                        Log::info("n8n webhook triggered for customer {$payment->customer->name} - Invoice: {$payment->invoice_number}");
                        
                        // 2. Send WhatsApp notification to customer
                        try {
                            $whatsAppService = app(WhatsAppService::class);
                            
                            // Send service suspended notification
                            $whatsAppService->sendBillingNotification($payment, 'suspended', false);
                            
                            $totalNotified++;
                            Log::info("Suspension notification sent for {$payment->invoice_number}");
                        } catch (\Exception $e) {
                            Log::error("Failed to send suspension WhatsApp for {$payment->invoice_number}: {$e->getMessage()}");
                        }
                        
                        // 3. Record suspension to prevent duplicate
                        \DB::table('dunning_suspensions')->insert([
                            'payment_id' => $payment->id,
                            'suspended_at' => $today,
                            'days_overdue' => $daysOverdue,
                            'created_at' => $today,
                        ]);
                        
                        // 4. Log activity to activity log
                        activity('dunning')
                            ->performedOn($payment)
                            ->withProperties([
                                'action' => 'suspend',
                                'customer_id' => $payment->customer->id,
                                'customer_name' => $payment->customer->name,
                                'invoice_number' => $payment->invoice_number,
                                'days_overdue' => $daysOverdue,
                                'config_name' => $config->name,
                            ])
                            ->log("Layanan {$payment->customer->name} ditangguhkan karena tunggakan {$daysOverdue} hari (Invoice: {$payment->invoice_number})");
                        
                        // 5. Send database notification to admins
                        $this->sendSuspendNotification($payment, $daysOverdue);
                    }
                }
            }
            
            $totalProcessed++;
        }
        
        return [
            'success' => true,
            'message' => 'Dunning process completed',
            'total_processed' => $totalProcessed,
            'total_triggered' => $totalTriggered,
            'total_notified' => $totalNotified,
        ];
    }
    
    /**
     * Trigger n8n webhook for suspend/unsuspend action
     */
    public function triggerN8nWebhook(Payment $payment, DunningConfig $config, string $action = 'suspend')
    {
        if (!$config->n8n_enabled || !$config->n8n_webhook_url) {
            return [
                'success' => false,
                'message' => 'n8n integration not enabled or webhook URL not configured',
            ];
        }
        
        $customer = $payment->customer;
        $today = Carbon::now();
        $daysOverdue = $today->diffInDays($payment->due_date);
        
        // Build payload
        $payload = [
            'action' => $action,
            'customer_id' => $customer->id,
            'customer_name' => $customer->name,
            'customer_phone' => $customer->phone,
            'customer_email' => $customer->email,
            'customer_address' => $customer->address,
            'invoice_number' => $payment->invoice_number,
            'invoice_amount' => $payment->amount,
            'due_date' => $payment->due_date->format('Y-m-d'),
            'days_overdue' => $daysOverdue,
            'payment_id' => $payment->id,
            'triggered_at' => $today->toIso8601String(),
        ];
        
        // Prepare headers (simplified - no custom headers)
        $headers = [
            'Content-Type' => 'application/json',
            'Accept' => 'application/json',
        ];
        
        try {
            // Send HTTP request (always POST method)
            $response = Http::withHeaders($headers)
                ->timeout(30)
                ->post($config->n8n_webhook_url, $payload);
            
            if ($response->successful()) {
                Log::info("n8n webhook triggered successfully", [
                    'action' => $action,
                    'customer' => $customer->name,
                    'invoice' => $payment->invoice_number,
                    'status_code' => $response->status(),
                ]);
                
                return [
                    'success' => true,
                    'message' => 'Webhook triggered successfully',
                    'status_code' => $response->status(),
                    'response' => $response->json(),
                ];
            } else {
                Log::error("n8n webhook failed", [
                    'action' => $action,
                    'customer' => $customer->name,
                    'invoice' => $payment->invoice_number,
                    'status_code' => $response->status(),
                    'error' => $response->body(),
                ]);
                
                return [
                    'success' => false,
                    'message' => 'Webhook failed',
                    'status_code' => $response->status(),
                    'error' => $response->body(),
                ];
            }
        } catch (\Exception $e) {
            Log::error("n8n webhook exception", [
                'action' => $action,
                'customer' => $customer->name,
                'invoice' => $payment->invoice_number,
                'error' => $e->getMessage(),
            ]);
            
            return [
                'success' => false,
                'message' => 'Webhook exception: ' . $e->getMessage(),
            ];
        }
    }
    
    /**
     * Trigger unsuspend webhook when payment is received
     */
    public function triggerUnsuspendOnPayment(Payment $payment)
    {
        $config = DunningConfig::where('is_active', true)->first();
        
        if (!$config || !$config->n8n_enabled || !$config->n8n_auto_unsuspend) {
            return [
                'success' => false,
                'message' => 'Auto unsuspend not enabled',
            ];
        }
        
        $result = $this->triggerN8nWebhook($payment, $config, 'unsuspend');
        
        // Log activity if successful
        if ($result['success']) {
            activity('dunning')
                ->performedOn($payment)
                ->withProperties([
                    'action' => 'unsuspend',
                    'customer_id' => $payment->customer->id,
                    'customer_name' => $payment->customer->name,
                    'invoice_number' => $payment->invoice_number,
                    'config_name' => $config->name,
                ])
                ->log("Layanan {$payment->customer->name} diaktifkan kembali setelah pembayaran (Invoice: {$payment->invoice_number})");
            
            // Send database notification
            $this->sendUnsuspendNotification($payment);
        }
        
        return $result;
    }
    
    /**
     * Send database notification when customer is suspended
     */
    protected function sendSuspendNotification(Payment $payment, int $daysOverdue): void
    {
        try {
            $adminUsers = User::where('is_admin', true)->get();
            
            Notification::make()
                ->title('⚠️ Layanan Ditangguhkan')
                ->body("Layanan {$payment->customer->name} ditangguhkan karena tunggakan {$daysOverdue} hari. Invoice: {$payment->invoice_number} (Rp " . number_format($payment->amount, 0, ',', '.') . ")")
                ->warning()
                ->icon('heroicon-o-exclamation-triangle')
                ->actions([
                    \Filament\Notifications\Actions\Action::make('view')
                        ->label('Lihat Tagihan')
                        ->url(route('filament.admin.resources.payments.edit', $payment))
                        ->button(),
                    \Filament\Notifications\Actions\Action::make('customer')
                        ->label('Lihat Customer')
                        ->url(route('filament.admin.resources.customers.edit', $payment->customer))
                        ->button()
                        ->color('gray'),
                ])
                ->sendToDatabase($adminUsers);
        } catch (\Exception $e) {
            Log::error("Failed to send suspend notification: {$e->getMessage()}");
        }
    }
    
    /**
     * Send database notification when customer is unsuspended
     */
    protected function sendUnsuspendNotification(Payment $payment): void
    {
        try {
            $adminUsers = User::where('is_admin', true)->get();
            
            Notification::make()
                ->title('✅ Layanan Diaktifkan Kembali')
                ->body("Layanan {$payment->customer->name} telah diaktifkan kembali setelah pembayaran {$payment->invoice_number}.")
                ->success()
                ->icon('heroicon-o-check-badge')
                ->actions([
                    \Filament\Notifications\Actions\Action::make('view')
                        ->label('Lihat Detail')
                        ->url(route('filament.admin.resources.payments.edit', $payment))
                        ->button(),
                ])
                ->sendToDatabase($adminUsers);
        } catch (\Exception $e) {
            Log::error("Failed to send unsuspend notification: {$e->getMessage()}");
        }
    }
    
    /**
     * Test n8n webhook with real customer data
     */
    public function testN8nWebhook(DunningConfig $config)
    {
        if (!$config->n8n_enabled || !$config->n8n_webhook_url) {
            return [
                'success' => false,
                'message' => 'n8n integration not enabled or webhook URL not configured',
            ];
        }
        
        $today = Carbon::now();
        
        // Try to get a real overdue payment first (for more realistic test)
        $payment = Payment::where('status', 'pending')
            ->where('due_date', '<', $today)
            ->with('customer')
            ->first();
        
        // If no overdue payment, get any pending payment
        if (!$payment) {
            $payment = Payment::where('status', 'pending')
                ->with('customer')
                ->orderBy('due_date', 'desc')
                ->first();
        }
        
        // If still no payment, return error
        if (!$payment || !$payment->customer) {
            return [
                'success' => false,
                'message' => 'No payment data available for testing. Please create at least one payment record first.',
            ];
        }
        
        $customer = $payment->customer;
        $daysOverdue = $today->diffInDays($payment->due_date);
        
        // Build test payload with REAL customer data
        $payload = [
            'action' => 'test',
            'test_mode' => true,
            'customer_id' => $customer->id,
            'customer_name' => $customer->name,
            'customer_phone' => $customer->phone,
            'customer_email' => $customer->email,
            'customer_address' => $customer->address,
            'invoice_number' => $payment->invoice_number,
            'invoice_amount' => $payment->amount,
            'due_date' => $payment->due_date->format('Y-m-d'),
            'days_overdue' => $daysOverdue,
            'payment_id' => $payment->id,
            'triggered_at' => $today->toIso8601String(),
            'message' => 'This is a TEST webhook with REAL customer data for debugging n8n workflow. No action will be taken.',
        ];
        
        // Prepare headers
        $headers = [
            'Content-Type' => 'application/json',
            'Accept' => 'application/json',
            'X-Test-Mode' => 'true',
        ];
        
        try {
            // Send HTTP request
            $response = Http::withHeaders($headers)
                ->timeout(30)
                ->post($config->n8n_webhook_url, $payload);
            
            if ($response->successful()) {
                Log::info("n8n test webhook successful", [
                    'config' => $config->name,
                    'customer' => $customer->name,
                    'invoice' => $payment->invoice_number,
                    'status_code' => $response->status(),
                ]);
                
                return [
                    'success' => true,
                    'message' => "Test webhook sent with real data: {$customer->name} - {$payment->invoice_number}",
                    'status_code' => $response->status(),
                    'response' => $response->json(),
                ];
            } else {
                Log::error("n8n test webhook failed", [
                    'config' => $config->name,
                    'status_code' => $response->status(),
                    'error' => $response->body(),
                ]);
                
                return [
                    'success' => false,
                    'message' => "HTTP {$response->status()}: {$response->body()}",
                    'status_code' => $response->status(),
                ];
            }
        } catch (\Exception $e) {
            Log::error("n8n test webhook exception", [
                'config' => $config->name,
                'error' => $e->getMessage(),
            ]);
            
            return [
                'success' => false,
                'message' => 'Connection error: ' . $e->getMessage(),
            ];
        }
    }
}