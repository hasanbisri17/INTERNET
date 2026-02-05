<?php

namespace App\GPT\Functions;

use App\Models\Receivable;

class GetReceivableDataFunction extends BaseFunction
{
    public function name(): string
    {
        return 'get_receivable_data';
    }

    public function description(): string
    {
        return 'Mengambil data Piutang berdasarkan status (unpaid, paid, atau all). Gunakan fungsi ini ketika user bertanya tentang piutang, tagihan yang belum dibayar customer, atau receivable.';
    }

    public function parameters(): array
    {
        return [
            'status_filter' => [
                'type' => 'string',
                'description' => 'Filter status: "unpaid" untuk piutang yang belum lunas, "paid" untuk yang sudah lunas, atau "all" untuk semua',
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

            $query = Receivable::query();

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
            $overdueCount = Receivable::where('status', 'overdue')->count();

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
                    'Total Piutang' => 'Rp ' . number_format($total, 0, ',', '.'),
                    'Sudah Diterima' => 'Rp ' . number_format($paid, 0, ',', '.'),
                    'Sisa Piutang' => 'Rp ' . number_format($remaining, 0, ',', '.'),
                    'Jumlah Piutang' => $count . ' item',
                    'Piutang Terlambat' => $overdueCount . ' item',
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

