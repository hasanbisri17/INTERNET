<?php

namespace App\Console\Commands;

use App\Models\Payment;
use App\Models\PaymentReminder;
use App\Models\PaymentReminderRule;
use App\Models\Setting;
use App\Models\User;
use App\Services\WhatsAppService;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class SendPaymentReminders extends Command
{
    protected $signature = 'whatsapp:payment-reminders 
                            {--dry-run : Preview what will be sent without actually sending}';

    protected $description = 'Send WhatsApp reminders for upcoming and overdue payments based on configured rules';

    protected WhatsAppService $whatsapp;

    public function __construct()
    {
        parent::__construct();
    }

    public function handle()
    {
        $this->whatsapp = new WhatsAppService();
        $isDryRun = $this->option('dry-run');

        $this->info('=== Payment Reminder System (Dynamic Rules) ===');
        $this->info('Date: ' . now()->format('d F Y H:i:s'));

        if ($isDryRun) {
            $this->warn('🔍 DRY RUN MODE - No messages will be sent');
        }

        // Get active reminder rules
        $activeRules = PaymentReminderRule::active()->ordered()->get();

        if ($activeRules->isEmpty()) {
            $this->warn('⚠️  No active reminder rules found!');
            $this->info('Please create reminder rules in: WhatsApp → Pengaturan Reminder');
            return Command::FAILURE;
        }

        $this->info("📋 Found {$activeRules->count()} active reminder rules");
        $this->newLine();

        $totalSent = 0;
        $totalFailed = 0;
        $today = Carbon::today();
        $unpaidCustomers = collect(); // Track unpaid customers for admin report

        // Process each active rule
        foreach ($activeRules as $rule) {
            $this->info("Processing: {$rule->name}");
            $this->line("  Timing: {$rule->timing_label}");
            $this->line(str_repeat('─', 60));

            // Calculate target date based on rule
            if ($rule->days_before_due < 0) {
                // Reminder sebelum jatuh tempo
                $targetDueDate = $today->copy()->addDays(abs($rule->days_before_due));
            } elseif ($rule->days_before_due == 0) {
                // Reminder tepat di jatuh tempo
                $targetDueDate = $today;
            } else {
                // Overdue reminder - target payments yang sudah lewat jatuh tempo
                $targetDueDate = $today->copy()->subDays($rule->days_before_due);
            }

            // Find payments that need this reminder
            $paymentsQuery = Payment::where('status', 'pending')
                ->whereDate('due_date', $targetDueDate)
                ->whereDoesntHave('reminders', function ($q) use ($rule) {
                    $q->where('reminder_rule_id', $rule->id)
                        ->where('status', '!=', 'failed'); // Allow retry for failed
                });

            // For overdue, we need different logic
            if ($rule->days_before_due > 0) {
                $paymentsQuery = Payment::where('status', 'pending')
                    ->whereDate('due_date', '<=', $targetDueDate)
                    ->whereDoesntHave('reminders', function ($q) use ($rule) {
                        $q->where('reminder_rule_id', $rule->id);
                    });
            }

            $payments = $paymentsQuery->with(['customer', 'customer.internetPackage'])->get();

            $this->line("  Found {$payments->count()} payments to remind");

            if ($payments->isEmpty()) {
                $this->newLine();
                continue;
            }

            // Collect unpaid customers for admin report (overdue rules only)
            if ($rule->days_before_due > 0) {
                foreach ($payments as $payment) {
                    if ($payment->customer && !$unpaidCustomers->contains('payment_id', $payment->id)) {
                        $unpaidCustomers->push([
                            'payment_id' => $payment->id,
                            'customer_name' => $payment->customer->name,
                            'customer_phone' => $payment->customer->phone ?? '-',
                            'invoice_number' => $payment->invoice_number,
                            'amount' => $payment->amount,
                            'due_date' => $payment->due_date,
                            'package_name' => $payment->customer->internetPackage->name ?? '-',
                            'rule_name' => $rule->name,
                            'days_overdue' => $rule->days_before_due,
                        ]);
                    }
                }
            }

            $sent = 0;
            $failed = 0;

            foreach ($payments as $payment) {
                // Auto-suspend customer via IP Binding if the rule is overdue
                if ($rule->days_before_due > 0 && !$isDryRun && $payment->customer) {
                    try {
                        // Check if customer is not already isolated
                        if (!$payment->customer->is_isolated || $payment->customer->status !== 'suspended') {
                            $suspendService = new \App\Services\SuspendViaIpBindingService();
                            $suspendResult = $suspendService->suspendCustomer($payment->customer);

                            if ($suspendResult['success']) {
                                $this->line("  🔒 Auto-suspended {$payment->customer->name} via IP Binding (" . ($suspendResult['suspended_count'] ?? 1) . " IPs)");
                            }
                        }
                    } catch (\Exception $e) {
                        Log::error("Failed to auto-suspend during payment reminders", [
                            'customer_id' => $payment->customer->id,
                            'error' => $e->getMessage()
                        ]);
                    }
                }

                $result = $this->sendReminder($payment, $rule, $isDryRun);
                if ($result) {
                    $sent++;
                } else {
                    $failed++;
                }

                if (!$isDryRun) {
                    usleep(500000); // 0.5 second delay
                }
            }

            $this->line("  ✅ Sent: {$sent}");
            if ($failed > 0) {
                $this->error("  ❌ Failed: {$failed}");
            }

            $totalSent += $sent;
            $totalFailed += $failed;
            $this->newLine();
        }

        // Summary
        $this->info('=== Summary ===');
        $this->line("✅ Total Sent: {$totalSent}");
        if ($totalFailed > 0) {
            $this->error("❌ Total Failed: {$totalFailed}");
        }

        // Send admin report for unpaid customers (overdue)
        if ($unpaidCustomers->isNotEmpty() && !$isDryRun) {
            $this->sendAdminReport($unpaidCustomers);
        } elseif ($unpaidCustomers->isNotEmpty() && $isDryRun) {
            $this->warn("📋 DRY RUN: Would send admin report with {$unpaidCustomers->count()} unpaid customers");
        }

        $this->info('Payment reminders completed!');

        return Command::SUCCESS;
    }

    /**
     * Send reminder for a specific payment using a rule
     */
    protected function sendReminder(Payment $payment, PaymentReminderRule $rule, bool $isDryRun = false): bool
    {
        $customer = $payment->customer;

        if (!$customer || !$customer->phone) {
            $this->warn("  ⚠  Skip {$payment->invoice_number}: No customer or phone");
            return false;
        }

        if ($isDryRun) {
            $this->line("  📤 Would send to: {$customer->name} ({$customer->phone}) - Invoice: {$payment->invoice_number}");
            return true;
        }

        // Create reminder record
        $reminder = PaymentReminder::create([
            'payment_id' => $payment->id,
            'reminder_rule_id' => $rule->id,
            'reminder_type' => $this->getReminderType($rule->days_before_due),
            'reminder_date' => now(),
            'status' => 'pending',
        ]);

        try {
            // Determine service type for WhatsAppService
            $serviceType = $this->getServiceType($rule->days_before_due);

            // Send via WhatsApp WITH PDF INVOICE for reminders
            $this->whatsapp->sendBillingNotification($payment, $serviceType, true, $rule->whatsappTemplate);

            $this->info("  ✅ Sent to: {$customer->name} ({$customer->phone}) - {$payment->invoice_number}");

            // Find the WhatsAppMessage that was just sent
            $whatsAppMessage = $customer->whatsappMessages()
                ->where('payment_id', $payment->id)
                ->where('message_type', 'billing.' . $serviceType)
                ->latest()
                ->first();

            $reminder->markAsSent($whatsAppMessage->id ?? null);
            return true;

        } catch (\Exception $e) {
            $this->error("  ❌ Failed: {$customer->name} - {$e->getMessage()}");
            $reminder->markAsFailed($e->getMessage());
            Log::error("Payment reminder failed", [
                'payment_id' => $payment->id,
                'rule_id' => $rule->id,
                'error' => $e->getMessage(),
            ]);
            return false;
        }
    }

    /**
     * Get reminder type constant based on days_before_due
     */
    protected function getReminderType(int $days): string
    {
        if ($days < 0) {
            return 'before_due';
        } elseif ($days == 0) {
            return 'on_due';
        } else {
            return 'overdue';
        }
    }

    /**
     * Get service type for WhatsAppService based on days_before_due
     */
    protected function getServiceType(int $days): string
    {
        // Map to existing service types in WhatsAppService
        if ($days <= -7) {
            return 'reminder'; // Generic reminder
        } elseif ($days == -3) {
            return 'reminder_h3';
        } elseif ($days == -1) {
            return 'reminder_h1';
        } elseif ($days == 0) {
            return 'reminder_h0';
        } else {
            return 'overdue';
        }
    }

    /**
     * Send admin report with list of unpaid customers
     */
    protected function sendAdminReport($unpaidCustomers): void
    {
        // Get admin users with phone numbers
        $adminUsers = User::where('is_admin', true)
            ->whereNotNull('phone')
            ->where('phone', '!=', '')
            ->get();

        if ($adminUsers->isEmpty()) {
            $this->warn('⚠️  Tidak ada user admin dengan nomor telepon. Laporan tidak dikirim.');
            $this->line('  Tambahkan nomor telepon di menu Pengguna untuk user admin.');
            return;
        }

        $companyName = Setting::get('company_name', 'Internet Provider');
        $date = now()->format('d F Y');
        $time = now()->format('H:i');

        // Build the report message
        $message = "📊 *LAPORAN CUSTOMER BELUM BAYAR*\n";
        $message .= "━━━━━━━━━━━━━━━━━━━━\n";
        $message .= "🏢 {$companyName}\n";
        $message .= "📅 {$date} | ⏰ {$time}\n";
        $message .= "━━━━━━━━━━━━━━━━━━━━\n\n";

        $totalAmount = 0;
        $no = 1;

        foreach ($unpaidCustomers as $customer) {
            $dueDate = Carbon::parse($customer['due_date'])->format('d/m/Y');
            $amount = number_format($customer['amount'], 0, ',', '.');
            $daysOverdue = $customer['days_overdue'];
            $totalAmount += $customer['amount'];

            $message .= "{$no}. *{$customer['customer_name']}*\n";
            $message .= "   📱 {$customer['customer_phone']}\n";
            $message .= "   📦 {$customer['package_name']}\n";
            $message .= "   🧾 {$customer['invoice_number']}\n";
            $message .= "   💰 Rp {$amount}\n";
            $message .= "   📅 Jatuh tempo: {$dueDate}\n";
            $message .= "   ⏳ Terlambat: {$daysOverdue} hari\n";
            $message .= "\n";

            $no++;
        }

        $totalFormatted = number_format($totalAmount, 0, ',', '.');
        $message .= "━━━━━━━━━━━━━━━━━━━━\n";
        $message .= "📊 *RINGKASAN:*\n";
        $message .= "👥 Total: {$unpaidCustomers->count()} customer\n";
        $message .= "💰 Total Tagihan: Rp {$totalFormatted}\n";
        $message .= "━━━━━━━━━━━━━━━━━━━━\n\n";
        $message .= "_Pesan ini dikirim otomatis oleh sistem._";

        // Send to all admin users
        foreach ($adminUsers as $admin) {
            $this->info("📋 Mengirim laporan ke admin: {$admin->name} ({$admin->phone})");

            try {
                $result = $this->whatsapp->sendMessage($admin->phone, $message);

                if ($result['success'] ?? false) {
                    $this->info("✅ Laporan terkirim ke {$admin->name} ({$admin->phone})");
                } else {
                    $errorMsg = $result['error'] ?? 'Unknown error';
                    $this->error("❌ Gagal kirim ke {$admin->name}: {$errorMsg}");
                }
            } catch (\Exception $e) {
                $this->error("❌ Gagal kirim ke {$admin->name}: {$e->getMessage()}");
                Log::error('Admin unpaid report failed', [
                    'admin_id' => $admin->id,
                    'admin_name' => $admin->name,
                    'phone' => $admin->phone,
                    'error' => $e->getMessage(),
                    'customer_count' => $unpaidCustomers->count(),
                ]);
            }

            usleep(500000); // 0.5s delay between messages
        }
    }
}
