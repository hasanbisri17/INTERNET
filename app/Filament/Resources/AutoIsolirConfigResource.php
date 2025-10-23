<?php

namespace App\Filament\Resources;

use App\Filament\Resources\AutoIsolirConfigResource\Pages;
use App\Models\AutoIsolirConfig;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class AutoIsolirConfigResource extends Resource
{
    protected static ?string $model = AutoIsolirConfig::class;

    protected static ?string $navigationIcon = 'heroicon-o-shield-exclamation';
    
    protected static ?string $navigationGroup = 'MikroTik';
    
    protected static ?string $navigationLabel = 'Auto Isolir';
    
    protected static ?int $navigationSort = 4;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Perangkat')
                    ->schema([
                        Forms\Components\Select::make('mikrotik_device_id')
                            ->label('Perangkat MikroTik')
                            ->relationship('mikrotikDevice', 'name')
                            ->required()
                            ->unique(ignoreRecord: true)
                            ->searchable()
                            ->preload()
                            ->helperText('Satu konfigurasi per perangkat MikroTik'),
                    ]),
                
                Forms\Components\Section::make('Pengaturan Waktu')
                    ->schema([
                        Forms\Components\TextInput::make('grace_period_days')
                            ->label('Grace Period (Hari)')
                            ->numeric()
                            ->default(0)
                            ->required()
                            ->helperText('Jumlah hari setelah jatuh tempo sebelum isolir'),
                        
                        Forms\Components\TextInput::make('warning_days')
                            ->label('Peringatan (Hari)')
                            ->numeric()
                            ->default(3)
                            ->required()
                            ->helperText('Kirim peringatan X hari sebelum jatuh tempo'),
                    ])->columns(2),
                
                Forms\Components\Section::make('Konfigurasi Isolir')
                    ->description('Konfigurasi ini sudah tidak digunakan. Suspend customer sekarang menggunakan IP Binding.')
                    ->schema([
                        Forms\Components\TextInput::make('isolir_queue_name')
                            ->label('Nama Queue Isolir')
                            ->maxLength(255)
                            ->helperText('Override: Nama queue custom untuk isolir'),
                        
                        Forms\Components\TextInput::make('isolir_speed')
                            ->label('Kecepatan Isolir')
                            ->placeholder('128k/128k')
                            ->helperText('Override: Limit bandwidth saat isolir (contoh: 128k/128k)'),
                    ])->columns(2),
                
                Forms\Components\Section::make('Opsi')
                    ->schema([
                        Forms\Components\Toggle::make('enabled')
                            ->label('Aktifkan Auto Isolir')
                            ->default(true)
                            ->helperText('Enable/disable auto isolir untuk perangkat ini'),
                        
                        Forms\Components\Toggle::make('auto_restore')
                            ->label('Auto Restore')
                            ->default(true)
                            ->helperText('Otomatis restore saat pelanggan bayar'),
                        
                        Forms\Components\Toggle::make('send_notification')
                            ->label('Kirim Notifikasi')
                            ->default(true)
                            ->helperText('Kirim notifikasi WhatsApp saat isolir/restore'),
                        
                        Forms\Components\Textarea::make('notes')
                            ->label('Catatan')
                            ->maxLength(65535)
                            ->columnSpanFull(),
                    ])->columns(3),
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
                
                Tables\Columns\IconColumn::make('enabled')
                    ->label('Status')
                    ->boolean()
                    ->trueIcon('heroicon-o-check-circle')
                    ->falseIcon('heroicon-o-x-circle')
                    ->trueColor('success')
                    ->falseColor('danger'),
                
                Tables\Columns\TextColumn::make('grace_period_days')
                    ->label('Grace Period')
                    ->suffix(' hari')
                    ->sortable(),
                
                Tables\Columns\TextColumn::make('warning_days')
                    ->label('Peringatan')
                    ->suffix(' hari')
                    ->sortable(),
                
                Tables\Columns\IconColumn::make('auto_restore')
                    ->label('Auto Restore')
                    ->boolean(),
                
                Tables\Columns\IconColumn::make('send_notification')
                    ->label('Notifikasi')
                    ->boolean(),
                
                Tables\Columns\TextColumn::make('updated_at')
                    ->label('Diperbarui')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(),
            ])
            ->filters([
                Tables\Filters\TernaryFilter::make('enabled')
                    ->label('Status')
                    ->placeholder('Semua')
                    ->trueLabel('Aktif')
                    ->falseLabel('Non-aktif'),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
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
            'index' => Pages\ListAutoIsolirConfigs::route('/'),
            'create' => Pages\CreateAutoIsolirConfig::route('/create'),
            'edit' => Pages\EditAutoIsolirConfig::route('/{record}/edit'),
        ];
    }
}

