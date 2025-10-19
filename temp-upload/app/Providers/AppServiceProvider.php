<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Schema;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Registrasi komponen Livewire
        // MikrotikTrafficChart telah dihapus
        
        // Menggunakan nama aplikasi dan timezone dari pengaturan jika tersedia
        if (Schema::hasTable('settings')) {
            // Set app name
            $appName = \App\Models\Setting::get('app_name');
            if ($appName) {
                config(['app.name' => $appName]);
            }
            
            // Set timezone
            $timezone = \App\Models\Setting::get('app_timezone', 'Asia/Jakarta');
            if ($timezone) {
                config(['app.timezone' => $timezone]);
                date_default_timezone_set($timezone);
            }
        }
        
        // Mendaftarkan observer untuk model Customer
        \App\Models\Customer::observe(\App\Observers\CustomerObserver::class);
        
        // Mendaftarkan observer untuk model Payment (auto unsuspend via n8n)
        \App\Models\Payment::observe(\App\Observers\PaymentObserver::class);
    }
}
