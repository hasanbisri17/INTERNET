<?php

use Illuminate\Foundation\Console\ClosureCommand;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    /** @var ClosureCommand $this */
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// ============================================
// 📅 SCHEDULED TASKS
// ============================================

// Send scheduled WhatsApp messages every minute
Schedule::command('whatsapp:send-scheduled')->everyMinute()->withoutOverlapping();

// Send payment reminders - Run 3 times a day for better coverage
Schedule::command('whatsapp:payment-reminders')->dailyAt('09:00')->withoutOverlapping();
Schedule::command('whatsapp:payment-reminders')->dailyAt('14:00')->withoutOverlapping();
Schedule::command('whatsapp:payment-reminders')->dailyAt('19:00')->withoutOverlapping();

// Generate monthly bills on the 1st day of each month at 00:01 AM
Schedule::command('bills:generate')->monthlyOn(1, '00:01')->withoutOverlapping();

// Clean old activity logs (older than 6 months) - Run monthly on the 1st at 02:00 AM
Schedule::command('activitylog:clean --days=180')->monthlyOn(1, '02:00')->withoutOverlapping();

// Update payment status to overdue - Run daily at 00:01 AM
Schedule::command('payments:update-overdue')->dailyAt('00:01')->withoutOverlapping();

// Check upcoming and overdue payments - Run daily at 08:00 AM
Schedule::command('payments:check-due-dates')->dailyAt('08:00')->withoutOverlapping();

// Auto suspend customers via IP Binding on 26th of each month at 00:01 AM
Schedule::command('suspend:auto-ip-binding')->monthlyOn(26, '00:01')->withoutOverlapping();
