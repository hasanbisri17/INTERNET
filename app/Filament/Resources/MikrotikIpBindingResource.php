<?php

namespace App\Filament\Resources;

use App\Filament\Resources\MikrotikIpBindingResource\Pages;
use App\Models\MikrotikIpBinding;
use App\Models\MikrotikDevice;
use App\Models\Customer;
use App\Services\MikrotikIpBindingService;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Notifications\Notification;

class MikrotikIpBindingResource extends Resource
{
    protected static ?string $model = MikrotikIpBinding::class;

    protected static ?string $navigationIcon = 'heroicon-o-link';
    
    protected static ?string $navigationGroup = 'MikroTik';
    
    protected static ?string $navigationLabel = 'IP Bindings';
    
    protected static ?int $navigationSort = 4;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Informasi Perangkat')
                    ->schema([
                        Forms\Components\Select::make('mikrotik_device_id')
                            ->label('Perangkat MikroTik')
                            ->relationship('mikrotikDevice', 'name')
                            ->required()
                            ->searchable()
                            ->preload(),

                        Forms\Components\Select::make('customer_id')
                            ->label('Customer')
                            ->relationship('customer', 'name')
                            ->searchable()
                            ->preload()
                            ->helperText('Pilih customer yang terkait dengan IP Binding ini (opsional)')
                            ->getOptionLabelFromRecordUsing(fn (Customer $record) => "{$record->name} - {$record->phone}")
                            ->placeholder('Pilih customer (opsional)'),
                    ])->columns(2),
                
                Forms\Components\Section::make('Konfigurasi IP Binding')
                    ->schema([
                        Forms\Components\TextInput::make('mac_address')
                            ->label('MAC Address')
                            ->placeholder('00:00:00:00:00:00')
                            ->helperText('Format: XX:XX:XX:XX:XX:XX'),
                        
                        Forms\Components\TextInput::make('address')
                            ->label('IP Address')
                            ->placeholder('192.168.1.100')
                            ->helperText('IP Address yang akan di-bind'),
                        
                        Forms\Components\TextInput::make('to_address')
                            ->label('To Address')
                            ->placeholder('192.168.1.101')
                            ->helperText('IP Address tujuan (opsional)'),
                        
                        Forms\Components\TextInput::make('server')
                            ->label('Hotspot Server')
                            ->default('all')
                            ->helperText('Nama hotspot server'),
                        
                        Forms\Components\Select::make('type')
                            ->label('Type')
                            ->options([
                                'regular' => '🟢 Regular',
                                'bypassed' => '🟡 Bypassed',
                                'blocked' => '🔴 Blocked',
                            ])
                            ->default('regular')
                            ->required()
                            ->helperText('Regular: Normal authentication, Bypassed: Skip authentication, Blocked: Block access'),
                        
                        Forms\Components\Textarea::make('comment')
                            ->label('Komentar')
                            ->maxLength(255)
                            ->columnSpanFull(),
                    ])->columns(2),
                
                Forms\Components\Section::make('Status')
                    ->schema([
                        Forms\Components\Toggle::make('is_disabled')
                            ->label('Disabled')
                            ->helperText('Nonaktifkan IP Binding ini'),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('mikrotikDevice.name')
                    ->label('Perangkat')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('customer.name')
                    ->label('Customer')
                    ->searchable()
                    ->sortable()
                    ->placeholder('—')
                    ->toggleable()
                    ->tooltip(fn (?MikrotikIpBinding $record): ?string => 
                        $record?->customer ? "{$record->customer->name} - {$record->customer->phone}" : null
                    ),
                
                Tables\Columns\TextColumn::make('mac_address')
                    ->label('MAC Address')
                    ->searchable()
                    ->copyable()
                    ->placeholder('—'),
                
                Tables\Columns\TextColumn::make('address')
                    ->label('IP Address')
                    ->searchable()
                    ->copyable()
                    ->placeholder('—'),
                
                Tables\Columns\TextColumn::make('server')
                    ->label('Server')
                    ->badge()
                    ->color('info')
                    ->searchable(),
                
                Tables\Columns\TextColumn::make('type')
                    ->label('Type')
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => match($state) {
                        'regular' => 'Regular',
                        'bypassed' => 'Bypassed',
                        'blocked' => 'Blocked',
                        default => ucfirst($state),
                    })
                    ->color(fn (string $state): string => match($state) {
                        'regular' => 'success',
                        'bypassed' => 'warning',
                        'blocked' => 'danger',
                        default => 'gray',
                    }),
                
                Tables\Columns\TextColumn::make('comment')
                    ->label('Komentar')
                    ->searchable()
                    ->limit(50)
                    ->tooltip(function (Tables\Columns\TextColumn $column): ?string {
                        $state = $column->getState();
                        if (strlen($state) > 50) {
                            return $state;
                        }
                        return null;
                    })
                    ->placeholder('—')
                    ->wrap(),
                
                Tables\Columns\IconColumn::make('is_disabled')
                    ->label('Disabled')
                    ->boolean()
                    ->trueIcon('heroicon-o-x-circle')
                    ->falseIcon('heroicon-o-check-circle')
                    ->trueColor('danger')
                    ->falseColor('success')
                    ->toggleable(),
                
                Tables\Columns\IconColumn::make('is_synced')
                    ->label('Synced')
                    ->boolean()
                    ->trueIcon('heroicon-o-check-circle')
                    ->falseIcon('heroicon-o-x-circle')
                    ->trueColor('success')
                    ->falseColor('danger'),
                
                Tables\Columns\TextColumn::make('last_synced_at')
                    ->label('Last Sync')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('mikrotik_device_id')
                    ->label('Perangkat')
                    ->relationship('mikrotikDevice', 'name')
                    ->searchable()
                    ->preload(),

                Tables\Filters\SelectFilter::make('customer_id')
                    ->label('Customer')
                    ->relationship('customer', 'name')
                    ->searchable()
                    ->preload(),
                
                Tables\Filters\SelectFilter::make('type')
                    ->label('Type')
                    ->options([
                        'regular' => 'Regular',
                        'bypassed' => 'Bypassed',
                        'blocked' => 'Blocked',
                    ]),
                
                Tables\Filters\TernaryFilter::make('is_disabled')
                    ->label('Status')
                    ->placeholder('Semua')
                    ->trueLabel('Disabled')
                    ->falseLabel('Enabled'),
            ])
            ->actions([
                Tables\Actions\ActionGroup::make([
                    // Change Type Actions
                    Tables\Actions\Action::make('change_to_regular')
                        ->label('Change to Regular')
                        ->icon('heroicon-o-arrow-path')
                        ->color('success')
                        ->visible(fn (MikrotikIpBinding $record) => $record->type !== 'regular')
                        ->action(function (MikrotikIpBinding $record) {
                            try {
                                // Update via model (will trigger observer for auto-sync)
                                $record->update(['type' => 'regular']);
                                
                                Notification::make()
                                    ->success()
                                    ->title('Berhasil')
                                    ->body('Type berhasil diubah ke Regular dan auto-sync ke MikroTik')
                                    ->send();
                            } catch (\Exception $e) {
                                Notification::make()
                                    ->danger()
                                    ->title('Error')
                                    ->body($e->getMessage())
                                    ->send();
                            }
                        })
                        ->requiresConfirmation()
                        ->modalHeading('Ubah ke Regular')
                        ->modalDescription('Type akan otomatis di-sync ke MikroTik'),
                    
                    Tables\Actions\Action::make('change_to_bypassed')
                        ->label('Change to Bypassed')
                        ->icon('heroicon-o-arrow-path')
                        ->color('warning')
                        ->visible(fn (MikrotikIpBinding $record) => $record->type !== 'bypassed')
                        ->action(function (MikrotikIpBinding $record) {
                            try {
                                // Update via model (will trigger observer for auto-sync)
                                $record->update(['type' => 'bypassed']);
                                
                                Notification::make()
                                    ->success()
                                    ->title('Berhasil')
                                    ->body('Type berhasil diubah ke Bypassed dan auto-sync ke MikroTik')
                                    ->send();
                            } catch (\Exception $e) {
                                Notification::make()
                                    ->danger()
                                    ->title('Error')
                                    ->body($e->getMessage())
                                    ->send();
                            }
                        })
                        ->requiresConfirmation()
                        ->modalHeading('Ubah ke Bypassed')
                        ->modalDescription('Type akan otomatis di-sync ke MikroTik'),
                    
                    Tables\Actions\Action::make('change_to_blocked')
                        ->label('Change to Blocked')
                        ->icon('heroicon-o-arrow-path')
                        ->color('danger')
                        ->visible(fn (MikrotikIpBinding $record) => $record->type !== 'blocked')
                        ->action(function (MikrotikIpBinding $record) {
                            try {
                                // Update via model (will trigger observer for auto-sync)
                                $record->update(['type' => 'blocked']);
                                
                                Notification::make()
                                    ->success()
                                    ->title('Berhasil')
                                    ->body('Type berhasil diubah ke Blocked dan auto-sync ke MikroTik')
                                    ->send();
                            } catch (\Exception $e) {
                                Notification::make()
                                    ->danger()
                                    ->title('Error')
                                    ->body($e->getMessage())
                                    ->send();
                            }
                        })
                        ->requiresConfirmation()
                        ->modalHeading('Ubah ke Blocked')
                        ->modalDescription('Type akan otomatis di-sync ke MikroTik'),
                    
                    Tables\Actions\EditAction::make(),
                    Tables\Actions\DeleteAction::make()
                        ->successNotificationTitle('IP Binding berhasil dihapus dan auto-sync ke MikroTik'),
                ])
                ->button()
                ->label('Aksi'),
            ])
            ->headerActions([
                Tables\Actions\Action::make('sync_from_mikrotik')
                    ->label('Sync dari MikroTik')
                    ->icon('heroicon-o-arrow-down-tray')
                    ->color('success')
                    ->form([
                        Forms\Components\Select::make('mikrotik_device_id')
                            ->label('Pilih Perangkat MikroTik')
                            ->options(MikrotikDevice::pluck('name', 'id'))
                            ->required()
                            ->searchable(),
                    ])
                    ->action(function (array $data) {
                        $device = MikrotikDevice::find($data['mikrotik_device_id']);
                        
                        if (!$device) {
                            Notification::make()
                                ->danger()
                                ->title('Error')
                                ->body('Perangkat tidak ditemukan')
                                ->send();
                            return;
                        }
                        
                        $service = new MikrotikIpBindingService();
                        $result = $service->syncAllBindings($device);
                        
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
                    }),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListMikrotikIpBindings::route('/'),
            'create' => Pages\CreateMikrotikIpBinding::route('/create'),
            'edit' => Pages\EditMikrotikIpBinding::route('/{record}/edit'),
        ];
    }
}

