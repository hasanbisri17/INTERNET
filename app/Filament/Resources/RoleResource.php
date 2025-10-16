<?php

namespace App\Filament\Resources;

use App\Filament\Resources\RoleResource\Pages;
use App\Models\Permission;
use App\Models\Role;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class RoleResource extends Resource
{
    protected static ?string $model = Role::class;
    protected static ?string $navigationIcon = 'heroicon-o-shield-check';
    protected static ?string $navigationGroup = 'Manajemen Pengguna';
    protected static ?string $navigationLabel = 'Peran & Izin';
    protected static ?int $navigationSort = 2;
    protected static bool $shouldRegisterNavigation = false;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Card::make()
                    ->schema([
                        Forms\Components\TextInput::make('name')
                            ->label('Nama Peran')
                            ->required()
                            ->maxLength(255),
                        
                        Forms\Components\Textarea::make('description')
                            ->label('Deskripsi')
                            ->maxLength(1000),
                        
                        Forms\Components\Section::make('Hak Akses')
                            ->schema([
                                Forms\Components\Grid::make()
                                    ->schema([
                                        // Data Modul
                                        Forms\Components\Section::make('Data Modul')
                                            ->schema([
                                                Forms\Components\Grid::make(3)
                                                    ->schema([
                                                        Forms\Components\Placeholder::make('data_modul_label')
                                                            ->content('Data Modul'),
                                                        Forms\Components\Placeholder::make('add_label')
                                                            ->content('Add'),
                                                        Forms\Components\Placeholder::make('edit_label')
                                                            ->content('Edit'),
                                                    ]),
                                                Forms\Components\Grid::make(3)
                                                    ->schema([
                                                        Forms\Components\CheckboxList::make('permissions')
                                                            ->label('Data Modul')
                                                            ->relationship('permissions', 'name')
                                                            ->options(Permission::where('name', 'like', '%Data Modul%')->pluck('name', 'id')),
                                                        Forms\Components\CheckboxList::make('permissions')
                                                            ->label('Add')
                                                            ->relationship('permissions', 'name')
                                                            ->options(Permission::where('name', 'like', '%Add%')->where('name', 'like', '%Modul%')->pluck('name', 'id')),
                                                        Forms\Components\CheckboxList::make('permissions')
                                                            ->label('Edit')
                                                            ->relationship('permissions', 'name')
                                                            ->options(Permission::where('name', 'like', '%Edit%')->where('name', 'like', '%Modul%')->pluck('name', 'id')),
                                                    ]),
                                            ]),
                                        
                                        // Data Peserta
                                        Forms\Components\Section::make('Data Peserta')
                                            ->schema([
                                                Forms\Components\Grid::make(3)
                                                    ->schema([
                                                        Forms\Components\Placeholder::make('data_peserta_label')
                                                            ->content('Data Peserta'),
                                                        Forms\Components\Placeholder::make('add_label')
                                                            ->content('Add'),
                                                        Forms\Components\Placeholder::make('edit_label')
                                                            ->content('Edit'),
                                                    ]),
                                                Forms\Components\Grid::make(3)
                                                    ->schema([
                                                        Forms\Components\CheckboxList::make('permissions')
                                                            ->label('Data Peserta')
                                                            ->relationship('permissions', 'name')
                                                            ->options(Permission::where('name', 'like', '%Data Peserta%')->pluck('name', 'id')),
                                                        Forms\Components\CheckboxList::make('permissions')
                                                            ->label('Add')
                                                            ->relationship('permissions', 'name')
                                                            ->options(Permission::where('name', 'like', '%Add%')->where('name', 'like', '%Peserta%')->pluck('name', 'id')),
                                                        Forms\Components\CheckboxList::make('permissions')
                                                            ->label('Edit')
                                                            ->relationship('permissions', 'name')
                                                            ->options(Permission::where('name', 'like', '%Edit%')->where('name', 'like', '%Peserta%')->pluck('name', 'id')),
                                                    ]),
                                            ]),
                                        
                                        // Data Tes
                                        Forms\Components\Section::make('Data Tes')
                                            ->schema([
                                                Forms\Components\Grid::make(3)
                                                    ->schema([
                                                        Forms\Components\Placeholder::make('data_tes_label')
                                                            ->content('Data Tes'),
                                                        Forms\Components\Placeholder::make('add_label')
                                                            ->content('Add'),
                                                        Forms\Components\Placeholder::make('edit_label')
                                                            ->content('Edit'),
                                                    ]),
                                                Forms\Components\Grid::make(3)
                                                    ->schema([
                                                        Forms\Components\CheckboxList::make('permissions')
                                                            ->label('Data Tes')
                                                            ->relationship('permissions', 'name')
                                                            ->options(Permission::where('name', 'like', '%Data Tes%')->pluck('name', 'id')),
                                                        Forms\Components\CheckboxList::make('permissions')
                                                            ->label('Add')
                                                            ->relationship('permissions', 'name')
                                                            ->options(Permission::where('name', 'like', '%Add%')->where('name', 'like', '%Tes%')->pluck('name', 'id')),
                                                        Forms\Components\CheckboxList::make('permissions')
                                                            ->label('Edit')
                                                            ->relationship('permissions', 'name')
                                                            ->options(Permission::where('name', 'like', '%Edit%')->where('name', 'like', '%Tes%')->pluck('name', 'id')),
                                                    ]),
                                            ]),
                                        

                                        

                                    ]),
                            ]),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label('Nama Peran')
                    ->searchable()
                    ->sortable(),
                
                Tables\Columns\TextColumn::make('description')
                    ->label('Deskripsi')
                    ->limit(50)
                    ->searchable(),
                
                Tables\Columns\TextColumn::make('permissions_count')
                    ->label('Jumlah Izin')
                    ->counts('permissions')
                    ->sortable(),
                
                Tables\Columns\TextColumn::make('users_count')
                    ->label('Jumlah Pengguna')
                    ->counts('users')
                    ->sortable(),
                
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
            'index' => Pages\ListRoles::route('/'),
            'create' => Pages\CreateRole::route('/create'),
            'edit' => Pages\EditRole::route('/{record}/edit'),
        ];
    }
}