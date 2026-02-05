<?php

namespace App\Filament\Plugins;

use Filament\PluginServiceProvider;
use Filament\Support\Assets\Js;
use Filament\Support\Facades\FilamentAsset;
use Illuminate\Support\ServiceProvider;

class OpenRouterChatPlugin extends PluginServiceProvider
{
    public static string $name = 'openrouter-chat';

    protected array $styles = [];

    protected array $scripts = [];

    public function boot(): void
    {
        parent::boot();

        // Register Livewire component
        \Livewire\Livewire::component('openrouter-chat', \App\Livewire\OpenRouterChatWidget::class);
    }

    public function register(): void
    {
        parent::register();
    }
}

