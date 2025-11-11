<?php

namespace App\Console\Commands;

use App\Models\Payment;
use App\Models\User;
use App\Models\WhatsAppTemplate;
use App\Services\WhatsAppService;
use Carbon\Carbon;
use Filament\Notifications\Notification;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class UpdateOverduePayments extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'payments:update-overdue {--dry-run : Preview without updating}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Update status payment pending menjadi overdue setelah melewati due date';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $isDryRun = $this->option('dry-run');
        
        $this->info('=== Update Overdue Payments ===');
        $this->info('Date: ' . now()->format('d F Y H:i:s'));
        
        if ($isDryRun) {
            $this->warn('🔍 DRY RUN MODE - No updates will be made');
        }
        
        $this->newLine();
        
        try {
            $today = Carbon::now()->startOfDay();
            
            // Get all pending payments dengan due_date < today
            $overduePayments = Payment::where('status', 'pending')
                ->whereDate('due_date', '<', $today)
                ->with(['customer', 'internetPackage'])
                ->get();
            
            if ($overduePayments->isEmpty()) {
                $this->info('✅ No overdue payments found.');
                return Command::SUCCESS;
            }
            
            $this->info("Found {$overduePayments->count()} overdue payment(s):");
            $this->newLine();
            
            // Display table
            $tableData = [];
            foreach ($overduePayments as $payment) {
                $daysOverdue = Carbon::parse($payment->due_date)->diffInDays(now());
                
                $tableData[] = [
                    'Invoice' => $payment->invoice_number,
                    'Customer' => $payment->customer?->name ?? '-',
                    'Amount' => 'Rp ' . number_format($payment->amount, 0, ',', '.'),
                    'Due Date' => $payment->due_date->format('d M Y'),
                    'Days Overdue' => $daysOverdue . ' hari',
                ];
            }
            
            $this->table(
                ['Invoice', 'Customer', 'Amount', 'Due Date', 'Days Overdue'],
                $tableData
            );
            
            if ($isDryRun) {
                $this->newLine();
                $this->info("🔍 DRY RUN: {$overduePayments->count()} payment(s) would be updated to 'overdue' status");
                return Command::SUCCESS;
            }
            
            // Update status
            $updated = 0;
            $failed = 0;
            $errors = [];
            
            $this->newLine();
            $this->info('Updating payment statuses...');
            
            foreach ($overduePayments as $payment) {
                try {
                    $payment->update([
                        'status' => 'overdue',
                    ]);
                    
                    // Log activity
                    activity('payment_status_update')
                        ->performedOn($payment)
                        ->withProperties([
                            'old_status' => 'pending',
                            'new_status' => 'overdue',
                            'invoice_number' => $payment->invoice_number,
                            'customer' => $payment->customer?->name,
                            'days_overdue' => Carbon::parse($payment->due_date)->diffInDays(now()),
                        ])
                        ->log("Payment {$payment->invoice_number} status updated to overdue");
                    
                    // Send WhatsApp notification to customer
                    $this->sendStatusOverdueNotification($payment);
                    
                    $updated++;
                    $this->line("  ✅ {$payment->invoice_number} - {$payment->customer?->name}");
                    
                } catch (\Exception $e) {
                    $failed++;
                    $errors[] = "{$payment->invoice_number}: {$e->getMessage()}";
                    $this->error("  ❌ {$payment->invoice_number} - {$e->getMessage()}");
                    
                    Log::error("Failed to update payment to overdue", [
                        'payment_id' => $payment->id,
                        'invoice_number' => $payment->invoice_number,
                        'error' => $e->getMessage(),
                    ]);
                }
            }
            
            $this->newLine();
            $this->info('=== Summary ===');
            $this->line("✅ Updated: {$updated}");
            
            if ($failed > 0) {
                $this->error("❌ Failed: {$failed}");
            }
            
            // Send notification to admins
            if ($updated > 0) {
                $this->sendAdminNotification($updated, $overduePayments->sum('amount'));
                $this->info('📧 Notification sent to admin users');
            }
            
            Log::info("Overdue payments updated", [
                'updated' => $updated,
                'failed' => $failed,
                'errors' => $errors,
            ]);
            
            return Command::SUCCESS;
            
        } catch (\Exception $e) {
            $this->error("Error updating overdue payments: {$e->getMessage()}");
            Log::error("Error updating overdue payments: {$e->getMessage()}");
            
            return Command::FAILURE;
        }
    }
    
    /**
     * Send notification to admin users
     */
    protected function sendAdminNotification(int $count, float $totalAmount): void
    {
        try {
            $adminUsers = User::where('is_admin', true)->get();
            
            Notification::make()
                ->title('🔴 Status Payment Diupdate ke Overdue')
                ->body("{$count} payment telah diupdate statusnya menjadi 'Terlambat' (Total: Rp " . number_format($totalAmount, 0, ',', '.') . ").")
                ->danger()
                ->icon('heroicon-o-exclamation-triangle')
                ->actions([
                    \Filament\Notifications\Actions\Action::make('view')
                        ->label('Lihat Payment Overdue')
                        ->url(route('filament.admin.resources.payments.index', [
                            'tableFilters' => [
                                'status' => ['value' => 'overdue'],
                            ],
                        ]))
                        ->button(),
                ])
                ->sendToDatabase($adminUsers);
                
        } catch (\Exception $e) {
            Log::error("Failed to send overdue notification: {$e->getMessage()}");
        }
    }
    
    /**
     * Send WhatsApp notification to customer about status overdue
     */
    protected function sendStatusOverdueNotification(Payment $payment): void
    {
        try {
            $customer = $payment->customer;
            
            if (!$customer || !$customer->phone) {
                Log::warning("Customer tidak memiliki nomor telepon", [
                    'payment_id' => $payment->id,
                    'customer_id' => $customer?->id,
                ]);
                return;
            }
            
            // Send WhatsApp notification WITH PDF INVOICE for overdue payments
            // sendBillingNotification will handle template selection automatically
            $whatsAppService = new WhatsAppService();
            $whatsAppService->sendBillingNotification($payment, 'overdue', true); // true = send PDF invoice
            
            Log::info("Status overdue WhatsApp notification sent", [
                'payment_id' => $payment->id,
                'invoice_number' => $payment->invoice_number,
                'customer_id' => $customer->id,
                'customer_name' => $customer->name,
                'phone' => $customer->phone,
            ]);
            
        } catch (\Exception $e) {
            Log::error("Failed to send status overdue WhatsApp notification", [
                'payment_id' => $payment->id,
                'error' => $e->getMessage(),
            ]);
        }
    }
}

