<?php

namespace App\Filament\Resources;

use App\Filament\Resources\MikrotikDeviceResource\Pages;
use App\Models\MikrotikDevice;
use App\Services\MikrotikService;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
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
                Tables\Actions\Action::make('test_connection')
                    ->label('Tes Koneksi')
                    ->icon('heroicon-o-link')
                    ->color('success')
                    ->action(function (MikrotikDevice $record) {
                        $service = new MikrotikService();
                        
                        try {
                            if ($service->connect($record)) {
                                $systemInfo = $service->getSystemInfo();
                                $service->disconnect();
                                
                                $message = 'Koneksi berhasil! ' . 
                                    'Perangkat: ' . ($systemInfo[0]['board-name'] ?? 'Unknown') . 
                                    ', Versi: ' . ($systemInfo[0]['version'] ?? 'Unknown');
                                
                                \Filament\Notifications\Notification::make()
                                    ->success()
                                    ->title('Koneksi Berhasil')
                                    ->body($message)
                                    ->duration(5000)
                                    ->send();
                                
                                return [
                                    'success' => true,
                                    'message' => $message,
                                ];
                            } else {
                                $message = 'Koneksi gagal. Periksa kredensial dan alamat IP.';
                                
                                \Filament\Notifications\Notification::make()
                                    ->danger()
                                    ->title('Koneksi Gagal')
                                    ->body($message)
                                    ->duration(5000)
                                    ->send();
                                
                                return [
                                    'success' => false,
                                    'message' => $message,
                                ];
                            }
                        } catch (\Exception $e) {
                            Log::error('MikroTik test connection error: ' . $e->getMessage(), [
                                'device_id' => $record->id,
                            ]);
                            
                            $message = 'Error: ' . $e->getMessage();
                            
                            \Filament\Notifications\Notification::make()
                                ->danger()
                                ->title('Koneksi Gagal')
                                ->body($message)
                                ->duration(5000)
                                ->send();
                            
                            return [
                                'success' => false,
                                'message' => $message,
                            ];
                        }
                    })
                    ->requiresConfirmation()
                    ->modalHeading('Tes Koneksi MikroTik')
                    ->modalDescription('Apakah Anda yakin ingin menguji koneksi ke perangkat ini?')
                    ->modalSubmitActionLabel('Tes Koneksi'),
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
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