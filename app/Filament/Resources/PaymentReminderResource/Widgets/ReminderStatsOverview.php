<?php

namespace App\Filament\Resources\PaymentReminderResource\Widgets;

use App\Models\PaymentReminder;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class ReminderStatsOverview extends BaseWidget
{
    protected function getStats(): array
    {
        $today = now()->toDateString();
        $thisMonth = now()->format('Y-m');

        $totalThisMonth = PaymentReminder::whereRaw("DATE_FORMAT(created_at, '%Y-%m') = ?", [$thisMonth])->count();
        $sentThisMonth = PaymentReminder::where('status', 'sent')
            ->whereRaw("DATE_FORMAT(created_at, '%Y-%m') = ?", [$thisMonth])->count();
        $failedThisMonth = PaymentReminder::where('status', 'failed')
            ->whereRaw("DATE_FORMAT(created_at, '%Y-%m') = ?", [$thisMonth])->count();
        $pendingCount = PaymentReminder::where('status', 'pending')->count();

        // Success rate
        $successRate = $totalThisMonth > 0
            ? round(($sentThisMonth / $totalThisMonth) * 100, 1)
            : 0;

        // Today's stats
        $sentToday = PaymentReminder::where('status', 'sent')
            ->whereDate('sent_at', $today)->count();
        $failedToday = PaymentReminder::where('status', 'failed')
            ->whereDate('created_at', $today)->count();

        return [
            Stat::make('Terkirim Bulan Ini', $sentThisMonth)
                ->description("{$sentToday} terkirim hari ini")
                ->descriptionIcon('heroicon-m-check-circle')
                ->color('success')
                ->chart($this->getChartData('sent')),

            Stat::make('Gagal Bulan Ini', $failedThisMonth)
                ->description("{$failedToday} gagal hari ini")
                ->descriptionIcon('heroicon-m-x-circle')
                ->color('danger')
                ->chart($this->getChartData('failed')),

            Stat::make('Pending', $pendingCount)
                ->description('Menunggu dikirim')
                ->descriptionIcon('heroicon-m-clock')
                ->color('warning'),

            Stat::make('Tingkat Sukses', "{$successRate}%")
                ->description("Dari {$totalThisMonth} reminder bulan ini")
                ->descriptionIcon('heroicon-m-chart-bar')
                ->color($successRate >= 90 ? 'success' : ($successRate >= 70 ? 'warning' : 'danger')),
        ];
    }

    /**
     * Get chart data for the last 7 days
     */
    protected function getChartData(string $status): array
    {
        $data = [];
        for ($i = 6; $i >= 0; $i--) {
            $date = now()->subDays($i)->toDateString();
            $data[] = PaymentReminder::where('status', $status)
                ->whereDate('created_at', $date)
                ->count();
        }
        return $data;
    }
}
