<?php

namespace App\Filament\Widgets;

use App\Models\Payment;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Card;

class PaymentStatusAnalytics extends BaseWidget
{
    protected ?string $heading = 'Status Pembayaran';
    
    protected static bool $shouldRegisterNavigation = false;

    public function getHeading(): ?string
    {
        // Cek apakah ada custom heading yang disimpan dalam cache
        $customHeading = cache()->get('analytics_widget_custom_heading_' . static::class);
        return $customHeading ?: $this->heading;
    }

    protected function getCards(): array
    {
        $now = now();
        $start = $now->copy()->startOfMonth();
        $end = $now->copy()->endOfMonth();

        // Pembayaran lunas bulan ini
        $paidCount = Payment::whereBetween('payment_date', [$start, $end])
            ->where('status', 'paid')
            ->count();
        
        $paidAmount = Payment::whereBetween('payment_date', [$start, $end])
            ->where('status', 'paid')
            ->sum('amount');

        // Pembayaran pending
        $pendingCount = Payment::where('status', 'pending')
            ->count();
        
        $pendingAmount = Payment::where('status', 'pending')
            ->sum('amount');

        // Pembayaran overdue (terlambat)
        $overdueCount = Payment::where('status', 'pending')
            ->where('due_date', '<', now())
            ->count();
        
        $overdueAmount = Payment::where('status', 'pending')
            ->where('due_date', '<', now())
            ->sum('amount');

        // Total tagihan bulan ini
        $totalInvoices = Payment::whereBetween('created_at', [$start, $end])
            ->count();
        
        $totalInvoiceAmount = Payment::whereBetween('created_at', [$start, $end])
            ->sum('amount');

        $formatCurrency = fn (float $v) => 'Rp ' . number_format($v, 0, ',', '.');

        return [
            Card::make('Pembayaran Lunas', $formatCurrency($paidAmount))
                ->description("$paidCount pembayaran")
                ->descriptionIcon('heroicon-m-check-circle')
                ->color('success'),

            Card::make('Pembayaran Pending', $formatCurrency($pendingAmount))
                ->description("$pendingCount pembayaran")
                ->descriptionIcon('heroicon-m-clock')
                ->color('warning'),

            Card::make('Pembayaran Terlambat', $formatCurrency($overdueAmount))
                ->description("$overdueCount pembayaran")
                ->descriptionIcon('heroicon-m-exclamation-triangle')
                ->color('danger'),

            Card::make('Total Tagihan Bulan Ini', $formatCurrency($totalInvoiceAmount))
                ->description("$totalInvoices tagihan")
                ->descriptionIcon('heroicon-m-document-text')
                ->color('info'),
        ];
    }
}
