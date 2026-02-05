<?php

namespace App\GPT\Functions;

use App\Models\CashTransaction;
use App\Models\Debt;
use App\Models\Receivable;
use App\Models\Payment;
use App\Models\Customer;

class GetGeneralDataFunction extends BaseFunction
{
    public function name(): string
    {
        return 'get_general_data';
    }

    public function description(): string
    {
        return 'Mengambil data umum/overview dari semua modul (KAS, Hutang, Piutang, Tagihan, Customer). Gunakan fungsi ini ketika user bertanya tentang ringkasan, overview, atau dashboard data.';
    }

    public function parameters(): array
    {
        return [];
    }

    protected function requiredParameters(): array
    {
        return [];
    }

    public function execute(array $arguments): array
    {
        try {
            // Cash data
            $cashBalance = CashTransaction::whereNull('voided_at')
                ->selectRaw('SUM(CASE WHEN type = "income" THEN amount ELSE -amount END) as balance')
                ->value('balance') ?? 0;
            $cashIncome = CashTransaction::whereNull('voided_at')->where('type', 'income')->sum('amount');
            $cashExpense = CashTransaction::whereNull('voided_at')->where('type', 'expense')->sum('amount');

            // Debt data
            $debtTotal = Debt::sum('amount');
            $debtPaid = Debt::sum('paid_amount');
            $debtRemaining = $debtTotal - $debtPaid;
            $debtCount = Debt::count();
            $debtUnpaidCount = Debt::whereIn('status', ['pending', 'partial', 'overdue'])->count();
            $debtOverdueCount = Debt::where('status', 'overdue')->count();

            // Receivable data
            $receivableTotal = Receivable::sum('amount');
            $receivablePaid = Receivable::sum('paid_amount');
            $receivableRemaining = $receivableTotal - $receivablePaid;
            $receivableCount = Receivable::count();
            $receivableUnpaidCount = Receivable::whereIn('status', ['pending', 'partial', 'overdue'])->count();
            $receivableOverdueCount = Receivable::where('status', 'overdue')->count();

            // Payment data
            $paymentTotal = Payment::sum('amount');
            $paymentCount = Payment::count();
            $paymentUnpaidCount = Payment::whereIn('status', ['pending', 'overdue'])->count();
            $paymentPaidCount = Payment::whereIn('status', ['paid', 'confirmed'])->count();
            $paymentOverdueCount = Payment::where('status', 'overdue')->count();

            // Customer data
            $customerCount = Customer::count();
            $customerActiveCount = Customer::where('status', 'active')->count();
            $customerSuspendedCount = Customer::where('status', 'suspended')->count();

            return [
                'success' => true,
                'data' => [
                    'cash' => [
                        'balance' => $cashBalance,
                        'income' => $cashIncome,
                        'expense' => $cashExpense,
                    ],
                    'debt' => [
                        'total' => $debtTotal,
                        'paid' => $debtPaid,
                        'remaining' => $debtRemaining,
                        'count' => $debtCount,
                        'unpaid_count' => $debtUnpaidCount,
                        'overdue_count' => $debtOverdueCount,
                    ],
                    'receivable' => [
                        'total' => $receivableTotal,
                        'paid' => $receivablePaid,
                        'remaining' => $receivableRemaining,
                        'count' => $receivableCount,
                        'unpaid_count' => $receivableUnpaidCount,
                        'overdue_count' => $receivableOverdueCount,
                    ],
                    'payment' => [
                        'total' => $paymentTotal,
                        'count' => $paymentCount,
                        'unpaid_count' => $paymentUnpaidCount,
                        'paid_count' => $paymentPaidCount,
                        'overdue_count' => $paymentOverdueCount,
                    ],
                    'customer' => [
                        'total' => $customerCount,
                        'active' => $customerActiveCount,
                        'suspended' => $customerSuspendedCount,
                    ],
                ],
                'formatted' => [
                    'KAS' => [
                        'Saldo' => 'Rp ' . number_format($cashBalance, 0, ',', '.'),
                        'Pemasukan' => 'Rp ' . number_format($cashIncome, 0, ',', '.'),
                        'Pengeluaran' => 'Rp ' . number_format($cashExpense, 0, ',', '.'),
                    ],
                    'Hutang' => [
                        'Total' => 'Rp ' . number_format($debtTotal, 0, ',', '.'),
                        'Sisa' => 'Rp ' . number_format($debtRemaining, 0, ',', '.'),
                        'Jumlah' => $debtCount . ' item',
                        'Belum Lunas' => $debtUnpaidCount . ' item',
                        'Terlambat' => $debtOverdueCount . ' item',
                    ],
                    'Piutang' => [
                        'Total' => 'Rp ' . number_format($receivableTotal, 0, ',', '.'),
                        'Sisa' => 'Rp ' . number_format($receivableRemaining, 0, ',', '.'),
                        'Jumlah' => $receivableCount . ' item',
                        'Belum Lunas' => $receivableUnpaidCount . ' item',
                        'Terlambat' => $receivableOverdueCount . ' item',
                    ],
                    'Tagihan' => [
                        'Total' => 'Rp ' . number_format($paymentTotal, 0, ',', '.'),
                        'Jumlah' => $paymentCount . ' item',
                        'Belum Dibayar' => $paymentUnpaidCount . ' item',
                        'Sudah Dibayar' => $paymentPaidCount . ' item',
                        'Terlambat' => $paymentOverdueCount . ' item',
                    ],
                    'Customer' => [
                        'Total' => $customerCount . ' customer',
                        'Aktif' => $customerActiveCount . ' customer',
                        'Suspended' => $customerSuspendedCount . ' customer',
                    ],
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

