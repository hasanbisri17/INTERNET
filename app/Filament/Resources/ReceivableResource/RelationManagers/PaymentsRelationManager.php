<?php

namespace App\Filament\Resources\ReceivableResource\RelationManagers;

use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class PaymentsRelationManager extends RelationManager
{
    protected static string $relationship = 'payments';

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Informasi Pembayaran (Read-only)')
                    ->schema([
                        Forms\Components\DatePicker::make('payment_date')
                            ->label('Tanggal Pembayaran')
                            ->disabled()
                            ->dehydrated()
                            ->native(false),
                        Forms\Components\TextInput::make('amount')
                            ->label('Jumlah')
                            ->disabled()
                            ->dehydrated()
                            ->prefix('Rp'),
                        Forms\Components\Select::make('payment_method_id')
                            ->label('Metode Pembayaran')
                            ->relationship('paymentMethod', 'name')
                            ->disabled()
                            ->dehydrated()
                            ->searchable()
                            ->preload(),
                    ])
                    ->columns(3),
                Forms\Components\Section::make('Edit')
                    ->schema([
                        Forms\Components\Textarea::make('notes')
                            ->label('Catatan')
                            ->maxLength(65535)
                            ->columnSpanFull(),
                        Forms\Components\FileUpload::make('proof_of_payment')
                            ->label('Bukti Pembayaran')
                            ->helperText('Upload atau update bukti pembayaran (opsional). Format: JPG, PNG, PDF. Maksimal 5MB.')
                            ->acceptedFileTypes(['image/jpeg', 'image/png', 'image/jpg', 'application/pdf'])
                            ->maxSize(5120)
                            ->directory('proof-of-payments/receivables')
                            ->disk('public')
                            ->visibility('public')
                            ->downloadable()
                            ->openable()
                            ->previewable()
                            ->imagePreviewHeight('150')
                            ->columnSpanFull(),
                    ]),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('payment_date')
            ->columns([
                Tables\Columns\TextColumn::make('payment_date')
                    ->label('Tanggal')
                    ->date()
                    ->sortable(),
                Tables\Columns\TextColumn::make('amount')
                    ->label('Jumlah')
                    ->money('IDR')
                    ->sortable(),
                Tables\Columns\TextColumn::make('paymentMethod.name')
                    ->label('Metode Pembayaran')
                    ->searchable(),
                Tables\Columns\TextColumn::make('notes')
                    ->label('Catatan')
                    ->limit(50)
                    ->toggleable(),
                Tables\Columns\TextColumn::make('proof_of_payment')
                    ->label('Bukti Pembayaran')
                    ->formatStateUsing(function ($state) {
                        if (empty($state)) {
                            return '-';
                        }
                        $url = asset('storage/' . $state);
                        $isImage = in_array(strtolower(pathinfo($state, PATHINFO_EXTENSION)), ['jpg', 'jpeg', 'png', 'gif', 'webp']);
                        
                        if ($isImage) {
                            return new \Illuminate\Support\HtmlString(
                                '<a href="' . $url . '" target="_blank" class="text-primary-600 hover:text-primary-800">
                                    <img src="' . $url . '" alt="Bukti" class="h-10 w-10 rounded object-cover" />
                                </a>'
                            );
                        } else {
                            return new \Illuminate\Support\HtmlString(
                                '<a href="' . $url . '" target="_blank" class="text-primary-600 hover:text-primary-800 underline">
                                    📄 ' . basename($state) . '
                                </a>'
                            );
                        }
                    })
                    ->html()
                    ->toggleable(),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Dibuat')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                //
            ])
            ->headerActions([
                // Tidak ada action create karena pembayaran dibuat melalui action "Terima Pembayaran" di ReceivableResource
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('payment_date', 'desc');
    }
}
