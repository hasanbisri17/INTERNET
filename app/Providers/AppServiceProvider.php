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
        
        // Mendaftarkan observer untuk model MikrotikIpBinding (with loop protection)
        \App\Models\MikrotikIpBinding::observe(\App\Observers\MikrotikIpBindingObserver::class);
        
        // Mendaftarkan observer untuk model DebtPayment (handle deletion - void cash transaction and reduce paid_amount)
        \App\Models\DebtPayment::observe(\App\Observers\DebtPaymentObserver::class);
        
        // Mendaftarkan observer untuk model ReceivablePayment (handle deletion - void cash transaction and reduce paid_amount)
        \App\Models\ReceivablePayment::observe(\App\Observers\ReceivablePaymentObserver::class);
        
        // Mendaftarkan observer untuk model Debt (handle deletion - void cash transaction)
        \App\Models\Debt::observe(\App\Observers\DebtObserver::class);
        
        // Mendaftarkan observer untuk model Receivable (handle deletion if needed)
        \App\Models\Receivable::observe(\App\Observers\ReceivableObserver::class);
        
        // Add chat widget to all Filament views using view composer
        \Illuminate\Support\Facades\View::composer('filament.*', function ($view) {
            $view->with('showChatWidget', true);
        });
    }
}
