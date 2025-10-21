<?php

namespace App\Filament\Resources\PaymentResource\Pages;

use App\Filament\Resources\PaymentResource;
use Filament\Actions;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Pages\ListRecords;
use Filament\Notifications\Notification;

class ListPayments extends ListRecords
{
    protected static string $resource = PaymentResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()
                ->label('Buat Tagihan'),
            Actions\Action::make('generateMonthlyBills')
                ->label('Generate Tagihan Bulanan')
                ->form([
                    Select::make('month')
                        ->label('Bulan Tagihan')
                        ->options([
                            1 => 'Januari',
                            2 => 'Februari',
                            3 => 'Maret',
                            4 => 'April',
                            5 => 'Mei',
                            6 => 'Juni',
                            7 => 'Juli',
                            8 => 'Agustus',
                            9 => 'September',
                            10 => 'Oktober',
                            11 => 'November',
                            12 => 'Desember',
                        ])
                        ->default(fn () => now()->month)
                        ->required()
                        ->native(false)
                        ->columnSpan(1),
                    TextInput::make('year')
                        ->label('Tahun Tagihan')
                        ->numeric()
                        ->default(fn () => now()->year)
                        ->required()
                        ->minValue(2020)
                        ->maxValue(2100)
                        ->columnSpan(1),
                ])
                ->action(function (array $data): void {
                    try {
                        // Construct month format Y-m from month and year
                        $month = sprintf('%04d-%02d', $data['year'], $data['month']);
                        PaymentResource::generateMonthlyBills($month, $data['month'], $data['year']);
                        
                        Notification::make()
                            ->title('Tagihan bulanan berhasil dibuat')
                            ->success()
                            ->send();
                    } catch (\Exception $e) {
                        Notification::make()
                            ->title('Gagal membuat tagihan')
                            ->body($e->getMessage())
                            ->danger()
                            ->send();
                    }
                })
                ->modalHeading('Generate Tagihan Bulanan')
                ->modalDescription('Pilih bulan dan tahun untuk membuat tagihan baru. Sistem akan melewati pelanggan yang sudah memiliki tagihan di bulan yang dipilih.'),
        ];
    }
}
