<?php

namespace App\Filament\Resources;

use App\Filament\Resources\CustomerResource\Pages;
use App\Filament\Resources\CustomerResource\RelationManagers;
use App\Models\Customer;
use App\Models\InternetPackage;
use App\Models\MikrotikIpBinding;
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
                
                Forms\Components\Select::make('status')
                    ->label('Status Customer')
                    ->required()
                    ->options([
                        'active' => 'Aktif',
                        'inactive' => 'Non-Aktif',
                        'suspended' => 'Suspended',
                    ])
                    ->default('active')
                    ->native(false)
                    ->helperText('Status customer: Aktif = layanan berjalan, Non-Aktif = tidak berlangganan, Suspended = ditangguhkan karena belum bayar'),

                Forms\Components\Placeholder::make('ip_bindings_info')
                    ->label('💡 Info IP Bindings')
                    ->content(function (?Customer $record): string {
                        if (!$record || !$record->exists) {
                            return '⚠️ Setelah customer dibuat, Anda dapat mengelola IP Bindings di tab "IP Bindings" yang akan muncul di halaman edit.';
                        }

                        $count = $record->ipBindings()->count();
                        if ($count === 0) {
                            return '📋 Customer ini belum memiliki IP Bindings. Klik tab "IP Bindings" di atas untuk menambahkan.';
                        }

                        return "✅ Customer ini memiliki {$count} IP Binding(s). Klik tab \"IP Bindings\" di atas untuk mengelola.";
                    })
                    ->columnSpanFull()
                    ->visible(fn (?Customer $record): bool => $record === null || !$record->exists),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->searchable(),
                Tables\Columns\TextColumn::make('phone')
                    ->searchable(),
                Tables\Columns\TextColumn::make('internetPackage.name')
                    ->label('Paket Internet')
                    ->sortable(),
                Tables\Columns\BadgeColumn::make('status')
                    ->label('Status')
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'active' => 'Aktif',
                        'inactive' => 'Non-Aktif',
                        'suspended' => 'Suspended',
                        'expired' => 'Expired',
                        'terminated' => 'Terminated',
                        default => ucfirst($state),
                    })
                    ->color(fn (string $state): string => match ($state) {
                        'active' => 'success',
                        'inactive' => 'gray',
                        'suspended' => 'danger',
                        'expired' => 'warning',
                        'terminated' => 'danger',
                        default => 'warning',
                    })
                    ->icon(fn (string $state): string => match ($state) {
                        'active' => 'heroicon-o-check-circle',
                        'inactive' => 'heroicon-o-x-circle',
                        'suspended' => 'heroicon-o-exclamation-circle',
                        'expired' => 'heroicon-o-clock',
                        'terminated' => 'heroicon-o-x-mark',
                        default => 'heroicon-o-question-mark-circle',
                    })
                    ->sortable()
                    ->searchable(),
                Tables\Columns\TextColumn::make('ipBindings')
                    ->label('IP Bindings')
                    ->badge()
                    ->formatStateUsing(fn (Customer $record): string => $record->ipBindings()->count() . ' IP')
                    ->color(fn (Customer $record): string => $record->ipBindings()->count() > 0 ? 'success' : 'gray')
                    ->tooltip(function (Customer $record): ?string {
                        $bindings = $record->ipBindings()->get();
                        if ($bindings->isEmpty()) {
                            return 'Belum ada IP Bindings';
                        }
                        return $bindings->pluck('address')->join(', ');
                    })
                    ->toggleable(),
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
                Tables\Filters\SelectFilter::make('status')
                    ->label('Status Customer')
                    ->options([
                        'active' => 'Aktif',
                        'inactive' => 'Non-Aktif',
                        'suspended' => 'Suspended',
                    ])
                    ->placeholder('Semua Status'),
                
                Tables\Filters\SelectFilter::make('internet_package_id')
                    ->label('Paket Internet')
                    ->relationship('internetPackage', 'name')
                    ->searchable()
                    ->preload(),
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
            RelationManagers\IpBindingsRelationManager::class,
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
