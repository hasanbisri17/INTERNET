<?php

namespace App\Filament\Widgets;

use App\Models\CashTransaction;
use App\Models\TransactionCategory;
use Filament\Widgets\ChartWidget;

class TransactionCategoryAnalytics extends ChartWidget
{
    protected static ?string $heading = 'Kategori Transaksi Terbesar (Bulan Ini)';
    
    protected static bool $shouldRegisterNavigation = false;

    public function getHeading(): ?string
    {
        // Cek apakah ada custom heading yang disimpan dalam cache
        $customHeading = cache()->get('analytics_widget_custom_heading_' . static::class);
        return $customHeading ?: static::$heading;
    }

    protected function getData(): array
    {
        $now = now();
        $start = $now->copy()->startOfMonth();
        $end = $now->copy()->endOfMonth();

        $categories = TransactionCategory::withSum([
            'cashTransactions' => function ($query) use ($start, $end) {
                $query->whereBetween('date', [$start->toDateString(), $end->toDateString()]);
            }
        ], 'amount')->get();

        $labels = [];
        $incomeData = [];
        $expenseData = [];
        $colors = [
            '#3b82f6', '#ef4444', '#10b981', '#f59e0b', 
            '#8b5cf6', '#06b6d4', '#84cc16', '#f97316'
        ];

        foreach ($categories as $index => $category) {
            if ($category->cash_transactions_sum_amount > 0) {
                $labels[] = $category->name;
                
                // Pisahkan income dan expense
                $income = CashTransaction::where('category_id', $category->id)
                    ->whereBetween('date', [$start->toDateString(), $end->toDateString()])
                    ->where('type', 'income')
                    ->sum('amount');
                
                $expense = CashTransaction::where('category_id', $category->id)
                    ->whereBetween('date', [$start->toDateString(), $end->toDateString()])
                    ->where('type', 'expense')
                    ->sum('amount');
                
                $incomeData[] = (float) $income;
                $expenseData[] = (float) $expense;
            }
        }

        return [
            'datasets' => [
                [
                    'label' => 'Pemasukan',
                    'data' => $incomeData,
                    'backgroundColor' => '#10b981',
                    'borderColor' => '#059669',
                    'borderWidth' => 1,
                ],
                [
                    'label' => 'Pengeluaran',
                    'data' => $expenseData,
                    'backgroundColor' => '#ef4444',
                    'borderColor' => '#dc2626',
                    'borderWidth' => 1,
                ],
            ],
            'labels' => $labels,
        ];
    }

    protected function getType(): string
    {
        return 'bar';
    }

    protected function getOptions(): array
    {
        return [
            'plugins' => [
                'legend' => [
                    'position' => 'top',
                ],
                'tooltip' => [
                    'callbacks' => [
                        'label' => 'function(context) {
                            const value = context.parsed.y;
                            return context.dataset.label + ": Rp " + value.toLocaleString("id-ID");
                        }'
                    ]
                ]
            ],
            'scales' => [
                'y' => [
                    'beginAtZero' => true,
                    'ticks' => [
                        'callback' => 'function(value) {
                            return "Rp " + value.toLocaleString("id-ID");
                        }'
                    ],
                ],
            ],
            'responsive' => true,
            'maintainAspectRatio' => false,
        ];
    }
}
