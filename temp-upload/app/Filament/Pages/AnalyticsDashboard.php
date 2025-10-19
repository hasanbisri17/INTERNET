<?php

namespace App\Filament\Pages;

use Filament\Pages\Dashboard as BaseDashboard;
use App\Filament\Widgets\AnalyticsStatsOverview;
use App\Filament\Widgets\MonthlyRevenueExpenseChart;
use App\Filament\Widgets\PaymentMethodAnalytics;
use App\Filament\Widgets\InternetPackageAnalytics;
use App\Filament\Widgets\PaymentStatusAnalytics;
use App\Filament\Widgets\CustomerGrowthChart;
use App\Filament\Widgets\TransactionCategoryAnalytics;
use App\Models\Setting;

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
        return $this->getConfiguredWidgets('header');
    }

    protected function getFooterWidgets(): array
    {
        return $this->getConfiguredWidgets('footer');
    }

    public function getWidgets(): array
    {
        // Return empty array untuk mencegah auto-discovery widget
        // Widget akan ditampilkan melalui getHeaderWidgets() dan getFooterWidgets()
        return [];
    }

    private function getConfiguredWidgets(string $type): array
    {
        $widgets = Setting::get("analytics_widgets_{$type}", '[]');
        $config = json_decode($widgets, true) ?: [];
        
        // Konversi struktur data dari {widgetName: {enabled, order, title}} ke array
        $widgetArray = [];
        foreach ($config as $widgetName => $widgetConfig) {
            $widgetArray[] = [
                'widget' => $widgetName,
                'enabled' => $widgetConfig['enabled'] ?? true,
                'order' => $widgetConfig['order'] ?? 1,
                'title' => $widgetConfig['title'] ?? null,
            ];
        }
        
        // Filter hanya widget yang enabled dan urutkan berdasarkan order
        $enabledWidgets = collect($widgetArray)
            ->filter(fn($widget) => $widget['enabled'] ?? true)
            ->sortBy('order')
            ->map(function($widget) {
                $widgetClass = $this->getWidgetClass($widget['widget']);
                if ($widgetClass) {
                    // Set custom heading dalam cache jika ada
                    if (!empty($widget['title'])) {
                        cache()->put('analytics_widget_custom_heading_' . $widgetClass, $widget['title'], 3600);
                    }
                    return $widgetClass;
                }
                return null;
            })
            ->filter()
            ->values()
            ->toArray();

        return $enabledWidgets;
    }

    private function getWidgetClass(string $widgetName): ?string
    {
        $widgetMap = [
            'AnalyticsStatsOverview' => AnalyticsStatsOverview::class,
            'PaymentStatusAnalytics' => PaymentStatusAnalytics::class,
            'MonthlyRevenueExpenseChart' => MonthlyRevenueExpenseChart::class,
            'PaymentMethodAnalytics' => PaymentMethodAnalytics::class,
            'InternetPackageAnalytics' => InternetPackageAnalytics::class,
            'CustomerGrowthChart' => CustomerGrowthChart::class,
            'TransactionCategoryAnalytics' => TransactionCategoryAnalytics::class,
        ];

        return $widgetMap[$widgetName] ?? null;
    }

}