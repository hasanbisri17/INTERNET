<?php

namespace App\Filament\Resources;

use App\Filament\Resources\MikrotikNetwatchResource\Pages;
use App\Models\MikrotikDevice;
use App\Models\MikrotikNetwatch;
use App\Services\MikrotikNetwatchService;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class MikrotikNetwatchResource extends Resource
{
    protected static ?string $model = MikrotikNetwatch::class;

    protected static ?string $navigationIcon = 'heroicon-o-eye';

    protected static ?string $navigationLabel = 'Netwatch';

    protected static ?string $navigationGroup = 'MikroTik';

    protected static ?int $navigationSort = 7;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Select::make('mikrotik_device_id')
                    ->label('MikroTik Device')
                    ->options(MikrotikDevice::where('is_active', true)->pluck('name', 'id'))
                    ->required()
                    ->searchable()
                    ->preload()
                    ->columnSpanFull(),

                Forms\Components\TextInput::make('host')
                    ->label('Host/IP Address')
                    ->required()
                    ->placeholder('192.168.1.1 atau google.com')
                    ->helperText('IP address atau hostname yang akan dimonitor')
                    ->maxLength(255)
                    ->columnSpanFull(),

                Forms\Components\Grid::make(2)
                    ->schema([
                        Forms\Components\TextInput::make('interval')
                            ->label('Interval')
                            ->default('00:01:00')
                            ->required()
                            ->placeholder('00:01:00')
                            ->helperText('Format: HH:MM:SS (default: 1 menit)')
                            ->maxLength(255),

                        Forms\Components\TextInput::make('timeout')
                            ->label('Timeout')
                            ->default('1000ms')
                            ->required()
                            ->placeholder('1000ms')
                            ->helperText('Timeout untuk ping (default: 1000ms)')
                            ->maxLength(255),
                    ]),

                Forms\Components\Textarea::make('up_script')
                    ->label('Up Script')
                    ->placeholder(':log info "Host is UP"')
                    ->helperText('Script yang dijalankan ketika host UP')
                    ->rows(3)
                    ->columnSpanFull(),

                Forms\Components\Textarea::make('down_script')
                    ->label('Down Script')
                    ->placeholder(':log warning "Host is DOWN"')
                    ->helperText('Script yang dijalankan ketika host DOWN')
                    ->rows(3)
                    ->columnSpanFull(),

                Forms\Components\Textarea::make('comment')
                    ->label('Comment')
                    ->placeholder('Monitoring gateway utama')
                    ->maxLength(255)
                    ->columnSpanFull(),

                Forms\Components\Toggle::make('is_disabled')
                    ->label('Disabled')
                    ->helperText('Nonaktifkan netwatch ini')
                    ->default(false),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('mikrotikDevice.name')
                    ->label('Device')
                    ->searchable()
                    ->sortable()
                    ->toggleable(),

                Tables\Columns\TextColumn::make('host')
                    ->label('Host')
                    ->searchable()
                    ->sortable()
                    ->copyable()
                    ->copyMessage('Host berhasil dicopy!')
                    ->weight('bold'),

                Tables\Columns\TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->color(fn (MikrotikNetwatch $record): string => $record->getStatusBadgeColor())
                    ->formatStateUsing(fn (MikrotikNetwatch $record): string => $record->getStatusLabel())
                    ->sortable(),

                Tables\Columns\TextColumn::make('since')
                    ->label('Since')
                    ->dateTime('d M Y, H:i')
                    ->sortable()
                    ->toggleable()
                    ->placeholder('-'),

                Tables\Columns\TextColumn::make('interval')
                    ->label('Interval')
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('timeout')
                    ->label('Timeout')
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('comment')
                    ->label('Comment')
                    ->searchable()
                    ->limit(30)
                    ->tooltip(function (Tables\Columns\TextColumn $column): ?string {
                        $state = $column->getState();
                        if (strlen($state) > 30) {
                            return $state;
                        }
                        return null;
                    })
                    ->placeholder('-')
                    ->wrap()
                    ->toggleable(),

                Tables\Columns\IconColumn::make('is_disabled')
                    ->label('Status')
                    ->boolean()
                    ->trueIcon('heroicon-o-x-circle')
                    ->falseIcon('heroicon-o-check-circle')
                    ->trueColor('danger')
                    ->falseColor('success')
                    ->sortable()
                    ->toggleable(),

                Tables\Columns\IconColumn::make('is_synced')
                    ->label('Synced')
                    ->boolean()
                    ->trueIcon('heroicon-o-check-circle')
                    ->falseIcon('heroicon-o-x-circle')
                    ->trueColor('success')
                    ->falseColor('warning')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('last_synced_at')
                    ->label('Last Synced')
                    ->dateTime('d M Y, H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->placeholder('-'),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                Tables\Filters\SelectFilter::make('mikrotik_device_id')
                    ->label('Device')
                    ->options(MikrotikDevice::where('is_active', true)->pluck('name', 'id'))
                    ->searchable()
                    ->preload(),

                Tables\Filters\SelectFilter::make('status')
                    ->options([
                        'up' => 'Up',
                        'down' => 'Down',
                        'unknown' => 'Unknown',
                    ]),

                Tables\Filters\TernaryFilter::make('is_disabled')
                    ->label('Disabled')
                    ->placeholder('All')
                    ->trueLabel('Only disabled')
                    ->falseLabel('Only enabled'),
            ])
            ->actions([
                Tables\Actions\ActionGroup::make([
                    Tables\Actions\Action::make('enable')
                        ->label('Enable')
                        ->icon('heroicon-o-check-circle')
                        ->color('success')
                        ->visible(fn (MikrotikNetwatch $record) => $record->is_disabled)
                        ->action(function (MikrotikNetwatch $record) {
                            $service = new MikrotikNetwatchService();
                            $result = $service->toggleNetwatch($record->mikrotikDevice, $record, false);
                            
                            if ($result['success']) {
                                Notification::make()
                                    ->success()
                                    ->title('Berhasil')
                                    ->body($result['message'])
                                    ->send();
                            } else {
                                Notification::make()
                                    ->danger()
                                    ->title('Gagal')
                                    ->body($result['message'])
                                    ->send();
                            }
                        }),

                    Tables\Actions\Action::make('disable')
                        ->label('Disable')
                        ->icon('heroicon-o-x-circle')
                        ->color('danger')
                        ->visible(fn (MikrotikNetwatch $record) => !$record->is_disabled)
                        ->action(function (MikrotikNetwatch $record) {
                            $service = new MikrotikNetwatchService();
                            $result = $service->toggleNetwatch($record->mikrotikDevice, $record, true);
                            
                            if ($result['success']) {
                                Notification::make()
                                    ->success()
                                    ->title('Berhasil')
                                    ->body($result['message'])
                                    ->send();
                            } else {
                                Notification::make()
                                    ->danger()
                                    ->title('Gagal')
                                    ->body($result['message'])
                                    ->send();
                            }
                        }),

                    Tables\Actions\EditAction::make(),
                    
                    Tables\Actions\DeleteAction::make()
                        ->after(function (MikrotikNetwatch $record) {
                            // Delete from MikroTik after delete from database
                            if ($record->netwatch_id && $record->mikrotikDevice) {
                                $service = new MikrotikNetwatchService();
                                $service->deleteNetwatch($record->mikrotikDevice, $record);
                            }
                        }),
                ])
                ->button()
                ->label('Aksi'),
            ])
            ->headerActions([
                Tables\Actions\Action::make('sync_from_mikrotik')
                    ->label('Sync dari MikroTik')
                    ->icon('heroicon-o-arrow-path')
                    ->color('info')
                    ->action(function () {
                        $service = new MikrotikNetwatchService();
                        $result = $service->syncAllNetwatch();
                        
                        if ($result['success']) {
                            Notification::make()
                                ->success()
                                ->title('Sync Berhasil')
                                ->body($result['message'])
                                ->send();
                        } else {
                            Notification::make()
                                ->danger()
                                ->title('Sync Gagal')
                                ->body($result['message'])
                                ->send();
                        }
                    })
                    ->requiresConfirmation()
                    ->modalHeading('Sync Netwatch dari MikroTik')
                    ->modalDescription('Akan mengambil semua data netwatch dari semua device MikroTik yang aktif.')
                    ->modalSubmitActionLabel('Ya, Sync Sekarang'),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListMikrotikNetwatches::route('/'),
            'create' => Pages\CreateMikrotikNetwatch::route('/create'),
            'edit' => Pages\EditMikrotikNetwatch::route('/{record}/edit'),
        ];
    }
}

