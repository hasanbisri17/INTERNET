<?php

namespace App\Filament\Pages;

use Filament\Pages\Dashboard as BaseDashboard;
use App\Filament\Widgets\AnalyticsStatsOverview;
use App\Filament\Widgets\MonthlyRevenueExpenseChart;

class AnalyticsDashboard extends BaseDashboard
{
    protected static ?string $navigationIcon = 'heroicon-o-chart-bar';

    protected static ?string $navigationGroup = 'Keuangan';

    protected static ?string $title = 'Analisis';

    // Tetapkan slug eksplisit agar penamaan route konsisten
    protected static ?string $slug = 'analytics-dashboard';

    // Override route path agar tidak bentrok dengan Dashboard bawaan ('/')
    public static function getRoutePath(): string
    {
        return '/' . static::getSlug();
    }

    protected function getHeaderWidgets(): array
    {
        return [
            AnalyticsStatsOverview::class,
            MonthlyRevenueExpenseChart::class,
        ];
    }
}