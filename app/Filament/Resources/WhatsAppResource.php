<?php

namespace App\Filament\Resources;

use App\Filament\Resources\WhatsAppResource\Pages;
use App\Models\WhatsAppMessage;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Tables\Columns\TextColumn;
use Filament\Notifications\Notification;
use Filament\Tables\Actions\Action;
use Filament\Tables\Actions\BulkActionGroup;
use Filament\Tables\Actions\DeleteAction;
use Filament\Tables\Actions\DeleteBulkAction;
use Filament\Tables\Actions\ViewAction;
use Filament\Tables\Filters\SelectFilter;
use App\Services\WhatsAppService;
use Illuminate\Support\HtmlString;

class WhatsAppResource extends Resource
{
    protected static ?string $model = WhatsAppMessage::class;

    protected static ?string $navigationIcon = 'heroicon-o-chat-bubble-left-right';

    protected static ?string $navigationLabel = 'WhatsApp';

    protected static ?string $modelLabel = 'Pesan WhatsApp';

    protected static ?string $pluralModelLabel = 'Pesan WhatsApp';

    protected static ?string $navigationGroup = 'WhatsApp';

    protected static ?int $navigationSort = 1;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Select::make('customer_id')
                    ->relationship('customer', 'name')
                    ->searchable()
                    ->preload()
                    ->required(),
                Forms\Components\TextInput::make('message_type')
                    ->required()
                    ->maxLength(255),
                Forms\Components\Textarea::make('message')
                    ->required()
                    ->maxLength(65535)
                    ->columnSpanFull(),
                Forms\Components\Select::make('status')
                    ->options([
                        'pending' => 'Menunggu',
                        'sent' => 'Terkirim',
                        'failed' => 'Gagal',
                    ])
                    ->required(),
                Forms\Components\DateTimePicker::make('sent_at'),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('customer.name')
                    ->label('Pelanggan')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('message_type')
                    ->label('Tipe Pesan')
                    ->formatStateUsing(fn (string $state): string => match($state) {
                        'billing.new' => 'Tagihan Baru',
                        'billing.reminder' => 'Pengingat Tagihan',
                        'billing.overdue' => 'Tagihan Terlambat',
                        'billing.paid' => 'Konfirmasi Pembayaran',
                        'broadcast' => 'Broadcast',
                        default => $state,
                    })
                    ->badge()
                    ->color('primary'),
                TextColumn::make('message')
                    ->label('Pesan')
                    ->html()
                    ->formatStateUsing(fn (string $state): HtmlString => new HtmlString(
                        nl2br(e($state))
                    ))
                    ->limit(50)
                    ->searchable(),
                TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => match($state) {
                        'pending' => 'Menunggu',
                        'sent' => 'Terkirim',
                        'failed' => 'Gagal',
                        default => $state,
                    })
                    ->color(fn (string $state): string => match($state) {
                        'sent' => 'success',
                        'pending' => 'warning',
                        'failed' => 'danger',
                        default => 'secondary',
                    }),
                TextColumn::make('scheduled_at')
                    ->label('Dijadwalkan')
                    ->dateTime('d/m/Y H:i')
                    ->timezone('Asia/Jakarta')
                    ->sortable(),
                TextColumn::make('sent_at')
                    ->label('Waktu Kirim')
                    ->dateTime('d/m/Y H:i')
                    ->timezone('Asia/Jakarta')
                    ->sortable(),
            ])
            ->defaultSort('sent_at', 'desc')
            ->filters([
                SelectFilter::make('status')
                    ->options([
                        'pending' => 'Menunggu',
                        'sent' => 'Terkirim',
                        'failed' => 'Gagal',
                    ]),
                SelectFilter::make('message_type')
                    ->label('Tipe Pesan')
                    ->options([
                        'billing.new' => 'Tagihan Baru',
                        'billing.reminder' => 'Pengingat Tagihan',
                        'billing.overdue' => 'Tagihan Terlambat',
                        'billing.paid' => 'Konfirmasi Pembayaran',
                        'broadcast' => 'Broadcast',
                    ]),
            ])
            ->actions([
                Action::make('resend')
                    ->label('Kirim Ulang')
                    ->icon('heroicon-m-arrow-path')
                    ->color('warning')
                    ->action(function (WhatsAppMessage $record, $livewire): void {
                        try {
                            $whatsapp = new WhatsAppService();
                            $result = $whatsapp->sendMessage($record->customer->phone, $record->message);
                            
                            $record->update([
                                'status' => $result['success'] ? 'sent' : 'failed',
                                'response' => $result,
                                'sent_at' => now(),
                            ]);

                            Notification::make()
                                ->title('Message Resent')
                                ->success()
                                ->send();

                            // Refresh the Livewire component (and thus the table)
                            $livewire->dispatch('$refresh');
                        } catch (\Exception $e) {
                            Notification::make()
                                ->title('Error')
                                ->body($e->getMessage())
                                ->danger()
                                ->send();
                        }
                    })
                    ->visible(fn (WhatsAppMessage $record): bool => $record->status === 'failed'),
                ViewAction::make(),
                DeleteAction::make(),
            ])
            ->bulkActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
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
            'index' => Pages\ListWhatsAppMessages::route('/'),
        ];
    }
}
