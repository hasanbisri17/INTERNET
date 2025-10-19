<?php

namespace App\Console\Commands;

use App\Filament\Resources\PaymentResource;
use App\Models\Customer;
use App\Models\Payment;
use App\Models\Setting;
use App\Models\User;
use App\Services\WhatsAppService;
use Carbon\Carbon;
use Filament\Notifications\Notification;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class GenerateMonthlyBills extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'bills:generate {--month= : Format YYYY-MM, default is current month}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Generate monthly bills for all active customers';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $month = $this->option('month');
        
        if (!$month) {
            // Default to current month
            $month = Carbon::now()->format('Y-m');
        }
        
        $this->info("Generating bills for month: {$month}");
        
        try {
            // Get all active customers
            $customers = Customer::whereHas('internetPackage', function ($query) {
                $query->where('is_active', true);
            })->get();
            
            $this->info("Found {$customers->count()} active customers");
            
            // Get due date setting from database (default to 25th if not set)
            $dueDateDay = (int) Setting::get('billing_due_day', 25);
            
            $selectedDate = Carbon::createFromFormat('Y-m', $month)->startOfMonth();
            // Set due date to configured day, or last day of month if day doesn't exist
            $dueDate = $selectedDate->copy()->day(min($dueDateDay, $selectedDate->daysInMonth));
            
            $this->info("Using due date: {$dueDate->format('Y-m-d')} (day {$dueDateDay} of month)");
            
            $billsGenerated = 0;
            $billsSkipped = 0;
            
            foreach ($customers as $customer) {
                // Check if customer already has a bill for this month
                $existingBill = Payment::where('customer_id', $customer->id)
                    ->whereYear('due_date', $selectedDate->year)
                    ->whereMonth('due_date', $selectedDate->month)
                    ->exists();
                
                if (!$existingBill) {
                    $payment = Payment::create([
                        'customer_id' => $customer->id,
                        'internet_package_id' => $customer->internet_package_id,
                        'invoice_number' => Payment::generateInvoiceNumber(),
                        'amount' => $customer->internetPackage->price,
                        'due_date' => $dueDate,
                        'status' => 'pending',
                        'payment_method_id' => null, // Allow null for pending payments
                    ]);
                    
                    // Send WhatsApp notification WITH PDF INVOICE
                    try {
                        $whatsapp = new WhatsAppService();
                        $whatsapp->sendBillingNotification($payment, 'new', true); // true = send PDF
                        $this->info("  → WhatsApp + PDF sent");
                    } catch (\Exception $e) {
                        $this->warn("Failed to send WhatsApp notification for customer {$customer->name}: {$e->getMessage()}");
                        Log::error("Failed to send WhatsApp notification for bill {$payment->invoice_number}: {$e->getMessage()}");
                    }
                    
                    $billsGenerated++;
                    $this->info("Generated bill for {$customer->name}: {$payment->invoice_number}");
                } else {
                    $billsSkipped++;
                    $this->line("Skipped bill for {$customer->name} (already exists for {$month})");
                }
            }
            
            $this->info("Bills generation completed: {$billsGenerated} generated, {$billsSkipped} skipped");
            
            // Send database notification to admins
            if ($billsGenerated > 0) {
                $this->sendBillGenerationNotification($billsGenerated, $billsSkipped, $month);
            }
            
            return Command::SUCCESS;
        } catch (\Exception $e) {
            $this->error("Error generating bills: {$e->getMessage()}");
            Log::error("Error generating bills: {$e->getMessage()}", [
                'trace' => $e->getTraceAsString(),
            ]);
            
            return Command::FAILURE;
        }
    }
    
    /**
     * Send database notification after bill generation
     */
    protected function sendBillGenerationNotification(int $generated, int $skipped, string $month): void
    {
        try {
            $adminUsers = User::where('is_admin', true)->get();
            
            Notification::make()
                ->title('📄 Tagihan Bulanan Dibuat')
                ->body("Tagihan bulan {$month} berhasil dibuat untuk {$generated} customer" . ($skipped > 0 ? ", {$skipped} dilewati (sudah ada)" : "") . ".")
                ->success()
                ->icon('heroicon-o-document-text')
                ->actions([
                    \Filament\Notifications\Actions\Action::make('view')
                        ->label('Lihat Tagihan')
                        ->url(route('filament.admin.resources.payments.index'))
                        ->button(),
                ])
                ->sendToDatabase($adminUsers);
        } catch (\Exception $e) {
            Log::error("Failed to send bill generation notification: {$e->getMessage()}");
        }
    }
}