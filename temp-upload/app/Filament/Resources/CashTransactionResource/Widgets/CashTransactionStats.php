<?php

namespace App\Filament\Resources\CashTransactionResource\Widgets;

use App\Models\CashTransaction;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Facades\Schema;

class CashTransactionStats extends BaseWidget
{
    protected function getStats(): array
    {
        if (Schema::hasColumn('cash_transactions', 'voided_at')) {
            $income = CashTransaction::where('type', 'income')->whereNull('voided_at')->sum('amount');
            $expense = CashTransaction::where('type', 'expense')->whereNull('voided_at')->sum('amount');
        } else {
            $income = CashTransaction::where('type', 'income')->sum('amount');
            $expense = CashTransaction::where('type', 'expense')->sum('amount');
        }
        $balance = $income - $expense;

        return [
            Stat::make('Total Pemasukan', 'Rp ' . number_format($income, 2))
                ->description('Total pemasukan (tanpa transaksi void)')
                ->color('success'),
            Stat::make('Total Pengeluaran', 'Rp ' . number_format($expense, 2))
                ->description('Total pengeluaran (tanpa transaksi void)')
                ->color('danger'),
            Stat::make('Saldo', 'Rp ' . number_format($balance, 2))
                ->description('Saldo saat ini (tanpa transaksi void)')
                ->color($balance >= 0 ? 'success' : 'danger'),
        ];
    }
}
