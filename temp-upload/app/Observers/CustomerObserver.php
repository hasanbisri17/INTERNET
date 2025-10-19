<?php

namespace App\Observers;

use App\Models\Customer;
use Carbon\Carbon;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Log;

class CustomerObserver
{
    /**
     * Handle the Customer "created" event.
     */
    public function created(Customer $customer): void
    {
        // Jika customer baru dibuat, jadwalkan pembuatan tagihan untuk bulan berikutnya
        if ($customer->is_active && $customer->internet_package_id) {
            try {
                // Dapatkan bulan berikutnya
                $nextMonth = Carbon::now()->addMonth()->format('Y-m');
                
                // Jadwalkan pembuatan tagihan untuk bulan berikutnya
                Log::info("Scheduling bill generation for new customer {$customer->name} for {$nextMonth}");
                
                // Jalankan command untuk generate tagihan bulan berikutnya khusus untuk customer ini
                Artisan::queue('bills:generate-for-customer', [
                    'customer' => $customer->id,
                    '--month' => $nextMonth
                ]);
            } catch (\Exception $e) {
                Log::error("Failed to schedule bill generation for new customer {$customer->name}: {$e->getMessage()}");
            }
        }
    }
}