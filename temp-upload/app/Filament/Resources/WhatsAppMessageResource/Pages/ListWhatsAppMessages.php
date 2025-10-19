<?php

namespace App\Filament\Resources\WhatsAppMessageResource\Pages;

use App\Filament\Resources\WhatsAppMessageResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Filament\Resources\Components\Tab;
use Illuminate\Database\Eloquent\Builder;

class ListWhatsAppMessages extends ListRecords
{
    protected static string $resource = WhatsAppMessageResource::class;

    protected function getHeaderActions(): array
    {
        return [
            //
        ];
    }

    public function getTabs(): array
    {
        return [
            'all' => Tab::make('Semua Pesan')
                ->badge(fn () => \App\Models\WhatsAppMessage::where('message_type', '!=', 'broadcast')->count()),
            
            'sent' => Tab::make('Terkirim')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('status', 'sent'))
                ->badge(fn () => \App\Models\WhatsAppMessage::where('message_type', '!=', 'broadcast')->where('status', 'sent')->count())
                ->badgeColor('success'),
            
            'failed' => Tab::make('Gagal')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('status', 'failed'))
                ->badge(fn () => \App\Models\WhatsAppMessage::where('message_type', '!=', 'broadcast')->where('status', 'failed')->count())
                ->badgeColor('danger'),
            
            'billing_new' => Tab::make('Tagihan Baru')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('message_type', 'billing.new'))
                ->badge(fn () => \App\Models\WhatsAppMessage::where('message_type', 'billing.new')->count())
                ->badgeColor('info'),
            
            'billing_reminder' => Tab::make('Pengingat')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('message_type', 'billing.reminder'))
                ->badge(fn () => \App\Models\WhatsAppMessage::where('message_type', 'billing.reminder')->count())
                ->badgeColor('warning'),
            
            'billing_overdue' => Tab::make('Terlambat')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('message_type', 'billing.overdue'))
                ->badge(fn () => \App\Models\WhatsAppMessage::where('message_type', 'billing.overdue')->count())
                ->badgeColor('danger'),
            
            'billing_paid' => Tab::make('Konfirmasi Bayar')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('message_type', 'billing.paid'))
                ->badge(fn () => \App\Models\WhatsAppMessage::where('message_type', 'billing.paid')->count())
                ->badgeColor('success'),
        ];
    }
}

