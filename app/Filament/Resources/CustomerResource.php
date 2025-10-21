<?php

namespace App\Filament\Resources;

use App\Filament\Resources\CustomerResource\Pages;
use App\Models\Customer;
use App\Models\InternetPackage;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class CustomerResource extends Resource
{
    protected static ?string $model = Customer::class;

    protected static ?string $navigationIcon = 'heroicon-o-users';

    protected static ?string $navigationGroup = 'Manajement';

    protected static ?int $navigationSort = 2;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('name')
                    ->label('Name')
                    ->required()
                    ->maxLength(255),
                Forms\Components\TextInput::make('email')
                    ->label('Email')
                    ->email()
                    ->maxLength(255)
                    ->unique(ignoreRecord: true),
                Forms\Components\TextInput::make('phone')
                    ->label('Phone')
                    ->tel()
                    ->maxLength(255),
                Forms\Components\Textarea::make('address')
                    ->label('Address')
                    ->maxLength(65535)
                    ->columnSpanFull(),
                Forms\Components\Select::make('internet_package_id')
                    ->label('Paket Internet')
                    ->required()
                    ->options(InternetPackage::where('is_active', true)->pluck('name', 'id'))
                    ->searchable(),
                Forms\Components\Select::make('connection_type')
                    ->label('Jenis Koneksi')
                    ->options([
                        'pppoe' => 'PPPOE',
                        'static' => 'STATIC',
                    ])
                    ->default('pppoe')
                    ->required()
                    ->native(false)
                    ->live()
                    ->disabled(fn (?Customer $record): bool => $record !== null) // Disable on edit
                    ->afterStateUpdated(function ($state, Forms\Set $set) {
                        // Clear fields when connection type changes
                        $set('pppoe_username', null);
                        $set('pppoe_password', null);
                        $set('customer_id', null);
                    }),
                Forms\Components\TextInput::make('pppoe_username')
                    ->label('Username PPPOE')
                    ->disabled()
                    ->dehydrated()
                    ->visible(fn (Forms\Get $get): bool => $get('connection_type') === 'pppoe')
                    ->helperText(fn (?Customer $record): string => 
                        $record === null 
                            ? 'Username akan di-generate otomatis oleh sistem' 
                            : 'Username tidak dapat diubah'
                    ),
                Forms\Components\TextInput::make('pppoe_password')
                    ->label('Password PPPOE')
                    ->disabled()
                    ->dehydrated()
                    ->visible(fn (Forms\Get $get): bool => $get('connection_type') === 'pppoe')
                    ->helperText(fn (?Customer $record): string => 
                        $record === null 
                            ? 'Password akan di-generate otomatis oleh sistem' 
                            : 'Password tidak dapat diubah'
                    ),
                Forms\Components\TextInput::make('customer_id')
                    ->label('ID Pelanggan')
                    ->disabled()
                    ->dehydrated()
                    ->visible(fn (Forms\Get $get): bool => $get('connection_type') === 'static')
                    ->helperText(fn (?Customer $record): string => 
                        $record === null 
                            ? 'ID Pelanggan akan di-generate otomatis oleh sistem' 
                            : 'ID Pelanggan tidak dapat diubah'
                    ),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->searchable(),
                Tables\Columns\TextColumn::make('email')
                    ->searchable(),
                Tables\Columns\TextColumn::make('phone')
                    ->searchable(),
                Tables\Columns\TextColumn::make('internetPackage.name')
                    ->label('Paket Internet')
                    ->sortable(),
                Tables\Columns\TextColumn::make('internetPackage.speed')
                    ->label('Kecepatan')
                    ->sortable(),
                Tables\Columns\TextColumn::make('connection_type')
                    ->label('Jenis Koneksi')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'pppoe' => 'success',
                        'static' => 'info',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn (string $state): string => strtoupper($state)),
                Tables\Columns\TextColumn::make('pppoe_username')
                    ->label('Username PPPOE')
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: false)
                    ->placeholder('-'),
                Tables\Columns\TextColumn::make('customer_id')
                    ->label('ID Pelanggan')
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: false)
                    ->placeholder('-'),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                //
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

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListCustomers::route('/'),
            'create' => Pages\CreateCustomer::route('/create'),
            'edit' => Pages\EditCustomer::route('/{record}/edit'),
        ];
    }
}
