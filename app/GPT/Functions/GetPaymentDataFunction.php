<?php

namespace App\GPT\Functions;

use App\Models\Payment;

class GetPaymentDataFunction extends BaseFunction
{
    public function name(): string
    {
        return 'get_payment_data';
    }

    public function description(): string
    {
        return 'Mengambil data Tagihan/Payment berdasarkan status dan tanggal. Gunakan fungsi ini ketika user bertanya tentang tagihan, payment, atau bill.';
    }

    public function parameters(): array
    {
        return [
            'status_filter' => [
                'type' => 'string',
                'description' => 'Filter status: "unpaid" untuk tagihan yang belum dibayar, "paid" untuk yang sudah dibayar, atau "all" untuk semua',
                'enum' => ['unpaid', 'paid', 'all'],
            ],
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
            $statusFilter = $arguments['status_filter'] ?? 'all';
            $dateFilter = $arguments['date_filter'] ?? 'all';

            $query = Payment::query();

            // Apply status filter
            if ($statusFilter === 'unpaid') {
                $query->whereIn('status', ['pending', 'overdue']);
            } elseif ($statusFilter === 'paid') {
                $query->whereIn('status', ['paid', 'confirmed']);
            }

            // Apply date filter
            if ($dateFilter === 'today') {
                $query->whereDate('created_at', today());
            } elseif ($dateFilter === 'this_week') {
                $query->whereBetween('created_at', [now()->startOfWeek(), now()->endOfWeek()]);
            } elseif ($dateFilter === 'this_month') {
                $query->whereMonth('created_at', now()->month)
                    ->whereYear('created_at', now()->year);
            }

            $total = $query->sum('amount');
            $count = $query->count();

            // Get breakdown by status
            $unpaidTotal = (clone $query)->whereIn('status', ['pending', 'overdue'])->sum('amount');
            $paidTotal = (clone $query)->whereIn('status', ['paid', 'confirmed'])->sum('amount');
            $unpaidCount = (clone $query)->whereIn('status', ['pending', 'overdue'])->count();
            $paidCount = (clone $query)->whereIn('status', ['paid', 'confirmed'])->count();
            $overdueCount = (clone $query)->where('status', 'overdue')->count();

            return [
                'success' => true,
                'data' => [
                    'total' => $total,
                    'count' => $count,
                    'unpaid_total' => $unpaidTotal,
                    'paid_total' => $paidTotal,
                    'unpaid_count' => $unpaidCount,
                    'paid_count' => $paidCount,
                    'overdue_count' => $overdueCount,
                    'status_filter' => $statusFilter,
                    'date_filter' => $dateFilter,
                ],
                'formatted' => [
                    'Total Tagihan' => 'Rp ' . number_format($total, 0, ',', '.'),
                    'Jumlah Tagihan' => $count . ' item',
                    'Belum Dibayar' => 'Rp ' . number_format($unpaidTotal, 0, ',', '.') . ' (' . $unpaidCount . ' item)',
                    'Sudah Dibayar' => 'Rp ' . number_format($paidTotal, 0, ',', '.') . ' (' . $paidCount . ' item)',
                    'Terlambat' => $overdueCount . ' item',
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

