<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ReceivableResource\Pages;
use App\Models\Receivable;
use App\Models\Customer;
use App\Models\User;
use App\Models\PaymentMethod;
use App\Models\CashTransaction;
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

class ReceivableResource extends Resource
{
    protected static ?string $model = Receivable::class;

    protected static ?string $navigationIcon = 'heroicon-o-credit-card';

    protected static ?string $navigationLabel = 'Piutang';

    protected static ?string $modelLabel = 'Piutang';

    protected static ?string $pluralModelLabel = 'Piutang';

    protected static ?string $navigationGroup = 'Keuangan';

    protected static ?int $navigationSort = 4;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Informasi Debitur')
                    ->schema([
                        Forms\Components\Select::make('debtor_type')
                            ->label('Tipe Debitur')
                            ->options([
                                'customer' => 'Customer',
                                'user' => 'User',
                                'other' => 'Lainnya',
                            ])
                            ->required()
                            ->live()
                            ->afterStateUpdated(function (Forms\Set $set) {
                                $set('debtor_customer_id', null);
                                $set('debtor_user_id', null);
                                $set('debtor_name', null);
                                $set('debtor_contact', null);
                            }),
                        Forms\Components\Select::make('debtor_customer_id')
                            ->label('Customer')
                            ->relationship('debtorCustomer', 'name')
                            ->searchable()
                            ->preload()
                            ->visible(fn (Forms\Get $get) => $get('debtor_type') === 'customer')
                            ->required(fn (Forms\Get $get) => $get('debtor_type') === 'customer')
                            ->afterStateUpdated(function ($state, Forms\Set $set, Forms\Get $get) {
                                if ($state && $get('debtor_type') === 'customer') {
                                    $customer = Customer::find($state);
                                    if ($customer) {
                                        $set('debtor_name', $customer->name);
                                        $set('debtor_contact', $customer->phone ?? '');
                                    }
                                }
                            }),
                        Forms\Components\Select::make('debtor_user_id')
                            ->label('User')
                            ->relationship('debtorUser', 'name')
                            ->searchable()
                            ->preload()
                            ->visible(fn (Forms\Get $get) => $get('debtor_type') === 'user')
                            ->required(fn (Forms\Get $get) => $get('debtor_type') === 'user')
                            ->afterStateUpdated(function ($state, Forms\Set $set, Forms\Get $get) {
                                if ($state && $get('debtor_type') === 'user') {
                                    $user = User::find($state);
                                    if ($user) {
                                        $set('debtor_name', $user->name);
                                        // Prioritaskan phone number untuk WhatsApp reminder
                                        $set('debtor_contact', $user->phone ?? $user->email ?? '');
                                    }
                                }
                            }),
                        Forms\Components\TextInput::make('debtor_name')
                            ->label('Nama Debitur')
                            ->required(fn (Forms\Get $get) => $get('debtor_type') === 'other')
                            ->maxLength(255)
                            ->visible(fn (Forms\Get $get) => $get('debtor_type') === 'other'),
                        Forms\Components\TextInput::make('debtor_contact')
                            ->label('Kontak Debitur')
                            ->maxLength(255)
                            ->visible(fn (Forms\Get $get) => $get('debtor_type') === 'other'),
                    ])
                    ->columns(2),
                Forms\Components\Section::make('Detail Piutang')
                    ->schema([
                        Forms\Components\TextInput::make('amount')
                            ->label('Jumlah Piutang')
                            ->required()
                            ->numeric()
                            ->prefix('Rp')
                            ->minValue(1)
                            ->maxValue(999999999999.99),
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
                Tables\Columns\TextColumn::make('debtor_display_name')
                    ->label('Debitur')
                    ->searchable(['debtor_name'])
                    ->sortable(),
                Tables\Columns\TextColumn::make('debtor_display_contact')
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
                    ->color(fn ($record) => $record->remaining_amount > 0 ? 'warning' : 'success'),
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
                Tables\Filters\SelectFilter::make('debtor_type')
                    ->label('Tipe Debitur')
                    ->options([
                        'customer' => 'Customer',
                        'user' => 'User',
                        'other' => 'Lainnya',
                    ]),
                Tables\Filters\Filter::make('overdue')
                    ->label('Terlambat')
                    ->query(fn (Builder $query): Builder => $query->where('due_date', '<', now())->where('status', '!=', 'paid')),
            ])
            ->actions([
                Tables\Actions\Action::make('add_payment')
                    ->label('Terima Pembayaran')
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
                            ->directory('proof-of-payments/receivables')
                            ->disk('public')
                            ->visibility('public')
                            ->downloadable()
                            ->openable()
                            ->previewable()
                            ->imagePreviewHeight('150'),
                    ])
                    ->action(function (array $data, Receivable $record): void {
                        DB::transaction(function () use ($data, $record) {
                            // Validasi jumlah tidak melebihi sisa piutang
                            if ($data['amount'] > $record->remaining_amount) {
                                throw new \Exception('Jumlah pembayaran tidak boleh melebihi sisa piutang.');
                            }

                            // Buat cash transaction (income)
                            $cashTransaction = CashTransaction::create([
                                'date' => $data['payment_date'],
                                'type' => 'income',
                                'amount' => $data['amount'],
                                'description' => "Pembayaran piutang dari {$record->debtor_display_name}",
                                'category_id' => null, // Bisa ditambahkan kategori khusus untuk piutang
                                'created_by' => Auth::id(),
                            ]);

                            // Buat receivable payment
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
                                Log::error("Failed to send WhatsApp notification for receivable payment", [
                                    'receivable_id' => $record->id,
                                    'payment_id' => $payment->id,
                                    'error' => $e->getMessage(),
                                ]);
                                // Don't fail the payment if notification fails
                            }

                            Notification::make()
                                ->success()
                                ->title('Pembayaran berhasil')
                                ->body('Pembayaran piutang telah dicatat dan saldo KAS telah ditambah.')
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
            ReceivableResource\RelationManagers\PaymentsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListReceivables::route('/'),
            'create' => Pages\CreateReceivable::route('/create'),
            'edit' => Pages\EditReceivable::route('/{record}/edit'),
        ];
    }

    /**
     * Send WhatsApp notification for receivable payment
     */
    protected static function sendPaymentNotification(Receivable $receivable, float $paymentAmount, $payment): void
    {
        // Get debtor contact (prefer phone number for WhatsApp)
        $contact = null;
        
        // If debtor_type is 'customer', get phone from customer relationship
        if ($receivable->debtor_type === 'customer' && $receivable->debtorCustomer) {
            $contact = $receivable->debtorCustomer->phone;
        }
        // If debtor_type is 'user', get phone from user relationship
        elseif ($receivable->debtor_type === 'user' && $receivable->debtorUser) {
            $contact = $receivable->debtorUser->phone;
        }
        
        // Fallback to debtor_contact field if no phone from relationship
        if (empty($contact)) {
            $contact = $receivable->debtor_contact;
            // Check if it's a phone number (starts with digit or +)
            if (!empty($contact) && !preg_match('/^[0-9+]/', $contact)) {
                $contact = null; // Not a phone number, skip
            }
        }
        
        // Only send notification if contact (phone number) is available
        if (empty($contact) || !preg_match('/^[0-9+]/', $contact)) {
            Log::warning("Receivable payment notification skipped: No valid phone number", [
                'receivable_id' => $receivable->id,
                'debtor' => $receivable->debtor_display_name,
            ]);
            return;
        }

        $whatsapp = new WhatsAppService();
        
        // Format message for payment notification
        $paymentAmountFormatted = number_format($paymentAmount, 0, ',', '.');
        $totalReceivable = number_format($receivable->amount, 0, ',', '.');
        $paidAmount = number_format($receivable->paid_amount, 0, ',', '.');
        $remainingAmount = number_format($receivable->remaining_amount, 0, ',', '.');
        $paymentDate = Carbon::parse($payment->payment_date)->format('d F Y');
        $paymentMethod = $payment->paymentMethod ? $payment->paymentMethod->name : 'Tidak disebutkan';
        
        // Determine payment status
        $isFullyPaid = $receivable->status === 'paid';
        
        $message = "💳 *Konfirmasi Pembayaran Piutang*\n\n";
        $message .= "Kepada: {$receivable->debtor_display_name}\n";
        $message .= "Jumlah Pembayaran: Rp {$paymentAmountFormatted}\n";
        $message .= "Tanggal Pembayaran: {$paymentDate}\n";
        $message .= "Metode Pembayaran: {$paymentMethod}\n\n";
        
        $message .= "📊 *Detail Piutang:*\n";
        $message .= "Total Piutang: Rp {$totalReceivable}\n";
        $message .= "Sudah Dibayar: Rp {$paidAmount}\n";
        $message .= "Sisa Piutang: Rp {$remainingAmount}\n\n";
        
        if ($isFullyPaid) {
            $message .= "✅ *Status: LUNAS*\n\n";
            $message .= "Terima kasih! Piutang telah dilunasi sepenuhnya.";
        } else {
            $message .= "⚠️ *Status: Sebagian Dibayar*\n\n";
            $message .= "Sisa piutang: Rp {$remainingAmount}\n";
            $message .= "Mohon lakukan pembayaran selanjutnya sebelum jatuh tempo.";
        }
        
        if ($payment->notes) {
            $message .= "\n\nCatatan: {$payment->notes}";
        }
        
        // Send WhatsApp message
        $result = $whatsapp->sendMessage($contact, $message);
        
        if ($result['success']) {
            Log::info("Receivable payment WhatsApp notification sent", [
                'receivable_id' => $receivable->id,
                'payment_id' => $payment->id,
                'debtor' => $receivable->debtor_display_name,
                'contact' => $contact,
                'payment_amount' => $paymentAmount,
                'status' => $receivable->status,
            ]);
        } else {
            throw new \Exception($result['message'] ?? 'Failed to send notification');
        }
    }
}
