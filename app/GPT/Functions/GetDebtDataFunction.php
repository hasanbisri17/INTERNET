<?php

namespace App\GPT\Functions;

use App\Models\Debt;

class GetDebtDataFunction extends BaseFunction
{
    public function name(): string
    {
        return 'get_debt_data';
    }

    public function description(): string
    {
        return 'Mengambil data Hutang berdasarkan status (unpaid, paid, atau all). Gunakan fungsi ini ketika user bertanya tentang hutang, utang, atau kewajiban yang belum dibayar.';
    }

    public function parameters(): array
    {
        return [
            'status_filter' => [
                'type' => 'string',
                'description' => 'Filter status: "unpaid" untuk hutang yang belum lunas, "paid" untuk yang sudah lunas, atau "all" untuk semua',
                'enum' => ['unpaid', 'paid', 'all'],
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
            $statusFilter = $arguments['status_filter'] ?? 'all';

            $query = Debt::query();

            // Apply status filter
            if ($statusFilter === 'unpaid') {
                $query->whereIn('status', ['pending', 'partial', 'overdue']);
            } elseif ($statusFilter === 'paid') {
                $query->where('status', 'paid');
            }

            $total = $query->sum('amount');
            $paid = $query->sum('paid_amount');
            $remaining = $total - $paid;
            $count = $query->count();

            // Get overdue count
            $overdueCount = Debt::where('status', 'overdue')->count();

            return [
                'success' => true,
                'data' => [
                    'total' => $total,
                    'paid' => $paid,
                    'remaining' => $remaining,
                    'count' => $count,
                    'overdue_count' => $overdueCount,
                    'status_filter' => $statusFilter,
                ],
                'formatted' => [
                    'Total Hutang' => 'Rp ' . number_format($total, 0, ',', '.'),
                    'Sudah Dibayar' => 'Rp ' . number_format($paid, 0, ',', '.'),
                    'Sisa Hutang' => 'Rp ' . number_format($remaining, 0, ',', '.'),
                    'Jumlah Hutang' => $count . ' item',
                    'Hutang Terlambat' => $overdueCount . ' item',
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

