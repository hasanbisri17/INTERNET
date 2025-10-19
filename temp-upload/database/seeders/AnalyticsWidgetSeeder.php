<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Setting;

class AnalyticsWidgetSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Konfigurasi widget analisis default
        $defaultWidgets = [
            'analytics_widgets_header' => json_encode([
                'AnalyticsStatsOverview' => [
                    'enabled' => true,
                    'order' => 1,
                    'title' => 'Ringkasan (Bulan Ini)'
                ],
            ]),
            'analytics_widgets_footer' => json_encode([
                'PaymentStatusAnalytics' => [
                    'enabled' => true,
                    'order' => 1,
                    'title' => 'Status Pembayaran'
                ],
                'MonthlyRevenueExpenseChart' => [
                    'enabled' => true,
                    'order' => 2,
                    'title' => 'Pendapatan vs Pengeluaran (12 Bulan)'
                ],
                'PaymentMethodAnalytics' => [
                    'enabled' => true,
                    'order' => 3,
                    'title' => 'Pembayaran per Metode Pembayaran (Bulan Ini)'
                ],
                'InternetPackageAnalytics' => [
                    'enabled' => true,
                    'order' => 4,
                    'title' => 'Paket Internet Paling Populer'
                ],
                'CustomerGrowthChart' => [
                    'enabled' => true,
                    'order' => 5,
                    'title' => 'Tren Pertumbuhan Pelanggan (12 Bulan)'
                ],
                'TransactionCategoryAnalytics' => [
                    'enabled' => true,
                    'order' => 6,
                    'title' => 'Kategori Transaksi Terbesar (Bulan Ini)'
                ],
            ]),
            'analytics_widgets_available' => json_encode([
                'AnalyticsStatsOverview' => [
                    'name' => 'Ringkasan Bulan Ini',
                    'description' => 'Menampilkan ringkasan pendapatan, pengeluaran, laba bersih, dan pelanggan baru',
                    'type' => 'stats',
                    'icon' => 'heroicon-o-chart-bar'
                ],
                'PaymentStatusAnalytics' => [
                    'name' => 'Status Pembayaran',
                    'description' => 'Menampilkan status pembayaran lunas, pending, dan terlambat',
                    'type' => 'stats',
                    'icon' => 'heroicon-o-credit-card'
                ],
                'MonthlyRevenueExpenseChart' => [
                    'name' => 'Grafik Pendapatan vs Pengeluaran',
                    'description' => 'Grafik line chart pendapatan dan pengeluaran 12 bulan terakhir',
                    'type' => 'chart',
                    'icon' => 'heroicon-o-chart-line'
                ],
                'PaymentMethodAnalytics' => [
                    'name' => 'Analisis Metode Pembayaran',
                    'description' => 'Distribusi pembayaran berdasarkan metode pembayaran',
                    'type' => 'chart',
                    'icon' => 'heroicon-o-currency-dollar'
                ],
                'InternetPackageAnalytics' => [
                    'name' => 'Paket Internet Populer',
                    'description' => 'Analisis paket internet yang paling banyak digunakan',
                    'type' => 'chart',
                    'icon' => 'heroicon-o-wifi'
                ],
                'CustomerGrowthChart' => [
                    'name' => 'Tren Pertumbuhan Pelanggan',
                    'description' => 'Grafik pertumbuhan pelanggan baru dan total pelanggan',
                    'type' => 'chart',
                    'icon' => 'heroicon-o-users'
                ],
                'TransactionCategoryAnalytics' => [
                    'name' => 'Kategori Transaksi',
                    'description' => 'Analisis kategori transaksi terbesar',
                    'type' => 'chart',
                    'icon' => 'heroicon-o-document-text'
                ],
            ])
        ];

        foreach ($defaultWidgets as $key => $value) {
            Setting::set($key, $value);
        }
    }
}
