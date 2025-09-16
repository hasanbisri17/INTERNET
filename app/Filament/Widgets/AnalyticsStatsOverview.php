<?php

namespace App\Filament\Widgets;

use App\Models\CashTransaction;
use App\Models\Customer;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Card;

class AnalyticsStatsOverview extends BaseWidget
{
    protected ?string $heading = 'Ringkasan (Bulan Ini)';

    protected function getCards(): array
    {
        $now = now();
        $start = $now->copy()->startOfMonth();
        $end = $now->copy()->endOfMonth();

        $income = (float) CashTransaction::whereBetween('date', [$start->toDateString(), $end->toDateString()])
            ->where('type', 'income')
            ->sum('amount');

        $expense = (float) CashTransaction::whereBetween('date', [$start->toDateString(), $end->toDateString()])
            ->where('type', 'expense')
            ->sum('amount');

        $net = $income - $expense;

        $newCustomers = (int) Customer::whereBetween('created_at', [$start, $end])->count();

        // Bulan lalu untuk tren
        $pStart = $start->copy()->subMonthNoOverflow();
        $pEnd = $end->copy()->subMonthNoOverflow();
        $pIncome = (float) CashTransaction::whereBetween('date', [$pStart->toDateString(), $pEnd->toDateString()])->where('type', 'income')->sum('amount');
        $pExpense = (float) CashTransaction::whereBetween('date', [$pStart->toDateString(), $pEnd->toDateString()])->where('type', 'expense')->sum('amount');
        $pNet = $pIncome - $pExpense;
        $pNewCustomers = (int) Customer::whereBetween('created_at', [$pStart, $pEnd])->count();

        $incomeTrend = $pIncome != 0.0 ? (($income - $pIncome) / max($pIncome, 0.00001)) * 100 : null;
        $expenseTrend = $pExpense != 0.0 ? (($expense - $pExpense) / max($pExpense, 0.00001)) * 100 : null;
        $netTrend = $pNet != 0.0 ? (($net - $pNet) / max(abs($pNet), 0.00001)) * 100 : null;
        $custTrend = $pNewCustomers != 0 ? (($newCustomers - $pNewCustomers) / max($pNewCustomers, 1)) * 100 : null;

        $formatCurrency = fn (float $v) => 'Rp ' . number_format($v, 0, ',', '.');

        $incomeCard = Card::make('Pendapatan', $formatCurrency($income))
            ->description(is_null($incomeTrend) ? 'Baru' : sprintf('%+.1f%% vs bln lalu', $incomeTrend))
            // gunakan ikon yang tersedia di paket blade-heroicons: panah tren naik & panah turun biasa
            ->descriptionIcon($incomeTrend === null ? 'heroicon-m-square-2-stack' : ($incomeTrend >= 0 ? 'heroicon-m-arrow-trending-up' : 'heroicon-m-arrow-down'))
            ->color($incomeTrend === null || $incomeTrend >= 0 ? 'success' : 'danger');

        $expenseCard = Card::make('Pengeluaran', $formatCurrency($expense))
            ->description(is_null($expenseTrend) ? 'Baru' : sprintf('%+.1f%% vs bln lalu', $expenseTrend))
            ->descriptionIcon($expenseTrend === null ? 'heroicon-m-square-2-stack' : ($expenseTrend <= 0 ? 'heroicon-m-arrow-down' : 'heroicon-m-arrow-trending-up'))
            ->color($expenseTrend <= 0 ? 'success' : 'warning');

        $netCard = Card::make('Laba Bersih', $formatCurrency($net))
            ->description(is_null($netTrend) ? '—' : sprintf('%+.1f%% vs bln lalu', $netTrend))
            ->descriptionIcon($netTrend === null ? 'heroicon-m-minus' : ($netTrend >= 0 ? 'heroicon-m-arrow-trending-up' : 'heroicon-m-arrow-down'))
            ->color($net >= 0 ? 'success' : 'danger');

        $custCard = Card::make('Pelanggan Baru', number_format($newCustomers))
            ->description(is_null($custTrend) ? '—' : sprintf('%+.1f%% vs bln lalu', $custTrend))
            ->descriptionIcon($custTrend === null ? 'heroicon-m-user' : ($custTrend >= 0 ? 'heroicon-m-user-plus' : 'heroicon-m-user-minus'))
            ->color($custTrend === null || $custTrend >= 0 ? 'success' : 'warning');

        return [
            $incomeCard,
            $expenseCard,
            $netCard,
            $custCard,
        ];
    }
}