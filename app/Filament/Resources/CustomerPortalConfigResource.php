<?php

namespace App\Filament\Resources;

use App\Filament\Resources\CustomerPortalConfigResource\Pages;
use App\Models\CustomerPortalConfig;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class CustomerPortalConfigResource extends Resource
{
    protected static ?string $model = CustomerPortalConfig::class;
    protected static ?string $navigationIcon = 'heroicon-o-user-group';
    protected static ?string $navigationGroup = 'Pengaturan Sistem';
    protected static ?string $navigationLabel = 'Konfigurasi Portal Pelanggan';
    protected static ?int $navigationSort = 3;
    protected static bool $shouldRegisterNavigation = false;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Card::make()
                    ->schema([
                        Forms\Components\Toggle::make('is_enabled')
                            ->label('Aktifkan Portal Pelanggan')
                            ->default(true)
                            ->required(),
                            
                        Forms\Components\TextInput::make('portal_url')
                            ->label('URL Portal Pelanggan')
                            ->placeholder('https://portal.example.com')
                            ->url()
                            ->required(),
                            
                        Forms\Components\TextInput::make('portal_name')
                            ->label('Nama Portal')
                            ->placeholder('Portal Pelanggan ISP')
                            ->required()
                            ->maxLength(100),
                    ]),
                    
                Forms\Components\Card::make()
                    ->schema([
                        Forms\Components\Fieldset::make('Pengaturan Tampilan')
                            ->schema([
                                Forms\Components\ColorPicker::make('primary_color')
                                    ->label('Warna Utama')
                                    ->default('#4f46e5'),
                                    
                                Forms\Components\ColorPicker::make('secondary_color')
                                    ->label('Warna Sekunder')
                                    ->default('#1f2937'),
                                    
                                Forms\Components\FileUpload::make('logo_path')
                                    ->label('Logo Portal')
                                    ->image()
                                    ->directory('portal-assets')
                                    ->maxSize(2048)
                                    ->helperText('Ukuran maksimal: 2MB. Format: PNG, JPG'),
                                    
                                Forms\Components\FileUpload::make('favicon_path')
                                    ->label('Favicon')
                                    ->image()
                                    ->directory('portal-assets')
                                    ->maxSize(512)
                                    ->helperText('Ukuran maksimal: 512KB. Format: PNG, ICO'),
                            ]),
                    ]),
                    
                Forms\Components\Card::make()
                    ->schema([
                        Forms\Components\Fieldset::make('Fitur Portal')
                            ->schema([
                                Forms\Components\Toggle::make('enable_payment')
                                    ->label('Aktifkan Pembayaran')
                                    ->default(true),
                                    
                                Forms\Components\Toggle::make('enable_ticket')
                                    ->label('Aktifkan Tiket Dukungan')
                                    ->default(true),
                                    
                                Forms\Components\Toggle::make('enable_usage_stats')
                                    ->label('Aktifkan Statistik Penggunaan')
                                    ->default(true),
                                    
                                Forms\Components\Toggle::make('enable_package_upgrade')
                                    ->label('Aktifkan Upgrade Paket')
                                    ->default(true),
                                    
                                Forms\Components\Toggle::make('enable_auto_renewal')
                                    ->label('Aktifkan Perpanjangan Otomatis')
                                    ->default(false),
                            ]),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\IconColumn::make('is_enabled')
                    ->label('Status')
                    ->boolean(),
                    
                Tables\Columns\TextColumn::make('portal_name')
                    ->label('Nama Portal')
                    ->searchable(),
                    
                Tables\Columns\TextColumn::make('portal_url')
                    ->label('URL Portal')
                    ->searchable(),
                    
                Tables\Columns\TextColumn::make('updated_at')
                    ->label('Terakhir Diperbarui')
                    ->dateTime('d M Y H:i')
                    ->sortable(),
            ])
            ->filters([
                //
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\Action::make('preview')
                    ->label('Pratinjau')
                    ->icon('heroicon-o-eye')
                    ->url(fn (CustomerPortalConfig $record): string => $record->portal_url)
                    ->openUrlInNewTab(),
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
            'index' => Pages\ListCustomerPortalConfigs::route('/'),
            'create' => Pages\CreateCustomerPortalConfig::route('/create'),
            'edit' => Pages\EditCustomerPortalConfig::route('/{record}/edit'),
        ];
    }
}