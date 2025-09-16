<?php

namespace App\Filament\Resources;

use Filament\Forms;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Spatie\Activitylog\Models\Activity;

class ActivityResource extends Resource
{
    protected static ?string $model = Activity::class;

    protected static ?string $navigationIcon = 'heroicon-o-clipboard-document-check';

    protected static ?string $navigationLabel = 'Log Aktivitas';

    protected static ?string $modelLabel = 'Log Aktivitas';

    protected static ?string $pluralModelLabel = 'Log Aktivitas';

    protected static ?string $navigationGroup = 'Manajement';

    protected static ?int $navigationSort = 99;

    protected static ?string $slug = 'activity-logs';

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('id')
                    ->label('ID')
                    ->sortable()
                    ->toggleable(),
                TextColumn::make('log_name')
                    ->label('Log')
                    ->sortable()
                    ->searchable()
                    ->formatStateUsing(fn ($state) => self::localizeLogName($state))
                    ->toggleable(),
                TextColumn::make('event')
                    ->label('Peristiwa')
                    ->badge()
                    ->sortable()
                    ->color(fn (?string $state) => match ($state) {
                        'created' => 'success',
                        'updated' => 'warning',
                        'deleted' => 'danger',
                        'login' => 'success',
                        'logout' => 'gray',
                        'failed' => 'danger',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn (?string $state) => match ($state) {
                        'created' => 'Dibuat',
                        'updated' => 'Diperbarui',
                        'deleted' => 'Dihapus',
                        'login' => 'Masuk',
                        'logout' => 'Keluar',
                        'failed' => 'Gagal',
                        default => $state,
                    })
                    ->toggleable(),
                TextColumn::make('description')
                    ->label('Deskripsi')
                    ->wrap()
                    ->limit(80)
                    ->searchable()
                    ->toggleable(),
                TextColumn::make('subject_type')
                    ->label('Tipe Subjek')
                    ->formatStateUsing(fn ($state) => $state ? class_basename($state) : '-')
                    ->toggleable(),
                TextColumn::make('subject_id')
                    ->label('ID Subjek')
                    ->sortable()
                    ->toggleable(),
                TextColumn::make('causer')
                    ->label('Pelaku')
                    ->formatStateUsing(function ($state, Activity $record) {
                        if ($record->causer && method_exists($record->causer, '__get')) {
                            $display = $record->causer->name ?? $record->causer->email ?? null;
                            if ($display) {
                                return $display;
                            }
                        }
                        if ($record->causer_id) {
                            return (\class_exists($record->causer_type) ? class_basename($record->causer_type) : $record->causer_type) . ' #' . $record->causer_id;
                        }
                        return '-';
                    })
                    ->toggleable(),
                TextColumn::make('created_at')
                    ->dateTime('Y-m-d H:i')
                    ->label('Tanggal')
                    ->sortable(),
            ])
            ->filters([
                SelectFilter::make('log_name')
                    ->label('Log')
                    ->options(function () {
                        $list = Activity::query()
                            ->select('log_name')
                            ->distinct()
                            ->whereNotNull('log_name')
                            ->orderBy('log_name')
                            ->pluck('log_name', 'log_name')
                            ->toArray();
                        $options = [];
                        foreach ($list as $value => $_) {
                            $options[$value] = self::localizeLogName($value);
                        }
                        return $options;
                    }),
                SelectFilter::make('event')
                    ->label('Peristiwa')
                    ->options([
                        'created' => 'Dibuat',
                        'updated' => 'Diperbarui',
                        'deleted' => 'Dihapus',
                        'login' => 'Masuk',
                        'logout' => 'Keluar',
                        'failed' => 'Gagal',
                    ]),
                Filter::make('created_at')
                    ->label('Tanggal')
                    ->form([
                        DatePicker::make('from')->label('Dari'),
                        DatePicker::make('until')->label('Sampai'),
                    ])
                    ->query(function ($query, array $data) {
                        return $query
                            ->when($data['from'] ?? null, fn ($q, $date) => $q->whereDate('created_at', '>=', $date))
                            ->when($data['until'] ?? null, fn ($q, $date) => $q->whereDate('created_at', '<=', $date));
                    }),
                Filter::make('causer')
                    ->label('Pelaku')
                    ->form([
                        TextInput::make('causer_type')->label('Tipe Pelaku'),
                        TextInput::make('causer_id')->label('ID Pelaku'),
                    ])
                    ->query(function ($query, array $data) {
                        return $query
                            ->when($data['causer_type'] ?? null, fn ($q, $type) => $q->where('causer_type', $type))
                            ->when($data['causer_id'] ?? null, fn ($q, $id) => $q->where('causer_id', $id));
                    }),
                Filter::make('subject')
                    ->label('Subjek')
                    ->form([
                        TextInput::make('subject_type')->label('Tipe Subjek'),
                        TextInput::make('subject_id')->label('ID Subjek'),
                    ])
                    ->query(function ($query, array $data) {
                        return $query
                            ->when($data['subject_type'] ?? null, fn ($q, $type) => $q->where('subject_type', $type))
                            ->when($data['subject_id'] ?? null, fn ($q, $id) => $q->where('subject_id', $id));
                    }),
            ])
            ->actions([])
            ->bulkActions([])
            ->defaultSort('created_at', 'desc');
    }

    private static function localizeLogName(?string $state): string
    {
        $map = [
            'customers' => 'Pelanggan',
            'payments' => 'Pembayaran',
            'cash_transactions' => 'Transaksi Kas',
            'users' => 'Pengguna',
            'internet_packages' => 'Paket Internet',
            'payment_methods' => 'Metode Pembayaran',
            'transaction_categories' => 'Kategori Transaksi',
            'whats_app_messages' => 'Pesan WhatsApp',
            'whatsapp_settings' => 'Pengaturan WhatsApp',
            'whatsapp_templates' => 'Template WhatsApp',
            'whatsapp_scheduled_messages' => 'Pesan Terjadwal WhatsApp',
            'auth' => 'Autentikasi',
        ];
        return $map[$state] ?? ($state ?? '-');
    }

    public static function getPages(): array
    {
        return [
            'index' => ActivityResource\Pages\ListActivities::route('/'),
        ];
    }
}