<?php

namespace App\Filament\Resources;

use App\Filament\Resources\MikrotikDeviceResource\Pages;
use App\Models\MikrotikDevice;
use App\Services\MikrotikApiService;
use App\Services\MikrotikProfileService;
use App\Services\MikrotikPppService;
use App\Services\MikrotikMonitoringService;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\Log;

class MikrotikDeviceResource extends Resource
{
    protected static ?string $model = MikrotikDevice::class;

    protected static ?string $navigationIcon = 'heroicon-o-server';
    
    protected static ?string $navigationGroup = 'Konfigurasi Sistem';
    
    protected static ?string $navigationLabel = 'Perangkat MikroTik';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Informasi Perangkat')
                    ->schema([
                        Forms\Components\TextInput::make('name')
                            ->label('Nama Perangkat')
                            ->required()
                            ->maxLength(255),
                        Forms\Components\TextInput::make('ip_address')
                            ->label('Alamat IP')
                            ->required()
                            ->maxLength(255)
                            ->rules(['required'])
                            ->validationAttribute('Alamat IP/Hostname'),
                        Forms\Components\TextInput::make('port')
                            ->label('Port')
                            ->required()
                            ->numeric()
                            ->default(8728),
                    ])->columns(2),
                
                Forms\Components\Section::make('Kredensial')
                    ->schema([
                        Forms\Components\TextInput::make('username')
                            ->label('Username')
                            ->required()
                            ->maxLength(255),
                        Forms\Components\TextInput::make('password')
                            ->label('Password')
                            ->required()
                            ->password()
                            ->maxLength(255)
                            ->dehydrated(fn ($state) => filled($state))
                            ->dehydrateStateUsing(fn ($state) => $state)
                            ->required(fn (string $context): bool => $context === 'create'),
                        Forms\Components\Toggle::make('use_ssl')
                            ->label('Gunakan SSL')
                            ->default(false),
                    ])->columns(2),
                
                Forms\Components\Section::make('Status & Deskripsi')
                    ->schema([
                        Forms\Components\Toggle::make('is_active')
                            ->label('Aktif')
                            ->default(true),
                        Forms\Components\Textarea::make('description')
                            ->label('Deskripsi')
                            ->maxLength(65535)
                            ->columnSpanFull(),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label('Nama Perangkat')
                    ->searchable(),
                Tables\Columns\TextColumn::make('ip_address')
                    ->label('Alamat IP')
                    ->searchable(),
                Tables\Columns\TextColumn::make('port')
                    ->label('Port')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\IconColumn::make('use_ssl')
                    ->label('SSL')
                    ->boolean(),
                Tables\Columns\IconColumn::make('is_active')
                    ->label('Aktif')
                    ->boolean(),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Dibuat Pada')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('updated_at')
                    ->label('Diperbarui Pada')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('is_active')
                    ->label('Status')
                    ->options([
                        '1' => 'Aktif',
                        '0' => 'Tidak Aktif',
                    ]),
            ])
            ->actions([
                Tables\Actions\ActionGroup::make([
                    Tables\Actions\Action::make('test_connection')
                        ->label('Tes Koneksi')
                        ->icon('heroicon-o-link')
                        ->color('success')
                        ->action(function (MikrotikDevice $record) {
                            $apiService = new MikrotikApiService();
                            
                            try {
                                $result = $apiService->testConnection($record);
                                
                                if ($result['success']) {
                                    $data = $result['data'];
                                    $message = 'Koneksi berhasil! ' . 
                                        'Perangkat: ' . ($data['board-name'] ?? 'Unknown') . 
                                        ', Versi: ' . ($data['version'] ?? 'Unknown');
                                    
                                    Notification::make()
                                        ->success()
                                        ->title('Koneksi Berhasil')
                                        ->body($message)
                                        ->send();
                                } else {
                                    Notification::make()
                                        ->danger()
                                        ->title('Koneksi Gagal')
                                        ->body($result['message'])
                                        ->send();
                                }
                            } catch (\Exception $e) {
                                Log::error('MikroTik test connection error', [
                                    'device_id' => $record->id,
                                    'error' => $e->getMessage(),
                                ]);
                                
                                Notification::make()
                                    ->danger()
                                    ->title('Error')
                                    ->body($e->getMessage())
                                    ->send();
                            }
                        })
                        ->requiresConfirmation()
                        ->modalHeading('Tes Koneksi MikroTik')
                        ->modalDescription('Menguji koneksi ke perangkat MikroTik')
                        ->modalSubmitActionLabel('Tes Koneksi'),
                    
                    Tables\Actions\Action::make('sync_profiles')
                        ->label('Sinkronisasi Profil')
                        ->icon('heroicon-o-arrow-path')
                        ->color('info')
                        ->action(function (MikrotikDevice $record) {
                            $profileService = new MikrotikProfileService();
                            
                            try {
                                $result = $profileService->syncAllProfiles($record);
                                
                                if ($result['success']) {
                                    Notification::make()
                                        ->success()
                                        ->title('Sinkronisasi Berhasil')
                                        ->body("Berhasil sinkronisasi {$result['synced']} profil")
                                        ->send();
                                } else {
                                    Notification::make()
                                        ->danger()
                                        ->title('Sinkronisasi Gagal')
                                        ->body($result['message'])
                                        ->send();
                                }
                            } catch (\Exception $e) {
                                Notification::make()
                                    ->danger()
                                    ->title('Error')
                                    ->body($e->getMessage())
                                    ->send();
                            }
                        })
                        ->requiresConfirmation()
                        ->modalHeading('Sinkronisasi Profil PPP')
                        ->modalDescription('Mengimpor semua profil PPP dari MikroTik ke database')
                        ->modalSubmitActionLabel('Sinkronisasi'),
                    
                    Tables\Actions\Action::make('check_monitoring')
                        ->label('Cek Status')
                        ->icon('heroicon-o-chart-bar')
                        ->color('warning')
                        ->action(function (MikrotikDevice $record) {
                            $monitoringService = new MikrotikMonitoringService();
                            
                            try {
                                $result = $monitoringService->checkDeviceStatus($record);
                                
                                if ($result['success']) {
                                    $data = $result['data'];
                                    $message = "Status: {$result['status']}\n" .
                                        "CPU Load: {$data->cpu_load}\n" .
                                        "Active Users: {$data->active_users}\n" .
                                        "Uptime: {$data->uptime}";
                                    
                                    Notification::make()
                                        ->success()
                                        ->title('Status Device')
                                        ->body($message)
                                        ->send();
                                } else {
                                    Notification::make()
                                        ->danger()
                                        ->title('Device Offline')
                                        ->body($result['message'])
                                        ->send();
                                }
                            } catch (\Exception $e) {
                                Notification::make()
                                    ->danger()
                                    ->title('Error')
                                    ->body($e->getMessage())
                                    ->send();
                            }
                        }),
                    
                    Tables\Actions\EditAction::make(),
                    Tables\Actions\DeleteAction::make(),
                ])
                ->button()
                ->label('Aksi'),
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
            'index' => Pages\ListMikrotikDevices::route('/'),
            'create' => Pages\CreateMikrotikDevice::route('/create'),
            'edit' => Pages\EditMikrotikDevice::route('/{record}/edit'),
        ];
    }
}