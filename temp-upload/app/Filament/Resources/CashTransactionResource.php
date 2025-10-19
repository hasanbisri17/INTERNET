<?php

namespace App\Filament\Resources;

use App\Filament\Resources\CashTransactionResource\Pages;
use App\Models\CashTransaction;
use App\Models\TransactionCategory;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Schema;

class CashTransactionResource extends Resource
{
    protected static ?string $model = CashTransaction::class;

    protected static ?string $navigationIcon = 'heroicon-o-currency-dollar';

    protected static ?string $navigationGroup = 'Keuangan';

    protected static ?int $navigationSort = 2;

    protected static ?string $navigationLabel = 'Kas';

    protected static ?string $modelLabel = 'Transaksi Kas';

    protected static ?string $pluralModelLabel = 'Transaksi Kas';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\DatePicker::make('date')
                    ->label('Tanggal')
                    ->required()
                    ->default(now()),
                Forms\Components\Select::make('type')
                    ->label('Jenis')
                    ->options([
                        'income' => 'Pemasukan',
                        'expense' => 'Pengeluaran',
                    ])
                    ->required(),
                Forms\Components\TextInput::make('amount')
                    ->label('Jumlah')
                    ->required()
                    ->numeric()
                    ->prefix('Rp')
                    ->maxValue(42949672.95),
                Forms\Components\Select::make('category_id')
                    ->label('Kategori')
                    ->required()
                    ->options(function (Forms\Get $get) {
                        $type = $get('type');
                        if (!$type) return [];
                        
                        return TransactionCategory::where('type', $type)
                            ->pluck('name', 'id');
                    })
                    ->live()
                    ->searchable(),
                Forms\Components\TextInput::make('description')
                    ->label('Keterangan')
                    ->required()
                    ->maxLength(255),
                // Field informasi void (read-only saat edit)
                Forms\Components\Fieldset::make('Status Void')
                    ->schema([
                        Forms\Components\TextInput::make('void_reason')
                            ->label('Alasan Void')
                            ->disabled()
                            ->dehydrated(false),
                        Forms\Components\DateTimePicker::make('voided_at')
                            ->label('Waktu Void')
                            ->disabled()
                            ->dehydrated(false),
                    ])
                    ->visibleOn('edit')
                    ->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        // Siapkan filters secara dinamis agar tidak error jika kolom belum ada di DB aktif
        $filters = [
            Tables\Filters\SelectFilter::make('category')
                ->label('Kategori')
                ->relationship('category', 'name'),
            Tables\Filters\SelectFilter::make('type')
                ->label('Jenis')
                ->options([
                    'income' => 'Pemasukan',
                    'expense' => 'Pengeluaran',
                ]),
        ];

        if (Schema::hasColumn('cash_transactions', 'voided_at')) {
            $filters[] = Tables\Filters\TernaryFilter::make('voided')
                ->label('Tampilkan yang di-void?')
                ->placeholder('Hanya aktif')
                ->trueLabel('Hanya void')
                ->falseLabel('Hanya aktif')
                ->queries(
                    true: fn (Builder $query) => $query->whereNotNull('voided_at'),
                    false: fn (Builder $query) => $query->whereNull('voided_at'),
                    blank: fn (Builder $query) => $query,
                )
                ->default('false');
        }

        return $table
            ->columns([
                Tables\Columns\TextColumn::make('date')
                    ->label('Tanggal')
                    ->date()
                    ->sortable(),
                Tables\Columns\TextColumn::make('payment.invoice_number')
                    ->label('Invoice')
                    ->toggleable()
                    ->searchable(),
                Tables\Columns\TextColumn::make('category.name')
                    ->label('Kategori')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('type')
                    ->label('Jenis')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'income' => 'success',
                        'expense' => 'danger',
                    })
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'income' => 'Pemasukan',
                        'expense' => 'Pengeluaran',
                    }),
                Tables\Columns\TextColumn::make('amount')
                    ->label('Jumlah')
                    ->money('IDR')
                    ->sortable(),
                Tables\Columns\TextColumn::make('description')
                    ->label('Keterangan')
                    ->searchable(),
                Tables\Columns\BadgeColumn::make('voided')
                    ->label('Status')
                    ->getStateUsing(fn ($record) => $record->voided_at ? 'Divoid' : 'Aktif')
                    ->colors([
                        'gray' => fn ($record) => (bool) $record->voided_at,
                        'success' => fn ($record) => !$record->voided_at,
                    ]),
            ])
            ->defaultSort('date', 'desc')
            ->filters($filters)
            ->actions([
                Tables\Actions\Action::make('void')
                    ->label('Void')
                    ->icon('heroicon-o-x-circle')
                    ->color('gray')
                    ->requiresConfirmation()
                    ->visible(fn ($record) => $record->voided_at === null)
                    ->form([
                        Forms\Components\Textarea::make('void_reason')
                            ->label('Alasan Void')
                            ->required()
                            ->maxLength(1000)
                            ->columnSpanFull(),
                    ])
                    ->action(function (CashTransaction $record, array $data) {
                        $record->update([
                            'voided_at' => now(),
                            'voided_by' => Auth::id(),
                            'void_reason' => $data['void_reason'] ?? null,
                        ]);
                        Notification::make()
                            ->title('Transaksi di-void')
                            ->body('Transaksi berhasil ditandai sebagai void dan tidak akan dihitung dalam saldo kas.')
                            ->success()
                            ->send();
                    }),
                Tables\Actions\Action::make('unvoid')
                    ->label('Unvoid')
                    ->icon('heroicon-o-arrow-uturn-left')
                    ->color('warning')
                    ->requiresConfirmation()
                    ->visible(fn ($record) => $record->voided_at !== null)
                    ->action(function (CashTransaction $record) {
                        $record->update([
                            'voided_at' => null,
                            'voided_by' => null,
                            'void_reason' => null,
                        ]);
                        Notification::make()
                            ->title('Transaksi dipulihkan')
                            ->body('Status void dihapus. Transaksi kembali dihitung dalam saldo kas.')
                            ->success()
                            ->send();
                    }),
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
                Tables\Actions\Action::make('summary')
                    ->label('Ringkasan')
                    ->icon('heroicon-m-calculator')
                    ->action(function () {
                        if (Schema::hasColumn('cash_transactions', 'voided_at')) {
                            $income = CashTransaction::where('type', 'income')->whereNull('voided_at')->sum('amount');
                            $expense = CashTransaction::where('type', 'expense')->whereNull('voided_at')->sum('amount');
                        } else {
                            $income = CashTransaction::where('type', 'income')->sum('amount');
                            $expense = CashTransaction::where('type', 'expense')->sum('amount');
                        }
                        $balance = $income - $expense;
                        
                        Notification::make()
                            ->title('Ringkasan Kas (tanpa transaksi void)')
                            ->body(
                                "Total Pemasukan: Rp " . number_format($income, 2) . "\n" .
                                "Total Pengeluaran: Rp " . number_format($expense, 2) . "\n" .
                                "Saldo: Rp " . number_format($balance, 2)
                            )
                            ->success()
                            ->send();
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
            'index' => Pages\ListCashTransactions::route('/'),
            'create' => Pages\CreateCashTransaction::route('/create'),
            'edit' => Pages\EditCashTransaction::route('/{record}/edit'),
        ];
    }
}
