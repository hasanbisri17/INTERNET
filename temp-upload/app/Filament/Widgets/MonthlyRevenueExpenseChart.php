<?php

namespace App\Filament\Widgets;

use App\Models\CashTransaction;
use Filament\Widgets\ChartWidget;

class MonthlyRevenueExpenseChart extends ChartWidget
{
    protected static ?string $heading = 'Pendapatan vs Pengeluaran (12 Bulan)';
    
    protected static bool $shouldRegisterNavigation = false;

    public function getHeading(): ?string
    {
        // Cek apakah ada custom heading yang disimpan dalam cache
        $customHeading = cache()->get('analytics_widget_custom_heading_' . static::class);
        return $customHeading ?: static::$heading;
    }

    protected function getData(): array
    {
        $labels = [];
        $income = [];
        $expense = [];

        for ($i = 11; $i >= 0; $i--) {
            $month = now()->copy()->subMonths($i);
            $labels[] = $month->format('M Y');

            $start = $month->copy()->startOfMonth();
            $end = $month->copy()->endOfMonth();

            $inc = (float) CashTransaction::whereBetween('date', [$start->toDateString(), $end->toDateString()])
                ->where('type', 'income')
                ->sum('amount');

            $exp = (float) CashTransaction::whereBetween('date', [$start->toDateString(), $end->toDateString()])
                ->where('type', 'expense')
                ->sum('amount');

            $income[] = $inc;
            $expense[] = $exp;
        }

        return [
            'datasets' => [
                [
                    'label' => 'Pendapatan',
                    'data' => $income,
                    'borderColor' => '#16a34a',
                    'backgroundColor' => 'rgba(22, 163, 74, 0.2)',
                ],
                [
                    'label' => 'Pengeluaran',
                    'data' => $expense,
                    'borderColor' => '#ef4444',
                    'backgroundColor' => 'rgba(239, 68, 68, 0.2)',
                ],
            ],
            'labels' => $labels,
        ];
    }

    protected function getType(): string
    {
        return 'line';
    }
}