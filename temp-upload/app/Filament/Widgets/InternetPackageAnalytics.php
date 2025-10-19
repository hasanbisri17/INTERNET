<?php

namespace App\Filament\Widgets;

use App\Models\InternetPackage;
use App\Models\Customer;
use Filament\Widgets\ChartWidget;

class InternetPackageAnalytics extends ChartWidget
{
    protected static ?string $heading = 'Paket Internet Paling Populer';
    
    protected static bool $shouldRegisterNavigation = false;

    public function getHeading(): ?string
    {
        // Cek apakah ada custom heading yang disimpan dalam cache
        $customHeading = cache()->get('analytics_widget_custom_heading_' . static::class);
        return $customHeading ?: static::$heading;
    }

    protected function getData(): array
    {
        $packages = InternetPackage::withCount('customers')->get();

        $labels = [];
        $data = [];
        $colors = [
            '#3b82f6', '#ef4444', '#10b981', '#f59e0b', 
            '#8b5cf6', '#06b6d4', '#84cc16', '#f97316'
        ];

        foreach ($packages as $index => $package) {
            if ($package->customers_count > 0) {
                $labels[] = $package->name;
                $data[] = $package->customers_count;
            }
        }

        return [
            'datasets' => [
                [
                    'label' => 'Jumlah Pelanggan',
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
        return 'bar';
    }

    protected function getOptions(): array
    {
        return [
            'plugins' => [
                'legend' => [
                    'display' => false,
                ],
                'tooltip' => [
                    'callbacks' => [
                        'label' => 'function(context) {
                            return context.label + ": " + context.parsed.y + " pelanggan";
                        }'
                    ]
                ]
            ],
            'scales' => [
                'y' => [
                    'beginAtZero' => true,
                    'ticks' => [
                        'stepSize' => 1,
                    ],
                ],
            ],
            'responsive' => true,
            'maintainAspectRatio' => false,
        ];
    }
}
