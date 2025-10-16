<?php

namespace App\Filament\Resources\BroadcastCampaignResource\Pages;

use App\Filament\Resources\BroadcastCampaignResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListBroadcastCampaigns extends ListRecords
{
    protected static string $resource = BroadcastCampaignResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('buat_broadcast')
                ->label('Buat Broadcast')
                ->icon('heroicon-o-plus-circle')
                ->url(route('filament.admin.pages.whats-app-broadcast'))
                ->color('success'),
        ];
    }
}

