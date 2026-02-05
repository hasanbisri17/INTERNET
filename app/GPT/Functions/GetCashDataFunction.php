<?php

namespace App\GPT\Functions;

use App\Models\CashTransaction;

class GetCashDataFunction extends BaseFunction
{
    public function name(): string
    {
        return 'get_cash_data';
    }

    public function description(): string
    {
        return 'Mengambil data KAS (saldo, pemasukan, pengeluaran) berdasarkan filter tanggal. Gunakan fungsi ini ketika user bertanya tentang saldo KAS, pemasukan, pengeluaran, atau transaksi kas.';
    }

    public function parameters(): array
    {
        return [
            'date_filter' => [
                'type' => 'string',
                'description' => 'Filter tanggal: "today" untuk hari ini, "this_week" untuk minggu ini, "this_month" untuk bulan ini, atau "all" untuk semua data',
                'enum' => ['today', 'this_week', 'this_month', 'all'],
            ],
        ];
    }

    protected function requiredParameters(): array
    {
        return [];
    }

    public function execute(array $arguments): array
    {
        try {
            $dateFilter = $arguments['date_filter'] ?? 'all';

            $query = CashTransaction::whereNull('voided_at');

            // Apply date filter
            if ($dateFilter === 'today') {
                $query->whereDate('date', today());
            } elseif ($dateFilter === 'this_week') {
                $query->whereBetween('date', [now()->startOfWeek(), now()->endOfWeek()]);
            } elseif ($dateFilter === 'this_month') {
                $query->whereMonth('date', now()->month)
                    ->whereYear('date', now()->year);
            }

            $income = (clone $query)->where('type', 'income')->sum('amount');
            $expense = (clone $query)->where('type', 'expense')->sum('amount');
            $balance = $income - $expense;
            $incomeCount = (clone $query)->where('type', 'income')->count();
            $expenseCount = (clone $query)->where('type', 'expense')->count();

            return [
                'success' => true,
                'data' => [
                    'balance' => $balance,
                    'income' => $income,
                    'expense' => $expense,
                    'income_count' => $incomeCount,
                    'expense_count' => $expenseCount,
                    'date_filter' => $dateFilter,
                ],
                'formatted' => [
                    'Saldo KAS' => 'Rp ' . number_format($balance, 0, ',', '.'),
                    'Total Pemasukan' => 'Rp ' . number_format($income, 0, ',', '.'),
                    'Total Pengeluaran' => 'Rp ' . number_format($expense, 0, ',', '.'),
                    'Jumlah Transaksi Pemasukan' => $incomeCount,
                    'Jumlah Transaksi Pengeluaran' => $expenseCount,
                ],
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }
}

