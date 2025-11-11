<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    /**
     * Define the application's command schedule.
     */
    protected function schedule(Schedule $schedule): void
    {
        // Send scheduled WhatsApp messages every minute
        $schedule->command('whatsapp:send-scheduled')
            ->everyMinute()
            ->withoutOverlapping();

        // Send payment reminders - Run 3 times a day for better coverage
        $schedule->command('whatsapp:payment-reminders')
            ->dailyAt('09:00')  // Morning reminder
            ->withoutOverlapping();
            
        $schedule->command('whatsapp:payment-reminders')
            ->dailyAt('14:00')  // Afternoon reminder
            ->withoutOverlapping();
            
        $schedule->command('whatsapp:payment-reminders')
            ->dailyAt('19:00')  // Evening reminder
            ->withoutOverlapping();
            
        // Generate monthly bills on the 1st day of each month at 00:01 AM
        $schedule->command('bills:generate')
            ->monthlyOn(1, '00:01')
            ->withoutOverlapping();
            
        // Clean old activity logs (older than 6 months) - Run monthly on the 1st at 02:00 AM
        $schedule->command('activitylog:clean --days=180')
            ->monthlyOn(1, '02:00')
            ->withoutOverlapping();
        
        // Update payment status to overdue - Run daily at 00:01 AM
        $schedule->command('payments:update-overdue')
            ->dailyAt('00:01')
            ->withoutOverlapping();
        
        // Check upcoming and overdue payments - Run daily at 08:00 AM
        $schedule->command('payments:check-due-dates')
            ->dailyAt('08:00')
            ->withoutOverlapping();
            
        // Auto suspend customers via IP Binding on 26th of each month at 00:01 AM
        $schedule->command('suspend:auto-ip-binding')
            ->monthlyOn(26, '00:01')
            ->withoutOverlapping();
    }

    /**
     * Register the commands for the application.
     */
    protected function commands(): void
    {
        $this->load(__DIR__.'/Commands');

        require base_path('routes/console.php');
    }

    /**
     * The Artisan commands provided by your application.
     */
    protected $commands = [
        Commands\SendScheduledWhatsAppMessages::class,
        Commands\SendPaymentReminders::class,
        Commands\TestWhatsAppCommand::class,
        Commands\GenerateMonthlyBills::class,
        Commands\GenerateBillForCustomer::class,
        Commands\CleanOldActivityLogs::class,
        Commands\CheckPaymentDueDates::class,
        Commands\UpdateOverduePayments::class,
        Commands\AutoSuspendViaIpBindingCommand::class,
    ];
}
