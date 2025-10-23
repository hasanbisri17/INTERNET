<?php

namespace App\Console\Commands;

use App\Services\SuspendViaIpBindingService;
use Illuminate\Console\Command;

class AutoSuspendViaIpBindingCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'suspend:auto-ip-binding 
                            {--dry-run : Run in dry-run mode without actually suspending}
                            {--customer= : Suspend specific customer by ID}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Auto suspend customers via IP Binding (change type from bypassed to regular) for overdue payments';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->info('🚀 Starting auto suspend via IP Binding...');
        $this->newLine();

        $service = new SuspendViaIpBindingService();

        // Dry run mode
        if ($this->option('dry-run')) {
            $this->warn('⚠️  Running in DRY-RUN mode - no actual changes will be made');
            $customers = $service->getCustomersToBeSuspended();
            
            if ($customers->isEmpty()) {
                $this->info('✅ No customers need to be suspended.');
                return Command::SUCCESS;
            }

            $this->table(
                ['ID', 'Name', 'Phone', 'Due Date', 'IP Bindings Count'],
                $customers->map(function ($customer) {
                    return [
                        $customer->id,
                        $customer->name,
                        $customer->phone ?? '-',
                        $customer->due_date?->format('d M Y') ?? '-',
                        $customer->ipBindings->count(),
                    ];
                })->toArray()
            );

            $this->newLine();
            $this->info("Total customers to be suspended: {$customers->count()}");
            return Command::SUCCESS;
        }

        // Suspend specific customer
        if ($customerId = $this->option('customer')) {
            $customer = \App\Models\Customer::find($customerId);
            
            if (!$customer) {
                $this->error("❌ Customer with ID {$customerId} not found");
                return Command::FAILURE;
            }

            $this->info("Suspending customer: {$customer->name}");
            $result = $service->suspendCustomer($customer);

            if ($result['success']) {
                $this->info("✅ {$result['message']}");
                return Command::SUCCESS;
            } else {
                $this->error("❌ {$result['message']}");
                if (!empty($result['errors'])) {
                    $this->error('Errors:');
                    foreach ($result['errors'] as $error) {
                        $this->error("  - {$error}");
                    }
                }
                return Command::FAILURE;
            }
        }

        // Process all customers
        $result = $service->processAutoSuspend();

        $this->newLine();
        
        if ($result['success']) {
            $this->info("✅ {$result['message']}");
            
            if ($result['suspended_count'] > 0) {
                $this->info("📊 Suspended: {$result['suspended_count']} customers");
            }
            
            if ($result['failed_count'] > 0) {
                $this->warn("⚠️  Failed: {$result['failed_count']} customers");
                
                if (!empty($result['errors'])) {
                    $this->error('Errors:');
                    foreach ($result['errors'] as $error) {
                        $this->error("  - {$error}");
                    }
                }
            }
            
            return Command::SUCCESS;
        } else {
            $this->error("❌ {$result['message']}");
            return Command::FAILURE;
        }
    }
}

