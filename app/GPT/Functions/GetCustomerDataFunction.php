<?php

namespace App\GPT\Functions;

use App\Models\Customer;

class GetCustomerDataFunction extends BaseFunction
{
    public function name(): string
    {
        return 'get_customer_data';
    }

    public function description(): string
    {
        return 'Mengambil data Customer (total, aktif, suspended). Gunakan fungsi ini ketika user bertanya tentang customer, pelanggan, atau jumlah customer.';
    }

    public function parameters(): array
    {
        return [
            'status_filter' => [
                'type' => 'string',
                'description' => 'Filter status: "active" untuk customer aktif, "suspended" untuk yang ditangguhkan, atau "all" untuk semua',
                'enum' => ['active', 'suspended', 'all'],
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

            $query = Customer::query();

            // Apply status filter
            if ($statusFilter === 'active') {
                $query->where('status', 'active');
            } elseif ($statusFilter === 'suspended') {
                $query->where('status', 'suspended');
            }

            $count = $query->count();
            $totalCount = Customer::count();
            $activeCount = Customer::where('status', 'active')->count();
            $suspendedCount = Customer::where('status', 'suspended')->count();

            return [
                'success' => true,
                'data' => [
                    'total' => $totalCount,
                    'active' => $activeCount,
                    'suspended' => $suspendedCount,
                    'filtered_count' => $count,
                    'status_filter' => $statusFilter,
                ],
                'formatted' => [
                    'Total Customer' => $totalCount . ' customer',
                    'Customer Aktif' => $activeCount . ' customer',
                    'Customer Suspended' => $suspendedCount . ' customer',
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

