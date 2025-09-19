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
        // Menggunakan nama aplikasi dari pengaturan jika tersedia
        if (Schema::hasTable('settings')) {
            $appName = \App\Models\Setting::get('app_name');
            if ($appName) {
                config(['app.name' => $appName]);
            }
        }
    }
}
