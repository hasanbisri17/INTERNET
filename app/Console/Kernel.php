<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    /**
     * Define the application's command schedule.
     */
    /**
     * Schedule is defined in routes/console.php (Laravel 12 convention).
     * Do not duplicate schedule definitions here.
     */
    protected function schedule(Schedule $schedule): void
    {
        // Schedules are registered in routes/console.php
    }

    /**
     * Register the commands for the application.
     */
    protected function commands(): void
    {
        $this->load(__DIR__ . '/Commands');

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
        Commands\SendDebtReminders::class,
        Commands\SendReceivableReminders::class,
    ];
}
