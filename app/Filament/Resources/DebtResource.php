<?php

namespace App\Filament\Resources;

use App\Filament\Resources\DebtResource\Pages;
use App\Models\Debt;
use App\Models\User;
use App\Models\PaymentMethod;
use App\Models\CashTransaction;
use App\Models\TransactionCategory;
use App\Services\WhatsAppService;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Notifications\Notification;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class DebtResource extends Resource
{
    protected static ?string $model = Debt::class;

    protected static ?string $navigationIcon = 'heroicon-o-banknotes';

    protected static ?string $navigationLabel = 'Hutang';

    protected static ?string $modelLabel = 'Hutang';

    protected static ?string $pluralModelLabel = 'Hutang';

    protected static ?string $navigationGroup = 'Keuangan';

    protected static ?int $navigationSort = 3;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Informasi Kreditur')
                    ->schema([
                        Forms\Components\Select::make('creditor_type')
                            ->label('Tipe Kreditur')
                            ->options([
                                'user' => 'User',
                                'supplier' => 'Supplier',
                                'other' => 'Lainnya',
                            ])
                            ->required()
                            ->live()
                            ->afterStateUpdated(function (Forms\Set $set) {
                                $set('creditor_id', null);
                                $set('creditor_name', null);
                                $set('creditor_contact', null);
                            }),
                        Forms\Components\Select::make('creditor_id')
                            ->label('User')
                            ->relationship('creditorUser', 'name')
                            ->searchable()
                            ->preload()
                            ->visible(fn (Forms\Get $get) => $get('creditor_type') === 'user')
                            ->required(fn (Forms\Get $get) => $get('creditor_type') === 'user')
                            ->afterStateUpdated(function ($state, Forms\Set $set, Forms\Get $get) {
                                if ($state && $get('creditor_type') === 'user') {
                                    $user = User::find($state);
                                    if ($user) {
                                        $set('creditor_name', $user->name);
                                        // Prioritaskan phone number untuk WhatsApp reminder
                                        $set('creditor_contact', $user->phone ?? $user->email ?? '');
                                    }
                                }
                            }),
                        Forms\Components\TextInput::make('creditor_name')
                            ->label('Nama Kreditur')
                            ->required(fn (Forms\Get $get) => $get('creditor_type') !== 'user')
                            ->maxLength(255)
                            ->visible(fn (Forms\Get $get) => $get('creditor_type') !== 'user'),
                        Forms\Components\TextInput::make('creditor_contact')
                            ->label('Kontak Kreditur')
                            ->maxLength(255)
                            ->visible(fn (Forms\Get $get) => $get('creditor_type') !== 'user'),
                    ])
                    ->columns(2),
                Forms\Components\Section::make('Detail Hutang')
                    ->schema([
                        Forms\Components\TextInput::make('amount')
                            ->label('Jumlah Hutang')
                            ->required()
                            ->numeric()
                            ->prefix('Rp')
                            ->minValue(1)
                            ->maxValue(999999999999.99)
                            ->helperText(fn () => 'Saldo KAS saat ini: Rp ' . number_format(static::getCurrentCashBalance(), 2))
                            ->live()
                            ->afterStateUpdated(function (Forms\Set $set, $state, Forms\Get $get) {
                                $cashBalance = static::getCurrentCashBalance();
                                if ($state && $state > $cashBalance) {
                                    Notification::make()
                                        ->warning()
                                        ->title('Peringatan')
                                        ->body('Jumlah hutang melebihi saldo KAS saat ini. Pastikan saldo KAS mencukupi sebelum membuat hutang.')
                                        ->persistent()
                                        ->send();
                                }
                            }),
                        Forms\Components\DatePicker::make('due_date')
                            ->label('Tanggal Jatuh Tempo')
                            ->required()
                            ->default(now()->addDays(30))
                            ->native(false),
                        Forms\Components\Textarea::make('description')
                            ->label('Deskripsi')
                            ->maxLength(65535)
                            ->columnSpanFull(),
                    ])
                    ->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('creditor_display_name')
                    ->label('Kreditur')
                    ->searchable(['creditor_name'])
                    ->sortable(),
                Tables\Columns\TextColumn::make('creditor_contact')
                    ->label('Kontak')
                    ->searchable()
                    ->toggleable(),
                Tables\Columns\TextColumn::make('amount')
                    ->label('Jumlah')
                    ->money('IDR')
                    ->sortable(),
                Tables\Columns\TextColumn::make('paid_amount')
                    ->label('Dibayar')
                    ->money('IDR')
                    ->sortable(),
                Tables\Columns\TextColumn::make('remaining_amount')
                    ->label('Sisa')
                    ->money('IDR')
                    ->sortable()
                    ->color(fn ($record) => $record->remaining_amount > 0 ? 'danger' : 'success'),
                Tables\Columns\TextColumn::make('due_date')
                    ->label('Jatuh Tempo')
                    ->date()
                    ->sortable()
                    ->color(fn ($record) => $record->isOverdue() ? 'danger' : null),
                Tables\Columns\BadgeColumn::make('status')
                    ->label('Status')
                    ->colors([
                        'warning' => 'pending',
                        'info' => 'partial',
                        'success' => 'paid',
                        'danger' => 'overdue',
                    ])
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'pending' => 'Menunggu',
                        'partial' => 'Sebagian',
                        'paid' => 'Lunas',
                        'overdue' => 'Terlambat',
                        default => ucfirst($state),
                    })
                    ->sortable(),
                Tables\Columns\TextColumn::make('payment_count')
                    ->label('Cicilan')
                    ->counts('payments')
                    ->sortable(),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Dibuat')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->options([
                        'pending' => 'Menunggu',
                        'partial' => 'Sebagian',
                        'paid' => 'Lunas',
                        'overdue' => 'Terlambat',
                    ]),
                Tables\Filters\SelectFilter::make('creditor_type')
                    ->label('Tipe Kreditur')
                    ->options([
                        'user' => 'User',
                        'supplier' => 'Supplier',
                        'other' => 'Lainnya',
                    ]),
                Tables\Filters\Filter::make('overdue')
                    ->label('Terlambat')
                    ->query(fn (Builder $query): Builder => $query->where('due_date', '<', now())->where('status', '!=', 'paid')),
            ])
            ->actions([
                Tables\Actions\Action::make('add_payment')
                    ->label('Bayar')
                    ->icon('heroicon-o-currency-dollar')
                    ->color('success')
                    ->form([
                        Forms\Components\DatePicker::make('payment_date')
                            ->label('Tanggal Pembayaran')
                            ->required()
                            ->default(now())
                            ->native(false),
                        Forms\Components\TextInput::make('amount')
                            ->label('Jumlah')
                            ->required()
                            ->numeric()
                            ->prefix('Rp')
                            ->minValue(1)
                            ->maxValue(fn ($record) => $record->remaining_amount)
                            ->helperText(fn ($record) => 'Maksimal: Rp ' . number_format($record->remaining_amount, 2)),
                        Forms\Components\Select::make('payment_method_id')
                            ->label('Metode Pembayaran')
                            ->options(PaymentMethod::pluck('name', 'id'))
                            ->searchable()
                            ->preload(),
                        Forms\Components\Textarea::make('notes')
                            ->label('Catatan')
                            ->maxLength(65535),
                        Forms\Components\FileUpload::make('proof_of_payment')
                            ->label('Bukti Pembayaran')
                            ->helperText('Upload bukti pembayaran (opsional). Format: JPG, PNG, PDF. Maksimal 5MB.')
                            ->acceptedFileTypes(['image/jpeg', 'image/png', 'image/jpg', 'application/pdf'])
                            ->maxSize(5120) // 5MB
                            ->directory('proof-of-payments/debts')
                            ->disk('public')
                            ->visibility('public')
                            ->downloadable()
                            ->openable()
                            ->previewable()
                            ->imagePreviewHeight('150'),
                    ])
                    ->action(function (array $data, Debt $record): void {
                        DB::transaction(function () use ($data, $record) {
                            // Validasi jumlah tidak melebihi sisa hutang
                            if ($data['amount'] > $record->remaining_amount) {
                                throw new \Exception('Jumlah pembayaran tidak boleh melebihi sisa hutang.');
                            }

                            // Buat cash transaction INCOME saat pembayaran
                            // Income ini mengembalikan KAS yang terpotong saat hutang dibuat
                            // Jika hutang Rp 1.000.000 (expense), lalu bayar Rp 300.000, maka income Rp 300.000
                            // Net effect: KAS -Rp 700.000 (sesuai sisa hutang)
                            
                            // Cari kategori "Pembayaran Hutang" atau "Lain-lain" untuk income
                            $category = TransactionCategory::where('type', 'income')
                                ->where(function ($query) {
                                    $query->where('name', 'Pembayaran Hutang')
                                        ->orWhere('name', 'Lain-lain');
                                })
                                ->first();
                            
                            // Jika kategori "Pembayaran Hutang" tidak ada, buat kategori baru
                            if (!$category || $category->name !== 'Pembayaran Hutang') {
                                $category = TransactionCategory::firstOrCreate(
                                    ['name' => 'Pembayaran Hutang', 'type' => 'income'],
                                    ['description' => 'Pemasukan dari pembayaran hutang']
                                );
                            }
                            
                            $cashTransaction = CashTransaction::create([
                                'date' => $data['payment_date'],
                                'type' => 'income',
                                'amount' => $data['amount'],
                                'description' => "Pembayaran hutang kepada {$record->creditor_display_name}" . ($data['notes'] ? " - {$data['notes']}" : ''),
                                'category_id' => $category->id,
                                'created_by' => Auth::id(),
                            ]);

                            // Buat debt payment dengan cash transaction
                            $payment = $record->payments()->create([
                                'payment_date' => $data['payment_date'],
                                'amount' => $data['amount'],
                                'payment_method_id' => $data['payment_method_id'] ?? null,
                                'notes' => $data['notes'] ?? null,
                                'proof_of_payment' => $data['proof_of_payment'] ?? null,
                                'cash_transaction_id' => $cashTransaction->id,
                                'created_by' => Auth::id(),
                            ]);

                            // Update paid_amount dan status
                            $record->paid_amount += $data['amount'];
                            $record->updateStatus();
                            
                            // Refresh record to get updated status
                            $record->refresh();

                            // Send WhatsApp notification for payment
                            try {
                                static::sendPaymentNotification($record, $data['amount'], $payment);
                            } catch (\Exception $e) {
                                Log::error("Failed to send WhatsApp notification for debt payment", [
                                    'debt_id' => $record->id,
                                    'payment_id' => $payment->id,
                                    'error' => $e->getMessage(),
                                ]);
                                // Don't fail the payment if notification fails
                            }

                            Notification::make()
                                ->success()
                                ->title('Pembayaran berhasil')
                                ->body('Pembayaran hutang telah dicatat dan KAS telah dikembalikan sebesar Rp ' . number_format($data['amount'], 2) . '. Saldo KAS akan bertambah sesuai jumlah pembayaran.')
                                ->send();
                        });
                    })
                    ->visible(fn ($record) => $record->remaining_amount > 0)
                    ->requiresConfirmation(),
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('due_date', 'asc');
    }

    public static function getRelations(): array
    {
        return [
            DebtResource\RelationManagers\PaymentsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListDebts::route('/'),
            'create' => Pages\CreateDebt::route('/create'),
            'edit' => Pages\EditDebt::route('/{record}/edit'),
        ];
    }

    /**
     * Get current cash balance
     */
    public static function getCurrentCashBalance(): float
    {
        $income = CashTransaction::whereNull('voided_at')->income()->sum('amount');
        $expense = CashTransaction::whereNull('voided_at')->expense()->sum('amount');
        return $income - $expense;
    }

    /**
     * Send WhatsApp notification for debt payment
     */
    protected static function sendPaymentNotification(Debt $debt, float $paymentAmount, $payment): void
    {
        // Get creditor contact (prefer phone number for WhatsApp)
        $contact = null;
        
        // If creditor_type is 'user', get phone from user relationship
        if ($debt->creditor_type === 'user' && $debt->creditorUser) {
            $contact = $debt->creditorUser->phone;
        }
        
        // Fallback to creditor_contact field if no phone from user
        if (empty($contact)) {
            $contact = $debt->creditor_contact;
            // Check if it's a phone number (starts with digit or +)
            if (!empty($contact) && !preg_match('/^[0-9+]/', $contact)) {
                $contact = null; // Not a phone number, skip
            }
        }
        
        // Only send notification if contact (phone number) is available
        if (empty($contact) || !preg_match('/^[0-9+]/', $contact)) {
            Log::warning("Debt payment notification skipped: No valid phone number", [
                'debt_id' => $debt->id,
                'creditor' => $debt->creditor_display_name,
            ]);
            return;
        }

        $whatsapp = new WhatsAppService();
        
        // Format message for payment notification
        $paymentAmountFormatted = number_format($paymentAmount, 0, ',', '.');
        $totalDebt = number_format($debt->amount, 0, ',', '.');
        $paidAmount = number_format($debt->paid_amount, 0, ',', '.');
        $remainingAmount = number_format($debt->remaining_amount, 0, ',', '.');
        $paymentDate = Carbon::parse($payment->payment_date)->format('d F Y');
        $paymentMethod = $payment->paymentMethod ? $payment->paymentMethod->name : 'Tidak disebutkan';
        
        // Determine payment status
        $isFullyPaid = $debt->status === 'paid';
        
        $message = "💳 *Konfirmasi Pembayaran Hutang*\n\n";
        $message .= "Kepada: {$debt->creditor_display_name}\n";
        $message .= "Jumlah Pembayaran: Rp {$paymentAmountFormatted}\n";
        $message .= "Tanggal Pembayaran: {$paymentDate}\n";
        $message .= "Metode Pembayaran: {$paymentMethod}\n\n";
        
        $message .= "📊 *Detail Hutang:*\n";
        $message .= "Total Hutang: Rp {$totalDebt}\n";
        $message .= "Sudah Dibayar: Rp {$paidAmount}\n";
        $message .= "Sisa Hutang: Rp {$remainingAmount}\n\n";
        
        if ($isFullyPaid) {
            $message .= "✅ *Status: LUNAS*\n\n";
            $message .= "Terima kasih! Hutang telah dilunasi sepenuhnya.";
        } else {
            $message .= "⚠️ *Status: Sebagian Dibayar*\n\n";
            $message .= "Sisa hutang: Rp {$remainingAmount}\n";
            $message .= "Mohon lakukan pembayaran selanjutnya sebelum jatuh tempo.";
        }
        
        if ($payment->notes) {
            $message .= "\n\nCatatan: {$payment->notes}";
        }
        
        // Send WhatsApp message
        $result = $whatsapp->sendMessage($contact, $message);
        
        if ($result['success']) {
            Log::info("Debt payment WhatsApp notification sent", [
                'debt_id' => $debt->id,
                'payment_id' => $payment->id,
                'creditor' => $debt->creditor_display_name,
                'contact' => $contact,
                'payment_amount' => $paymentAmount,
                'status' => $debt->status,
            ]);
        } else {
            throw new \Exception($result['message'] ?? 'Failed to send notification');
        }
    }
}
