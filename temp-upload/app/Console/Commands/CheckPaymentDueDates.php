<?php

namespace App\Console\Commands;

use App\Models\Payment;
use App\Models\User;
use Carbon\Carbon;
use Filament\Notifications\Notification;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class CheckPaymentDueDates extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'payments:check-due-dates';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Check for upcoming due dates and overdue payments, send notifications to admins';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Checking payment due dates...');
        
        try {
            $today = Carbon::now()->startOfDay();
            
            // Check for payments due in 2 days
            $upcomingDuePayments = Payment::where('status', 'pending')
                ->whereDate('due_date', $today->copy()->addDays(2))
                ->with('customer')
                ->get();
            
            if ($upcomingDuePayments->count() > 0) {
                $this->info("Found {$upcomingDuePayments->count()} payments due in 2 days");
                $this->sendUpcomingDueNotification($upcomingDuePayments);
            }
            
            // Check for overdue payments
            $overduePayments = Payment::where('status', 'pending')
                ->whereDate('due_date', '<', $today)
                ->with('customer')
                ->get();
            
            if ($overduePayments->count() > 0) {
                $this->info("Found {$overduePayments->count()} overdue payments");
                $this->sendOverdueNotification($overduePayments);
            }
            
            if ($upcomingDuePayments->count() === 0 && $overduePayments->count() === 0) {
                $this->info('No upcoming or overdue payments found.');
            }
            
            return Command::SUCCESS;
        } catch (\Exception $e) {
            $this->error("Error checking payment due dates: {$e->getMessage()}");
            Log::error("Error checking payment due dates: {$e->getMessage()}");
            
            return Command::FAILURE;
        }
    }
    
    /**
     * Send notification for upcoming due payments
     */
    protected function sendUpcomingDueNotification($payments): void
    {
        try {
            $adminUsers = User::where('is_admin', true)->get();
            $totalAmount = $payments->sum('amount');
            $count = $payments->count();
            
            // List of customer names (max 5)
            $customerList = $payments->take(5)->pluck('customer.name')->implode(', ');
            $moreText = $count > 5 ? " dan " . ($count - 5) . " lainnya" : "";
            
            Notification::make()
                ->title('⏰ Tagihan Mendekati Jatuh Tempo')
                ->body("{$count} tagihan akan jatuh tempo dalam 2 hari (Total: Rp " . number_format($totalAmount, 0, ',', '.') . "). Customer: {$customerList}{$moreText}.")
                ->warning()
                ->icon('heroicon-o-clock')
                ->actions([
                    \Filament\Notifications\Actions\Action::make('view')
                        ->label('Lihat Tagihan')
                        ->url(route('filament.admin.resources.payments.index', [
                            'tableFilters' => [
                                'status' => ['value' => 'pending'],
                            ],
                        ]))
                        ->button(),
                ])
                ->sendToDatabase($adminUsers);
            
            $this->info("Notification sent for {$count} upcoming due payments");
        } catch (\Exception $e) {
            Log::error("Failed to send upcoming due notification: {$e->getMessage()}");
        }
    }
    
    /**
     * Send notification for overdue payments
     */
    protected function sendOverdueNotification($payments): void
    {
        try {
            $adminUsers = User::where('is_admin', true)->get();
            $totalAmount = $payments->sum('amount');
            $count = $payments->count();
            
            // List of customer names (max 5)
            $customerList = $payments->take(5)->pluck('customer.name')->implode(', ');
            $moreText = $count > 5 ? " dan " . ($count - 5) . " lainnya" : "";
            
            Notification::make()
                ->title('🔴 Tagihan Terlambat')
                ->body("{$count} tagihan melewati jatuh tempo (Total: Rp " . number_format($totalAmount, 0, ',', '.') . "). Customer: {$customerList}{$moreText}.")
                ->danger()
                ->icon('heroicon-o-exclamation-circle')
                ->actions([
                    \Filament\Notifications\Actions\Action::make('view')
                        ->label('Lihat Tagihan')
                        ->url(route('filament.admin.resources.payments.index', [
                            'tableFilters' => [
                                'status' => ['value' => 'pending'],
                            ],
                        ]))
                        ->button(),
                ])
                ->sendToDatabase($adminUsers);
            
            $this->info("Notification sent for {$count} overdue payments");
        } catch (\Exception $e) {
            Log::error("Failed to send overdue notification: {$e->getMessage()}");
        }
    }
}

