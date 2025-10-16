<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use App\Models\Payment;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AnalyticsController extends Controller
{
    /**
     * Tampilkan dashboard analitik
     */
    public function dashboard()
    {
        // Statistik pelanggan
        $totalCustomers = Customer::count();
        $activeCustomers = Customer::where('is_active', true)->count();
        $inactiveCustomers = $totalCustomers - $activeCustomers;
        
        // Statistik pembayaran
        $currentMonth = Carbon::now()->startOfMonth();
        $lastMonth = Carbon::now()->subMonth()->startOfMonth();
        
        $totalRevenue = Payment::where('status', 'paid')->sum('amount');
        $currentMonthRevenue = Payment::where('status', 'paid')
            ->whereMonth('paid_at', $currentMonth->month)
            ->whereYear('paid_at', $currentMonth->year)
            ->sum('amount');
        $lastMonthRevenue = Payment::where('status', 'paid')
            ->whereMonth('paid_at', $lastMonth->month)
            ->whereYear('paid_at', $lastMonth->year)
            ->sum('amount');
            
        $revenueGrowth = $lastMonthRevenue > 0 
            ? (($currentMonthRevenue - $lastMonthRevenue) / $lastMonthRevenue) * 100 
            : 100;
            
        // Statistik tagihan
        $unpaidInvoices = Payment::where('status', 'unpaid')->count();
        $overdueInvoices = Payment::where('status', 'unpaid')
            ->where('due_date', '<', Carbon::now())
            ->count();
            
        // Data untuk grafik pendapatan 6 bulan terakhir
        $sixMonthsRevenue = $this->getRevenueForLastMonths(6);
        
        // Data untuk grafik status pembayaran
        $paymentStatusData = $this->getPaymentStatusData();
        
        // Data untuk grafik paket internet
        $packageDistribution = $this->getPackageDistribution();
        
        return view('admin.analytics.dashboard', compact(
            'totalCustomers',
            'activeCustomers',
            'inactiveCustomers',
            'totalRevenue',
            'currentMonthRevenue',
            'lastMonthRevenue',
            'revenueGrowth',
            'unpaidInvoices',
            'overdueInvoices',
            'sixMonthsRevenue',
            'paymentStatusData',
            'packageDistribution'
        ));
    }
    
    /**
     * Tampilkan laporan pendapatan
     */
    public function revenueReport(Request $request)
    {
        $startDate = $request->input('start_date') 
            ? Carbon::parse($request->input('start_date')) 
            : Carbon::now()->startOfMonth();
            
        $endDate = $request->input('end_date') 
            ? Carbon::parse($request->input('end_date')) 
            : Carbon::now()->endOfMonth();
            
        $payments = Payment::where('status', 'paid')
            ->whereBetween('paid_at', [$startDate, $endDate])
            ->orderBy('paid_at')
            ->get();
            
        $totalRevenue = $payments->sum('amount');
        $averageRevenue = $payments->count() > 0 ? $totalRevenue / $payments->count() : 0;
        
        // Data untuk grafik pendapatan harian
        $dailyRevenue = $payments->groupBy(function($payment) {
            return $payment->paid_at->format('Y-m-d');
        })->map(function($group) {
            return $group->sum('amount');
        });
        
        return view('admin.analytics.revenue', compact(
            'startDate',
            'endDate',
            'payments',
            'totalRevenue',
            'averageRevenue',
            'dailyRevenue'
        ));
    }
    
    /**
     * Tampilkan laporan pelanggan
     */
    public function customerReport()
    {
        $customers = Customer::withCount(['payments as total_payments' => function($query) {
            $query->where('status', 'paid');
        }])
        ->withSum(['payments as total_spent' => function($query) {
            $query->where('status', 'paid');
        }], 'amount')
        ->orderBy('total_spent', 'desc')
        ->get();
        
        // Data untuk grafik pertumbuhan pelanggan
        $customerGrowth = $this->getCustomerGrowthData();
        
        // Data untuk grafik retensi pelanggan
        $customerRetention = $this->getCustomerRetentionData();
        
        return view('admin.analytics.customers', compact(
            'customers',
            'customerGrowth',
            'customerRetention'
        ));
    }
    
    /**
     * Tampilkan laporan tagihan
     */
    public function invoiceReport(Request $request)
    {
        $status = $request->input('status', 'all');
        
        $query = Payment::query();
        
        if ($status !== 'all') {
            $query->where('status', $status);
        }
        
        $payments = $query->orderBy('created_at', 'desc')->paginate(20);
        
        // Statistik tagihan
        $totalInvoices = Payment::count();
        $paidInvoices = Payment::where('status', 'paid')->count();
        $unpaidInvoices = Payment::where('status', 'unpaid')->count();
        $overdueInvoices = Payment::where('status', 'unpaid')
            ->where('due_date', '<', Carbon::now())
            ->count();
            
        $paidPercentage = $totalInvoices > 0 ? ($paidInvoices / $totalInvoices) * 100 : 0;
        
        return view('admin.analytics.invoices', compact(
            'payments',
            'status',
            'totalInvoices',
            'paidInvoices',
            'unpaidInvoices',
            'overdueInvoices',
            'paidPercentage'
        ));
    }
    
    /**
     * Dapatkan data pendapatan untuk beberapa bulan terakhir
     */
    private function getRevenueForLastMonths($months)
    {
        $data = [];
        
        for ($i = $months - 1; $i >= 0; $i--) {
            $date = Carbon::now()->subMonths($i);
            $monthName = $date->format('M Y');
            
            $revenue = Payment::where('status', 'paid')
                ->whereMonth('paid_at', $date->month)
                ->whereYear('paid_at', $date->year)
                ->sum('amount');
                
            $data[$monthName] = $revenue;
        }
        
        return $data;
    }
    
    /**
     * Dapatkan data status pembayaran
     */
    private function getPaymentStatusData()
    {
        $paid = Payment::where('status', 'paid')->count();
        $unpaid = Payment::where('status', 'unpaid')->where('due_date', '>=', Carbon::now())->count();
        $overdue = Payment::where('status', 'unpaid')->where('due_date', '<', Carbon::now())->count();
        
        return [
            'Lunas' => $paid,
            'Belum Lunas' => $unpaid,
            'Terlambat' => $overdue
        ];
    }
    
    /**
     * Dapatkan data distribusi paket internet
     */
    private function getPackageDistribution()
    {
        return DB::table('customers')
            ->join('internet_packages', 'customers.internet_package_id', '=', 'internet_packages.id')
            ->select('internet_packages.name', DB::raw('count(*) as total'))
            ->groupBy('internet_packages.name')
            ->get()
            ->pluck('total', 'name')
            ->toArray();
    }
    
    /**
     * Dapatkan data pertumbuhan pelanggan
     */
    private function getCustomerGrowthData()
    {
        $data = [];
        
        for ($i = 11; $i >= 0; $i--) {
            $date = Carbon::now()->subMonths($i);
            $monthName = $date->format('M Y');
            
            $count = Customer::whereMonth('created_at', $date->month)
                ->whereYear('created_at', $date->year)
                ->count();
                
            $data[$monthName] = $count;
        }
        
        return $data;
    }
    
    /**
     * Dapatkan data retensi pelanggan
     */
    private function getCustomerRetentionData()
    {
        // Implementasi sederhana untuk retensi pelanggan
        // Dalam implementasi nyata, ini akan lebih kompleks
        
        $totalCustomers = Customer::count();
        $activeCustomers = Customer::where('is_active', true)->count();
        
        $retentionRate = $totalCustomers > 0 ? ($activeCustomers / $totalCustomers) * 100 : 0;
        
        return [
            'Aktif' => $activeCustomers,
            'Tidak Aktif' => $totalCustomers - $activeCustomers
        ];
    }
}