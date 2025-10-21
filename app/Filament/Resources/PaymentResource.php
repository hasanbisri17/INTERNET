<?php

namespace App\Filament\Resources;

use App\Filament\Resources\PaymentResource\Pages;
use App\Models\Payment;
use App\Models\PaymentMethod;
use Barryvdh\DomPDF\Facade\Pdf;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use App\Services\WhatsAppService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class PaymentResource extends Resource
{
    protected static ?string $model = Payment::class;

    protected static ?string $navigationIcon = 'heroicon-o-currency-dollar';

    protected static ?string $navigationLabel = 'Tagihan';

    protected static ?string $modelLabel = 'Tagihan';

    protected static ?string $pluralModelLabel = 'Tagihan';

    protected static ?string $navigationGroup = 'Pembayaran';

    protected static ?int $navigationSort = 2;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Select::make('customer_id')
                    ->label('Pelanggan')
                    ->relationship('customer', 'name')
                    ->required()
                    ->searchable()
                    ->preload()
                    ->live()
                    ->afterStateUpdated(function ($state, Forms\Set $set) {
                        if ($state) {
                            $customer = \App\Models\Customer::find($state);
                            if ($customer) {
                                $set('internet_package_id', $customer->internet_package_id);
                                $set('amount', $customer->internetPackage?->price ?? 0);
                            }
                        }
                    }),
                Forms\Components\Select::make('internet_package_id')
                    ->label('Paket Internet')
                    ->relationship('internetPackage', 'name')
                    ->required()
                    ->disabled()
                    ->dehydrated()
                    ->afterStateHydrated(function ($component, $state, $record) {
                        if ($record && $record->customer) {
                            $component->state($record->customer->internet_package_id);
                        }
                    }),
                Forms\Components\TextInput::make('invoice_number')
                    ->label('Nomor Invoice')
                    ->default(fn () => Payment::generateInvoiceNumber())
                    ->disabled()
                    ->dehydrated()
                    ->required(),
                Forms\Components\TextInput::make('amount')
                    ->label('Jumlah Tagihan')
                    ->numeric()
                    ->prefix('Rp')
                    ->required(),
                Forms\Components\DatePicker::make('due_date')
                    ->label('Tanggal Jatuh Tempo')
                    ->required(),
                Forms\Components\DatePicker::make('payment_date')
                    ->label('Tanggal Pembayaran')
                    ->nullable(),
                Forms\Components\Select::make('status')
                    ->label('Status')
                    ->options([
                        'pending' => 'Menunggu Pembayaran',
                        'paid' => 'Lunas',
                        'overdue' => 'Terlambat',
                        'failed' => 'Gagal',
                        'expired' => 'Kedaluwarsa',
                        'refunded' => 'Dikembalikan',
                        'canceled' => 'Dibatalkan',
                    ])
                    ->required()
                    ->default('pending')
                    ->disabled(),
                Forms\Components\Select::make('payment_method_id')
                    ->label('Metode Pembayaran')
                    ->relationship('paymentMethod', 'name')
                    ->preload()
                    ->searchable()
                    ->nullable()
                    ->hidden(fn ($record) => !$record || $record->status !== 'paid')
                    ->required(fn ($record) => $record && $record->status === 'paid')
                    ->live()
                    ->afterStateUpdated(function ($state, Forms\Set $set) {
                        if ($state) {
                            $set('status', 'paid');
                            $set('payment_date', now());
                        }
                    })
                    ->dehydrated(fn ($record) => $record && $record->status === 'paid')
                    ->createOptionForm([
                        Forms\Components\TextInput::make('name')
                            ->required()
                            ->maxLength(255),
                        Forms\Components\TextInput::make('code')
                            ->required()
                            ->maxLength(255)
                            ->unique('payment_methods', 'code'),
                        Forms\Components\Select::make('type')
                            ->options(PaymentMethod::TYPES)
                            ->required(),
                        Forms\Components\TextInput::make('provider')
                            ->maxLength(255),
                        Forms\Components\TextInput::make('account_number')
                            ->maxLength(255),
                        Forms\Components\TextInput::make('account_name')
                            ->maxLength(255),
                        Forms\Components\Toggle::make('is_active')
                            ->required()
                            ->default(true),
                    ]),
                Forms\Components\Textarea::make('notes')
                    ->label('Catatan')
                    ->maxLength(65535)
                    ->columnSpanFull(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('invoice_number')
                    ->label('Nomor Invoice')
                    ->searchable(),
                Tables\Columns\TextColumn::make('customer.name')
                    ->label('Pelanggan')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('internetPackage.name')
                    ->label('Paket')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('amount')
                    ->label('Jumlah')
                    ->money('IDR')
                    ->sortable(),
                Tables\Columns\TextColumn::make('due_date')
                    ->label('Jatuh Tempo')
                    ->date('d/m/Y')
                    ->sortable(),
                Tables\Columns\TextColumn::make('payment_date')
                    ->label('Tgl Pembayaran')
                    ->date()
                    ->sortable()
                    ->formatStateUsing(fn (?Payment $record): ?string => 
                        $record && $record->status === 'canceled' ? null : $record?->payment_date?->format('d/m/Y')
                    ),
                Tables\Columns\TextColumn::make('canceled_at')
                    ->label('Tgl Pembatalan')
                    ->date('d/m/Y')
                    ->sortable()
                    ->formatStateUsing(function (?Payment $record): ?string {
                        if (!$record || $record->status !== 'canceled' || !$record->canceled_at) {
                            return '-';
                        }
                        return $record->canceled_at->format('d/m/Y');
                    })
                    ->icon(function (?Payment $record): ?string {
                        if (!$record || $record->status !== 'canceled') {
                            return null;
                        }
                        return 'heroicon-o-information-circle';
                    })
                    ->iconColor(function (?Payment $record): ?string {
                        if (!$record || $record->status !== 'canceled') {
                            return null;
                        }
                        return 'danger';
                    })
                    ->tooltip(function (?Payment $record): ?string {
                        if (!$record || $record->status !== 'canceled') {
                            return null;
                        }
                        
                        $tooltip = "Alasan: " . ($record->canceled_reason ?? 'Tidak ada alasan');
                        
                        if ($record->canceled_by && $record->canceledBy) {
                            $tooltip .= "\nDibatalkan oleh: " . $record->canceledBy->name;
                        }
                        
                        return $tooltip;
                    }),
                Tables\Columns\BadgeColumn::make('status')
                    ->label('Status')
                    ->colors([
                        'danger' => ['overdue','failed','expired'],
                        'warning' => 'pending',
                        'success' => ['paid','refunded'],
                        'gray' => 'canceled',
                    ]),
                Tables\Columns\TextColumn::make('paymentMethod.name')
                    ->label('Metode')
                    ->badge()
                    ->color(fn ($record) => match($record?->paymentMethod?->type) {
                        'cash' => 'success',
                        'bank_transfer' => 'info',
                        'e_wallet' => 'warning',
                        default => 'gray',
                    }),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->options([
                        'pending' => 'Menunggu Pembayaran',
                        'paid' => 'Lunas',
                        'overdue' => 'Terlambat',
                        'failed' => 'Gagal',
                        'expired' => 'Kedaluwarsa',
                        'refunded' => 'Dikembalikan',
                        'canceled' => 'Dibatalkan',
                    ]),
                Tables\Filters\SelectFilter::make('payment_method_id')
                    ->label('Metode Pembayaran')
                    ->relationship('paymentMethod', 'name'),
            ])
            ->actions([
                Tables\Actions\Action::make('download_invoice')
                    ->label('Download Invoice')
                    ->icon('heroicon-o-document-arrow-down')
                    ->color('success')
                    ->url(fn (?Payment $record): string => $record ? route('invoice.download', $record) : '#')
                    ->openUrlInNewTab()
                    ->visible(fn (?Payment $record): bool => $record && $record->customer && $record->internetPackage),
                Tables\Actions\Action::make('pay')
                    ->label('Bayar')
                    ->icon('heroicon-o-credit-card')
                    ->color('success')
                    ->modalHeading('Pembayaran Tagihan')
                    ->form([
                        Forms\Components\DatePicker::make('payment_date')
                            ->label('Tanggal Pembayaran')
                            ->required()
                            ->default(now()),
                        Forms\Components\Select::make('payment_method_id')
                            ->label('Metode Pembayaran')
                            ->relationship('paymentMethod', 'name')
                            ->preload()
                            ->searchable()
                            ->required(),
                        Forms\Components\Textarea::make('notes')
                            ->label('Catatan')
                            ->maxLength(65535),
                    ])
                    ->action(function (Payment $record, array $data): void {
                        // Update payment record
                        $record->update([
                            'payment_date' => $data['payment_date'],
                            'payment_method_id' => $data['payment_method_id'],
                            'notes' => $data['notes'],
                            'status' => 'paid',
                        ]);

                        // Get the internet payment category
                        $category = \App\Models\TransactionCategory::where('type', 'income')
                            ->where('name', 'Pembayaran Internet')
                            ->first();

                        // Create cash transaction
                        \App\Models\CashTransaction::create([
                            'date' => $data['payment_date'],
                            'type' => 'income',
                            'amount' => $record->amount,
                            'description' => "Pembayaran Internet - {$record->customer->name} ({$record->invoice_number})",
                            'category_id' => $category?->id,
                            'payment_id' => $record->id,
                        ]);

                        // Send WhatsApp notification for successful payment WITH PAID INVOICE PDF
                        try {
                            $whatsapp = new WhatsAppService();
                            $whatsapp->sendBillingNotification($record, 'paid', true); // true = send PDF invoice lunas
                        } catch (\Exception $e) {
                            \Filament\Notifications\Notification::make()
                                ->warning()
                                ->title('WhatsApp Notification Failed')
                                ->body('Payment recorded successfully, but failed to send WhatsApp notification.')
                                ->send();
                        }
                    })
                    ->visible(fn (?Payment $record): bool => $record && $record->status !== 'paid')
                    ->successNotificationTitle('Pembayaran berhasil dicatat')
                    ->successNotification(
                        notification: function (Payment $record) {
                            return \Filament\Notifications\Notification::make()
                                ->success()
                                ->title('Pembayaran berhasil dicatat')
                                ->body("Pembayaran telah dicatat dan otomatis ditambahkan ke Kas sebagai pemasukan.\nNomor Invoice: {$record->invoice_number}\nPelanggan: {$record->customer->name}\nJumlah: Rp " . number_format($record->amount, 2));
                        }
                    ),
                // Tambahkan action pembatalan tagihan
                Tables\Actions\Action::make('cancel_invoice')
                    ->label('Batalkan Tagihan')
                    ->icon('heroicon-o-x-circle')
                    ->color('gray')
                    ->requiresConfirmation()
                    ->modalHeading('Batalkan Tagihan')
                    ->modalDescription('Pembatalan akan menandai tagihan sebagai dibatalkan dan me-void semua entri Kas terkait.')
                    ->form([
                        Forms\Components\Textarea::make('reason')
                            ->label('Alasan Pembatalan')
                            ->required()
                            ->maxLength(1000)
                            ->columnSpanFull(),
                    ])
                    ->visible(fn (?Payment $record): bool => $record && $record->status === 'paid')
                    ->action(function (Payment $record, array $data): void {
                        DB::transaction(function () use ($record, $data) {
                            // Update payment status to canceled and clear payment data
                            $record->update([
                                'status' => 'canceled',
                                'canceled_at' => now(),
                                'canceled_by' => Auth::id(),
                                'canceled_reason' => $data['reason'] ?? null,
                                'payment_date' => null,
                                'payment_method_id' => null,
                            ]);

                            // Void related cash transactions via relation
                            $related = $record->cashTransactions()->get();
                            foreach ($related as $trx) {
                                $trx->update([
                                    'voided_at' => now(),
                                    'voided_by' => Auth::id(),
                                    'void_reason' => 'Invoice dibatalkan: ' . ($data['reason'] ?? '-') ,
                                ]);
                            }

                            // Fallback: also find by invoice number pattern in description (legacy records)
                            if ($related->isEmpty()) {
                                $legacyQuery = \App\Models\CashTransaction::where('description', 'LIKE', "%({$record->invoice_number})%");
                                if (Schema::hasColumn('cash_transactions', 'voided_at')) {
                                    $legacyQuery->whereNull('voided_at');
                                }
                                $legacy = $legacyQuery->get();
                                foreach ($legacy as $trx) {
                                    $dataToUpdate = [
                                        'voided_by' => Auth::id(),
                                        'void_reason' => 'Invoice dibatalkan: ' . ($data['reason'] ?? '-') ,
                                        'payment_id' => $record->id,
                                    ];
                                    if (Schema::hasColumn('cash_transactions', 'voided_at')) {
                                        $dataToUpdate['voided_at'] = now();
                                    }
                                    $trx->update($dataToUpdate);
                                }
                            }

                            // Attempt gateway cancel if applicable
                            if (!empty($record->gateway) && class_exists('App\\\\Services\\\\Payments\\\\PaymentGatewayManager')) {
                                try {
                                    $manager = app(\App\Services\Payments\PaymentGatewayManager::class);
                                    $manager->cancel($record);
                                } catch (\Throwable $e) {
                                    // logging akan ditambahkan pada tahap Logging & Permissions
                                }
                            }
                        });

                        // Dispatch WA notif
                        try {
                            $whatsapp = new WhatsAppService();
                            $whatsapp->sendBillingNotification($record, 'canceled');
                        } catch (\Throwable $e) {}
                    })
                    ->successNotificationTitle('Tagihan dibatalkan')
                    ->successNotification(
                        notification: function (Payment $record) {
                            return \Filament\Notifications\Notification::make()
                                ->success()
                                ->title('Tagihan berhasil dibatalkan')
                                ->body("Nomor Invoice: {$record->invoice_number}\nPelanggan: {$record->customer->name}");
                        }
                    ),
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make()
                    ->visible(fn (?Payment $record): bool => $record && $record->status !== 'paid')
                    ->before(function (Payment $record) {
                        if ($record->status === 'paid') {
                            \Filament\Notifications\Notification::make()
                                ->danger()
                                ->title('Tagihan tidak dapat dihapus')
                                ->body('Tagihan yang sudah dibayar tidak dapat dihapus karena sudah memiliki data pembayaran.')
                                ->send();
                                
                            $this->halt();
                        }
                    }),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make()
                        ->before(function (Collection $records) {
                            $paidPayments = $records->filter(fn (Payment $record) => $record->status === 'paid');
                            
                            if ($paidPayments->count() > 0) {
                                \Filament\Notifications\Notification::make()
                                    ->danger()
                                    ->title('Beberapa tagihan tidak dapat dihapus')
                                    ->body('Tagihan yang sudah dibayar tidak dapat dihapus karena sudah memiliki data pembayaran.')
                                    ->send();
                                
                                // Hanya hapus tagihan yang belum dibayar
                                return $records->filter(fn (Payment $record) => $record->status !== 'paid');
                            }
                        }),
                ]),
            ])
            ->checkIfRecordIsSelectableUsing(
                fn (Payment $record): bool => $record->status !== 'paid'
            );
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
            'index' => Pages\ListPayments::route('/'),
            'create' => Pages\CreatePayment::route('/create'),
            'edit' => Pages\EditPayment::route('/{record}/edit'),
        ];
    }

    public static function generateMonthlyBills(string $month): void
    {
        $whatsapp = new WhatsAppService();
        try {
            // Get all active customers
            $customers = \App\Models\Customer::whereHas('internetPackage', function ($query) {
                $query->where('is_active', true);
            })->get();

            $selectedDate = \Carbon\Carbon::createFromFormat('Y-m', $month)->startOfMonth();
            $dueDate = $selectedDate->copy()->addDays(24); // Due date is 25th of the month

            foreach ($customers as $customer) {
                // Check if customer already has a bill for this month
                $existingBill = Payment::where('customer_id', $customer->id)
                    ->whereYear('due_date', $selectedDate->year)
                    ->whereMonth('due_date', $selectedDate->month)
                    ->exists();

                if (!$existingBill) {
                    $payment = Payment::create([
                        'customer_id' => $customer->id,
                        'internet_package_id' => $customer->internet_package_id,
                        'invoice_number' => Payment::generateInvoiceNumber(),
                        'amount' => $customer->internetPackage->price,
                        'due_date' => $dueDate,
                        'status' => 'pending',
                        'payment_method_id' => null, // Allow null for pending payments
                    ]);

                    // Send WhatsApp notification for new bill WITH PDF INVOICE
                    try {
                        $whatsapp->sendBillingNotification($payment, 'new', true); // true = send PDF
                    } catch (\Exception $e) {
                        \Filament\Notifications\Notification::make()
                            ->warning()
                            ->title('WhatsApp Notification Failed')
                            ->body("Failed to send WhatsApp notification for customer: {$customer->name}")
                            ->send();
                    }
                }
            }
        } catch (\Exception $e) {
            throw new \Exception('Gagal membuat tagihan bulanan: ' . $e->getMessage());
        }
    }
}
