<?php

namespace App\Filament\Widgets;

use App\Models\Payment;
use App\Models\PaymentMethod;
use Filament\Widgets\ChartWidget;

class PaymentMethodAnalytics extends ChartWidget
{
    protected static ?string $heading = 'Pembayaran per Metode Pembayaran (Bulan Ini)';
    
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

        $paymentMethods = PaymentMethod::withCount([
            'payments' => function ($query) use ($start, $end) {
                $query->whereBetween('payment_date', [$start, $end])
                      ->where('status', 'paid');
            }
        ])->withSum([
            'payments' => function ($query) use ($start, $end) {
                $query->whereBetween('payment_date', [$start, $end])
                      ->where('status', 'paid');
            }
        ], 'amount')->get();

        $labels = [];
        $data = [];
        $colors = [
            '#3b82f6', '#ef4444', '#10b981', '#f59e0b', 
            '#8b5cf6', '#06b6d4', '#84cc16', '#f97316'
        ];

        foreach ($paymentMethods as $index => $method) {
            if ($method->payments_sum_amount > 0) {
                $labels[] = $method->name;
                $data[] = (float) $method->payments_sum_amount;
            }
        }

        return [
            'datasets' => [
                [
                    'label' => 'Total Pembayaran (Rp)',
                    'data' => $data,
                    'backgroundColor' => array_slice($colors, 0, count($data)),
                    'borderWidth' => 2,
                    'borderColor' => '#ffffff',
                ],
            ],
            'labels' => $labels,
        ];
    }

    protected function getType(): string
    {
        return 'doughnut';
    }

    protected function getOptions(): array
    {
        return [
            'plugins' => [
                'legend' => [
                    'position' => 'bottom',
                ],
                'tooltip' => [
                    'callbacks' => [
                        'label' => 'function(context) {
                            const value = context.parsed;
                            return context.label + ": Rp " + value.toLocaleString("id-ID");
                        }'
                    ]
                ]
            ],
            'responsive' => true,
            'maintainAspectRatio' => false,
        ];
    }
}
