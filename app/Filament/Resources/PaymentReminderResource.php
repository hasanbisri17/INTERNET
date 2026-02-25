<?php

namespace App\Filament\Resources;

use App\Filament\Resources\PaymentReminderResource\Pages;
use App\Models\PaymentReminder;
use App\Models\PaymentReminderRule;
use App\Services\WhatsAppService;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Log;

class PaymentReminderResource extends Resource
{
    protected static ?string $model = PaymentReminder::class;

    protected static ?string $navigationIcon = 'heroicon-o-chat-bubble-left-right';

    protected static ?string $navigationLabel = 'Status Reminder';

    protected static ?string $modelLabel = 'Status Reminder';

    protected static ?string $pluralModelLabel = 'Status Reminder';

    protected static ?string $navigationGroup = 'WhatsApp';

    protected static ?int $navigationSort = 5;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Detail Reminder')
                    ->schema([
                        Forms\Components\Placeholder::make('customer_name')
                            ->label('Pelanggan')
                            ->content(fn(PaymentReminder $record): string => $record->payment?->customer?->name ?? '-'),

                        Forms\Components\Placeholder::make('invoice_number')
                            ->label('Invoice')
                            ->content(fn(PaymentReminder $record): string => $record->payment?->invoice_number ?? '-'),

                        Forms\Components\Placeholder::make('reminder_rule')
                            ->label('Rule Reminder')
                            ->content(fn(PaymentReminder $record): string => $record->reminderRule?->name ?? '-'),

                        Forms\Components\Placeholder::make('reminder_type')
                            ->label('Tipe')
                            ->content(fn(PaymentReminder $record): string => $record->reminder_type_label),

                        Forms\Components\Placeholder::make('reminder_date')
                            ->label('Tanggal Reminder')
                            ->content(fn(PaymentReminder $record): string => $record->reminder_date?->format('d M Y') ?? '-'),

                        Forms\Components\Placeholder::make('status')
                            ->label('Status')
                            ->content(fn(PaymentReminder $record): string => match ($record->status) {
                                'sent' => '✅ Terkirim',
                                'failed' => '❌ Gagal',
                                'pending' => '⏳ Pending',
                                default => $record->status,
                            }),

                        Forms\Components\Placeholder::make('sent_at')
                            ->label('Waktu Terkirim')
                            ->content(fn(PaymentReminder $record): string => $record->sent_at?->format('d M Y H:i:s') ?? '-'),

                        Forms\Components\Placeholder::make('error_message')
                            ->label('Pesan Error')
                            ->content(fn(PaymentReminder $record): string => $record->error_message ?? '-')
                            ->visible(fn(PaymentReminder $record): bool => !empty($record->error_message)),
                    ])
                    ->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('payment.customer.name')
                    ->label('Pelanggan')
                    ->searchable()
                    ->sortable()
                    ->weight('medium')
                    ->icon('heroicon-m-user'),

                Tables\Columns\TextColumn::make('payment.invoice_number')
                    ->label('Invoice')
                    ->searchable()
                    ->sortable()
                    ->color('primary')
                    ->copyable(),

                Tables\Columns\TextColumn::make('payment.status')
                    ->label('Status Bayar')
                    ->badge()
                    ->color(fn(string $state): string => match ($state) {
                        'paid' => 'success',
                        'pending' => 'warning',
                        'overdue' => 'danger',
                        'canceled' => 'gray',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn(string $state): string => match ($state) {
                        'paid' => 'Lunas',
                        'pending' => 'Belum Bayar',
                        'overdue' => 'Terlambat',
                        'canceled' => 'Dibatalkan',
                        default => ucfirst($state),
                    }),

                Tables\Columns\TextColumn::make('reminderRule.name')
                    ->label('Rule')
                    ->sortable()
                    ->toggleable()
                    ->color('gray'),

                Tables\Columns\TextColumn::make('reminder_type')
                    ->label('Tipe')
                    ->badge()
                    ->color(fn(string $state): string => match ($state) {
                        'before_due' => 'warning',
                        'on_due' => 'info',
                        'overdue' => 'danger',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn(string $state): string => match ($state) {
                        'before_due' => 'Sebelum Jatuh Tempo',
                        'on_due' => 'Jatuh Tempo',
                        'overdue' => 'Terlambat',
                        default => ucfirst(str_replace('_', ' ', $state)),
                    }),

                Tables\Columns\TextColumn::make('status')
                    ->label('Status Kirim')
                    ->badge()
                    ->color(fn(string $state): string => match ($state) {
                        'sent' => 'success',
                        'failed' => 'danger',
                        'pending' => 'warning',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn(string $state): string => match ($state) {
                        'sent' => 'Terkirim',
                        'failed' => 'Gagal',
                        'pending' => 'Pending',
                        default => ucfirst($state),
                    })
                    ->icon(fn(string $state): string => match ($state) {
                        'sent' => 'heroicon-m-check-circle',
                        'failed' => 'heroicon-m-x-circle',
                        'pending' => 'heroicon-m-clock',
                        default => 'heroicon-m-question-mark-circle',
                    }),

                Tables\Columns\TextColumn::make('error_message')
                    ->label('Error')
                    ->limit(40)
                    ->tooltip(fn(PaymentReminder $record): ?string => $record->error_message)
                    ->color('danger')
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('reminder_date')
                    ->label('Tgl Reminder')
                    ->date('d M Y')
                    ->sortable(),

                Tables\Columns\TextColumn::make('sent_at')
                    ->label('Waktu Kirim')
                    ->dateTime('d M Y H:i')
                    ->sortable()
                    ->placeholder('-'),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Dibuat')
                    ->dateTime('d M Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->label('Status Kirim')
                    ->options([
                        'sent' => '✅ Terkirim',
                        'failed' => '❌ Gagal',
                        'pending' => '⏳ Pending',
                    ]),

                Tables\Filters\SelectFilter::make('payment_status')
                    ->label('Status Bayar')
                    ->options([
                        'pending' => 'Belum Bayar',
                        'paid' => 'Lunas',
                        'overdue' => 'Terlambat',
                    ])
                    ->query(function (Builder $query, array $data) {
                        if ($data['value']) {
                            $query->whereHas('payment', fn(Builder $q) => $q->where('status', $data['value']));
                        }
                    }),

                Tables\Filters\SelectFilter::make('reminder_type')
                    ->label('Tipe Reminder')
                    ->options([
                        'before_due' => 'Sebelum Jatuh Tempo',
                        'on_due' => 'Jatuh Tempo',
                        'overdue' => 'Terlambat',
                    ]),

                Tables\Filters\Filter::make('reminder_date')
                    ->form([
                        Forms\Components\DatePicker::make('from')
                            ->label('Dari Tanggal'),
                        Forms\Components\DatePicker::make('until')
                            ->label('Sampai Tanggal'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                $data['from'],
                                fn(Builder $query, $date): Builder => $query->whereDate('reminder_date', '>=', $date),
                            )
                            ->when(
                                $data['until'],
                                fn(Builder $query, $date): Builder => $query->whereDate('reminder_date', '<=', $date),
                            );
                    })
                    ->indicateUsing(function (array $data): array {
                        $indicators = [];
                        if ($data['from'] ?? null) {
                            $indicators[] = 'Dari: ' . \Carbon\Carbon::parse($data['from'])->format('d M Y');
                        }
                        if ($data['until'] ?? null) {
                            $indicators[] = 'Sampai: ' . \Carbon\Carbon::parse($data['until'])->format('d M Y');
                        }
                        return $indicators;
                    }),
            ])
            ->actions([
                Tables\Actions\ViewAction::make()
                    ->label('Detail'),

                Tables\Actions\Action::make('resend')
                    ->label('Kirim Ulang')
                    ->icon('heroicon-o-arrow-path')
                    ->color('warning')
                    ->requiresConfirmation()
                    ->modalHeading('Kirim Ulang Reminder')
                    ->modalDescription(
                        fn(PaymentReminder $record): string =>
                        "Kirim ulang reminder ke {$record->payment?->customer?->name} untuk invoice {$record->payment?->invoice_number}?"
                    )
                    ->modalSubmitActionLabel('Ya, Kirim Ulang')
                    ->visible(
                        fn(PaymentReminder $record): bool =>
                        $record->status === 'failed' &&
                        $record->payment?->status !== 'paid'
                    )
                    ->action(function (PaymentReminder $record) {
                        $payment = $record->payment;
                        $customer = $payment?->customer;

                        if (!$payment || !$customer) {
                            Notification::make()
                                ->title('Gagal')
                                ->body('Data payment atau customer tidak ditemukan.')
                                ->danger()
                                ->send();
                            return;
                        }

                        if ($payment->status === 'paid') {
                            Notification::make()
                                ->title('Tidak Perlu')
                                ->body('Customer sudah melakukan pembayaran.')
                                ->warning()
                                ->send();
                            return;
                        }

                        if (!$customer->phone) {
                            Notification::make()
                                ->title('Gagal')
                                ->body('Customer tidak memiliki nomor telepon.')
                                ->danger()
                                ->send();
                            return;
                        }

                        try {
                            $whatsapp = new WhatsAppService();
                            $rule = $record->reminderRule;

                            // Determine service type
                            $days = $rule?->days_before_due ?? 0;
                            if ($days <= -7) {
                                $serviceType = 'reminder';
                            } elseif ($days == -3) {
                                $serviceType = 'reminder_h3';
                            } elseif ($days == -1) {
                                $serviceType = 'reminder_h1';
                            } elseif ($days == 0) {
                                $serviceType = 'reminder_h0';
                            } else {
                                $serviceType = 'overdue';
                            }

                            // Send with PDF
                            $whatsapp->sendBillingNotification(
                                $payment,
                                $serviceType,
                                true,
                                $rule?->whatsappTemplate
                            );

                            // Find the WA message just sent
                            $whatsAppMessage = \App\Models\WhatsAppMessage::where('customer_id', $customer->id)
                                ->where('payment_id', $payment->id)
                                ->latest()
                                ->first();

                            $record->markAsSent($whatsAppMessage?->id);

                            Notification::make()
                                ->title('Berhasil!')
                                ->body("Reminder berhasil dikirim ulang ke {$customer->name} ({$customer->phone})")
                                ->success()
                                ->send();

                        } catch (\Exception $e) {
                            $record->markAsFailed($e->getMessage());

                            Log::error('Resend payment reminder failed', [
                                'reminder_id' => $record->id,
                                'payment_id' => $payment->id,
                                'error' => $e->getMessage(),
                            ]);

                            Notification::make()
                                ->title('Gagal Kirim Ulang')
                                ->body("Error: {$e->getMessage()}")
                                ->danger()
                                ->send();
                        }
                    }),
            ])
            ->bulkActions([
                Tables\Actions\BulkAction::make('resend_failed')
                    ->label('Kirim Ulang yang Gagal')
                    ->icon('heroicon-o-arrow-path')
                    ->color('warning')
                    ->requiresConfirmation()
                    ->modalHeading('Kirim Ulang Semua Reminder Gagal')
                    ->modalDescription('Kirim ulang semua reminder yang gagal? Customer yang sudah bayar akan otomatis dilewati.')
                    ->modalSubmitActionLabel('Ya, Kirim Ulang Semua')
                    ->deselectRecordsAfterCompletion()
                    ->action(function (\Illuminate\Database\Eloquent\Collection $records) {
                        $sent = 0;
                        $skipped = 0;
                        $failed = 0;

                        foreach ($records as $record) {
                            // Skip if not failed
                            if ($record->status !== 'failed') {
                                $skipped++;
                                continue;
                            }

                            // Skip if already paid
                            if ($record->payment?->status === 'paid') {
                                $skipped++;
                                continue;
                            }

                            $payment = $record->payment;
                            $customer = $payment?->customer;

                            if (!$customer?->phone) {
                                $skipped++;
                                continue;
                            }

                            try {
                                $whatsapp = new WhatsAppService();
                                $rule = $record->reminderRule;

                                $days = $rule?->days_before_due ?? 0;
                                if ($days <= -7) {
                                    $serviceType = 'reminder';
                                } elseif ($days == -3) {
                                    $serviceType = 'reminder_h3';
                                } elseif ($days == -1) {
                                    $serviceType = 'reminder_h1';
                                } elseif ($days == 0) {
                                    $serviceType = 'reminder_h0';
                                } else {
                                    $serviceType = 'overdue';
                                }

                                $whatsapp->sendBillingNotification(
                                    $payment,
                                    $serviceType,
                                    true,
                                    $rule?->whatsappTemplate
                                );

                                $whatsAppMessage = \App\Models\WhatsAppMessage::where('customer_id', $customer->id)
                                    ->where('payment_id', $payment->id)
                                    ->latest()
                                    ->first();

                                $record->markAsSent($whatsAppMessage?->id);
                                $sent++;

                                usleep(500000); // 0.5s delay between sends
                            } catch (\Exception $e) {
                                $record->markAsFailed($e->getMessage());
                                $failed++;
                            }
                        }

                        Notification::make()
                            ->title('Kirim Ulang Selesai')
                            ->body("✅ Berhasil: {$sent} | ❌ Gagal: {$failed} | ⏭️ Dilewati: {$skipped}")
                            ->success()
                            ->send();
                    }),
            ]);
    }

    public static function getWidgets(): array
    {
        return [
            PaymentReminderResource\Widgets\ReminderStatsOverview::class,
        ];
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListPaymentReminders::route('/'),
        ];
    }

    public static function canCreate(): bool
    {
        return false;
    }
}
