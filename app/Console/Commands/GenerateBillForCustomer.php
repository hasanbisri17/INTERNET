<?php

namespace App\Console\Commands;

use App\Models\Customer;
use App\Models\Payment;
use App\Models\Setting;
use App\Services\WhatsAppService;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class GenerateBillForCustomer extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'bills:generate-for-customer {customer : Customer ID} {--month= : Format YYYY-MM, default is current month}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Generate bill for a specific customer';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $customerId = $this->argument('customer');
        $month = $this->option('month');
        
        if (!$month) {
            // Default to current month
            $month = Carbon::now()->format('Y-m');
        }
        
        try {
            $customer = Customer::findOrFail($customerId);
            
            // Only generate bill for active customers
            if ($customer->status !== 'active' || !$customer->internet_package_id) {
                $this->warn("Customer {$customer->name} is not active (status: {$customer->status}) or has no internet package");
                return Command::FAILURE;
            }
            
            $this->info("Generating bill for customer: {$customer->name} for month: {$month}");
            
            // Get due date setting from database (default to 25th if not set)
            $dueDateDay = (int) Setting::get('billing_due_day', 25);
            
            $selectedDate = Carbon::createFromFormat('Y-m', $month)->startOfMonth();
            // Set due date to configured day, or last day of month if day doesn't exist
            $dueDate = $selectedDate->copy()->day(min($dueDateDay, $selectedDate->daysInMonth));
            
            $this->info("Using due date: {$dueDate->format('Y-m-d')} (day {$dueDateDay} of month)");
            
            // Check if customer already has a bill for this month and year
            $existingBill = Payment::where('customer_id', $customer->id)
                ->where('billing_month', $selectedDate->month)
                ->where('billing_year', $selectedDate->year)
                ->exists();
            
            if (!$existingBill) {
                $payment = Payment::create([
                    'customer_id' => $customer->id,
                    'internet_package_id' => $customer->internet_package_id,
                    'invoice_number' => Payment::generateInvoiceNumber(),
                    'billing_month' => $selectedDate->month,
                    'billing_year' => $selectedDate->year,
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
                
                $this->info("Generated bill for {$customer->name}: {$payment->invoice_number}");
                return Command::SUCCESS;
            } else {
                $this->line("Skipped bill for {$customer->name} (already exists for {$month})");
                return Command::SUCCESS;
            }
        } catch (\Exception $e) {
            $this->error("Error generating bill: {$e->getMessage()}");
            Log::error("Error generating bill for customer {$customerId}: {$e->getMessage()}", [
                'trace' => $e->getTraceAsString(),
            ]);
            
            return Command::FAILURE;
        }
    }
}