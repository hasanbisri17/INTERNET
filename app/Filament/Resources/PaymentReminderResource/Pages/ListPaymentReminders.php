<?php

namespace App\Filament\Resources\PaymentReminderResource\Pages;

use App\Filament\Resources\PaymentReminderResource;
use App\Filament\Resources\PaymentReminderResource\Widgets\ReminderStatsOverview;
use App\Models\PaymentReminder;
use Filament\Resources\Pages\ListRecords;
use Filament\Resources\Components\Tab;
use Illuminate\Database\Eloquent\Builder;

class ListPaymentReminders extends ListRecords
{
    protected static string $resource = PaymentReminderResource::class;

    protected function getHeaderWidgets(): array
    {
        return [
            ReminderStatsOverview::class,
        ];
    }

    public function getTabs(): array
    {
        return [
            'all' => Tab::make('Semua')
                ->icon('heroicon-m-list-bullet')
                ->badge(PaymentReminder::count()),

            'sent' => Tab::make('Terkirim')
                ->icon('heroicon-m-check-circle')
                ->modifyQueryUsing(fn(Builder $query) => $query->where('status', 'sent'))
                ->badge(PaymentReminder::where('status', 'sent')->count())
                ->badgeColor('success'),

            'failed' => Tab::make('Gagal')
                ->icon('heroicon-m-x-circle')
                ->modifyQueryUsing(fn(Builder $query) => $query->where('status', 'failed'))
                ->badge(PaymentReminder::where('status', 'failed')->count())
                ->badgeColor('danger'),

            'pending' => Tab::make('Pending')
                ->icon('heroicon-m-clock')
                ->modifyQueryUsing(fn(Builder $query) => $query->where('status', 'pending'))
                ->badge(PaymentReminder::where('status', 'pending')->count())
                ->badgeColor('warning'),
        ];
    }
}
