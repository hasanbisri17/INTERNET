<?php

namespace App\Filament\Widgets;

use Filament\Widgets\Widget;
use App\Livewire\ChatWidget as ChatWidgetComponent;

class ChatWidget extends Widget
{
    protected static string $view = 'filament.widgets.chat-widget';
    
    protected int | string | array $columnSpan = 'full';
    
    protected static bool $isLazy = false;
    
    // Don't show in widget list, only render
    protected static bool $isDiscovered = false;
    
    protected static ?int $sort = 9999;
    
    public function getViewData(): array
    {
        return [];
    }
}




