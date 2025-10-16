<?php

namespace App\Filament\Widgets;

use App\Models\Customer;
use Filament\Widgets\ChartWidget;

class CustomerGrowthChart extends ChartWidget
{
    protected static ?string $heading = 'Tren Pertumbuhan Pelanggan (12 Bulan)';
    
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
        $newCustomers = [];
        $totalCustomers = [];

        for ($i = 11; $i >= 0; $i--) {
            $month = now()->copy()->subMonths($i);
            $labels[] = $month->format('M Y');

            $start = $month->copy()->startOfMonth();
            $end = $month->copy()->endOfMonth();

            // Pelanggan baru bulan ini
            $newCount = Customer::whereBetween('created_at', [$start, $end])->count();
            $newCustomers[] = $newCount;

            // Total pelanggan sampai bulan ini
            $totalCount = Customer::where('created_at', '<=', $end)->count();
            $totalCustomers[] = $totalCount;
        }

        return [
            'datasets' => [
                [
                    'label' => 'Pelanggan Baru',
                    'data' => $newCustomers,
                    'borderColor' => '#3b82f6',
                    'backgroundColor' => 'rgba(59, 130, 246, 0.1)',
                    'fill' => false,
                    'tension' => 0.4,
                ],
                [
                    'label' => 'Total Pelanggan',
                    'data' => $totalCustomers,
                    'borderColor' => '#10b981',
                    'backgroundColor' => 'rgba(16, 185, 129, 0.1)',
                    'fill' => false,
                    'tension' => 0.4,
                ],
            ],
            'labels' => $labels,
        ];
    }

    protected function getType(): string
    {
        return 'line';
    }

    protected function getOptions(): array
    {
        return [
            'plugins' => [
                'legend' => [
                    'position' => 'top',
                ],
                'tooltip' => [
                    'mode' => 'index',
                    'intersect' => false,
                ],
            ],
            'scales' => [
                'y' => [
                    'beginAtZero' => true,
                    'ticks' => [
                        'stepSize' => 1,
                    ],
                ],
                'x' => [
                    'display' => true,
                ],
            ],
            'responsive' => true,
            'maintainAspectRatio' => false,
            'interaction' => [
                'mode' => 'nearest',
                'axis' => 'x',
                'intersect' => false,
            ],
        ];
    }
}
