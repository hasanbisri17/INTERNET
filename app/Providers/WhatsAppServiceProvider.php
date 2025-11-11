<?php

namespace App\Providers;

use App\Services\WhatsAppService;
use Illuminate\Support\ServiceProvider;

class WhatsAppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(WhatsAppService::class, function ($app) {
            return new WhatsAppService();
        });
    }

    public function boot(): void
    {
        // Scheduled tasks are now defined in app/Console/Kernel.php
        // All scheduling should be centralized in one place for easier maintenance
    }
}
