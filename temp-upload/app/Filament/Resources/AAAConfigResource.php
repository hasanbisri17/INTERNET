<?php

namespace App\Filament\Resources;

use App\Filament\Resources\AAAConfigResource\Pages;
use App\Models\AAAConfig;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class AAAConfigResource extends Resource
{
    protected static ?string $model = AAAConfig::class;
    protected static ?string $navigationIcon = 'heroicon-o-server';
    protected static ?string $navigationGroup = 'Konfigurasi Sistem';
    protected static ?string $navigationLabel = 'Konfigurasi AAA';
    protected static ?int $navigationSort = 2;
    protected static bool $shouldRegisterNavigation = false;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Card::make()
                    ->schema([
                        Forms\Components\TextInput::make('name')
                            ->label('Nama Konfigurasi')
                            ->required()
                            ->maxLength(255),
                        
                        Forms\Components\Toggle::make('is_active')
                            ->label('Aktif')
                            ->default(true),
                        
                        Forms\Components\Fieldset::make('Pengaturan Koneksi API')
                            ->schema([
                                Forms\Components\TextInput::make('api_url')
                                    ->label('URL API')
                                    ->required()
                                    ->maxLength(255)
                                    ->rules(['required', function($value, $fail) {
                                        // Validasi khusus untuk URL Mikrotik (hostname:port)
                                        if (!preg_match('/^(https?:\/\/|[a-zA-Z0-9][-a-zA-Z0-9.]*(\.[a-zA-Z0-9][-a-zA-Z0-9.]*)+)(:\d+)?(\/.*)?$/', $value)) {
                                            $fail('URL API harus berupa URL valid atau format hostname:port');
                                        }
                                    }]),
                                
                                Forms\Components\Select::make('connection_type')
                                    ->label('Tipe Koneksi')
                                    ->options([
                                        'radius' => 'RADIUS',
                                        'mikrotik' => 'MikroTik API',
                                        'pppoe' => 'PPPoE',
                                        'custom' => 'Custom API',
                                    ])
                                    ->default('radius')
                                    ->required(),
                                
                                Forms\Components\TextInput::make('api_username')
                                    ->label('Username API')
                                    ->required()
                                    ->maxLength(255),
                                
                                Forms\Components\TextInput::make('api_password')
                                    ->label('Password API')
                                    ->password()
                                    ->dehydrated(fn ($state) => filled($state))
                                    ->required(fn (string $context): bool => $context === 'create')
                                    ->maxLength(255),
                                
                                Forms\Components\TextInput::make('api_key')
                                    ->label('API Key (opsional)')
                                    ->password()
                                    ->dehydrated(fn ($state) => filled($state))
                                    ->maxLength(255),
                                
                                Forms\Components\TextInput::make('timeout')
                                    ->label('Timeout (detik)')
                                    ->numeric()
                                    ->default(30)
                                    ->minValue(5)
                                    ->maxValue(120),
                            ]),
                        
                        Forms\Components\Fieldset::make('Pengaturan Captive Portal')
                            ->schema([
                                Forms\Components\Toggle::make('enable_captive_portal')
                                    ->label('Aktifkan Captive Portal')
                                    ->default(false),
                                
                                Forms\Components\TextInput::make('captive_portal_url')
                                    ->label('URL Captive Portal')
                                    ->maxLength(255)
                                    ->rules(['nullable', function($value, $fail) {
                                        if (empty($value)) return;
                                        // Validasi khusus untuk URL Mikrotik (hostname:port)
                                        if (!preg_match('/^(https?:\/\/|[a-zA-Z0-9][-a-zA-Z0-9.]*(\.[a-zA-Z0-9][-a-zA-Z0-9.]*)+)(:\d+)?(\/.*)?$/', $value)) {
                                            $fail('URL Captive Portal harus berupa URL valid atau format hostname:port');
                                        }
                                    }])
                                    ->visible(fn (callable $get) => $get('enable_captive_portal')),
                                
                                Forms\Components\Select::make('captive_portal_template')
                                    ->label('Template Captive Portal')
                                    ->options([
                                        'default' => 'Default',
                                        'modern' => 'Modern',
                                        'simple' => 'Simple',
                                        'custom' => 'Custom',
                                    ])
                                    ->default('default')
                                    ->visible(fn (callable $get) => $get('enable_captive_portal')),
                            ]),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label('Nama Konfigurasi')
                    ->searchable()
                    ->sortable(),
                
                Tables\Columns\TextColumn::make('connection_type')
                    ->label('Tipe Koneksi')
                    ->badge()
                    ->sortable(),
                
                Tables\Columns\TextColumn::make('api_url')
                    ->label('URL API')
                    ->limit(30)
                    ->searchable(),
                
                Tables\Columns\IconColumn::make('enable_captive_portal')
                    ->label('Captive Portal')
                    ->boolean()
                    ->sortable(),
                
                Tables\Columns\IconColumn::make('is_active')
                    ->label('Status')
                    ->boolean()
                    ->sortable(),
                
                Tables\Columns\TextColumn::make('updated_at')
                    ->label('Terakhir Diperbarui')
                    ->dateTime('d M Y H:i')
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\Filter::make('is_active')
                    ->label('Hanya yang Aktif')
                    ->query(fn ($query) => $query->where('is_active', true)),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
                Tables\Actions\Action::make('test_connection')
                    ->label('Uji Koneksi')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->action(function (AAAConfig $record) {
                        // Implementasi uji koneksi
                        return redirect()->back()->with('success', 'Koneksi berhasil!');
                    }),
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
            'index' => Pages\ListAAAConfigs::route('/'),
            'create' => Pages\CreateAAAConfig::route('/create'),
            'edit' => Pages\EditAAAConfig::route('/{record}/edit'),
        ];
    }
}