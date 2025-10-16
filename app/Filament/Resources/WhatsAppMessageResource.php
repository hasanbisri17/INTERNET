<?php

namespace App\Filament\Resources;

use App\Filament\Resources\WhatsAppMessageResource\Pages;
use App\Models\WhatsAppMessage;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Infolists\Infolist;
use Filament\Infolists\Components;
use Illuminate\Database\Eloquent\Builder;

class WhatsAppMessageResource extends Resource
{
    protected static ?string $model = WhatsAppMessage::class;

    protected static ?string $navigationIcon = 'heroicon-o-chat-bubble-left-right';
    
    protected static ?string $navigationLabel = 'Riwayat Pesan Sistem';
    
    protected static ?string $modelLabel = 'Pesan Sistem';
    
    protected static ?string $pluralModelLabel = 'Pesan Sistem';
    
    protected static ?string $navigationGroup = 'WhatsApp';
    
    protected static ?int $navigationSort = 3;

    public static function getEloquentQuery(): Builder
    {
        // Only show system messages (not broadcast)
        return parent::getEloquentQuery()
            ->where('message_type', '!=', 'broadcast');
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                //
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('customer.name')
                    ->label('Pelanggan')
                    ->searchable()
                    ->sortable()
                    ->description(fn (WhatsAppMessage $record): string => 
                        $record->customer->phone ?? '-'
                    )
                    ->weight('medium'),

                Tables\Columns\TextColumn::make('message_type')
                    ->label('Jenis Pesan')
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => match($state) {
                        'billing.new' => 'Tagihan Baru',
                        'billing.reminder' => 'Pengingat Tagihan',
                        'billing.overdue' => 'Tagihan Terlambat',
                        'billing.paid' => 'Konfirmasi Pembayaran',
                        'broadcast' => 'Broadcast',
                        default => ucwords(str_replace(['.', '_'], ' ', $state)),
                    })
                    ->color(fn (string $state): string => match ($state) {
                        'billing.new' => 'info',
                        'billing.reminder' => 'warning',
                        'billing.overdue' => 'danger',
                        'billing.paid' => 'success',
                        'broadcast' => 'gray',
                        default => 'secondary',
                    })
                    ->sortable()
                    ->searchable(),

                Tables\Columns\TextColumn::make('payment.invoice_number')
                    ->label('No. Invoice')
                    ->searchable()
                    ->sortable()
                    ->default('-')
                    ->description(fn (WhatsAppMessage $record): ?string => 
                        $record->payment ? 'Rp ' . number_format($record->payment->amount, 0, ',', '.') : null
                    ),

                Tables\Columns\TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => match($state) {
                        'sent' => 'Terkirim',
                        'failed' => 'Gagal',
                        'pending' => 'Menunggu',
                        default => ucfirst($state),
                    })
                    ->color(fn (string $state): string => match ($state) {
                        'sent' => 'success',
                        'failed' => 'danger',
                        'pending' => 'warning',
                        default => 'gray',
                    })
                    ->icon(fn (string $state): string => match ($state) {
                        'sent' => 'heroicon-o-check-circle',
                        'failed' => 'heroicon-o-x-circle',
                        'pending' => 'heroicon-o-clock',
                        default => 'heroicon-o-question-mark-circle',
                    }),

                Tables\Columns\TextColumn::make('sent_at')
                    ->label('Waktu Kirim')
                    ->dateTime('d M Y, H:i')
                    ->sortable()
                    ->default('-')
                    ->description(fn (WhatsAppMessage $record): ?string => 
                        $record->sent_at ? $record->sent_at->diffForHumans() : null
                    ),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('message_type')
                    ->label('Jenis Pesan')
                    ->options([
                        'billing.new' => 'Tagihan Baru',
                        'billing.reminder' => 'Pengingat Tagihan',
                        'billing.overdue' => 'Tagihan Terlambat',
                        'billing.paid' => 'Konfirmasi Pembayaran',
                    ])
                    ->multiple(),
                
                Tables\Filters\SelectFilter::make('status')
                    ->label('Status')
                    ->options([
                        'sent' => 'Terkirim',
                        'failed' => 'Gagal',
                        'pending' => 'Menunggu',
                    ])
                    ->multiple(),

                Tables\Filters\Filter::make('sent_at')
                    ->form([
                        Forms\Components\DatePicker::make('sent_from')
                            ->label('Dari Tanggal'),
                        Forms\Components\DatePicker::make('sent_until')
                            ->label('Sampai Tanggal'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                $data['sent_from'],
                                fn (Builder $query, $date): Builder => $query->whereDate('sent_at', '>=', $date),
                            )
                            ->when(
                                $data['sent_until'],
                                fn (Builder $query, $date): Builder => $query->whereDate('sent_at', '<=', $date),
                            );
                    }),
            ])
            ->actions([
                Tables\Actions\ViewAction::make()
                    ->label('Detail'),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('sent_at', 'desc')
            ->poll('30s');
    }

    public static function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                Components\Section::make('Informasi Pesan')
                    ->schema([
                        Components\Grid::make(2)
                            ->schema([
                                Components\TextEntry::make('message_type')
                                    ->label('Jenis Pesan')
                                    ->badge()
                                    ->formatStateUsing(fn (string $state): string => match($state) {
                                        'billing.new' => 'Tagihan Baru',
                                        'billing.reminder' => 'Pengingat Tagihan',
                                        'billing.overdue' => 'Tagihan Terlambat',
                                        'billing.paid' => 'Konfirmasi Pembayaran',
                                        'broadcast' => 'Broadcast',
                                        default => ucwords(str_replace(['.', '_'], ' ', $state)),
                                    })
                                    ->color(fn (string $state): string => match ($state) {
                                        'billing.new' => 'info',
                                        'billing.reminder' => 'warning',
                                        'billing.overdue' => 'danger',
                                        'billing.paid' => 'success',
                                        'broadcast' => 'gray',
                                        default => 'secondary',
                                    }),

                                Components\TextEntry::make('status')
                                    ->label('Status')
                                    ->badge()
                                    ->formatStateUsing(fn (string $state): string => match($state) {
                                        'sent' => 'Terkirim',
                                        'failed' => 'Gagal',
                                        'pending' => 'Menunggu',
                                        default => ucfirst($state),
                                    })
                                    ->color(fn (string $state): string => match ($state) {
                                        'sent' => 'success',
                                        'failed' => 'danger',
                                        'pending' => 'warning',
                                        default => 'gray',
                                    }),

                                Components\TextEntry::make('customer.name')
                                    ->label('Nama Pelanggan')
                                    ->default('-'),

                                Components\TextEntry::make('customer.phone')
                                    ->label('No. WhatsApp')
                                    ->default('-'),

                                Components\TextEntry::make('payment.invoice_number')
                                    ->label('No. Invoice')
                                    ->default('-')
                                    ->visible(fn ($record) => $record->payment_id),

                                Components\TextEntry::make('payment.amount')
                                    ->label('Jumlah Tagihan')
                                    ->money('IDR')
                                    ->visible(fn ($record) => $record->payment_id),

                                Components\TextEntry::make('sent_at')
                                    ->label('Waktu Kirim')
                                    ->dateTime('d F Y, H:i')
                                    ->default('-'),

                                Components\TextEntry::make('created_at')
                                    ->label('Dibuat Pada')
                                    ->dateTime('d F Y, H:i'),
                            ]),
                    ]),

                Components\Section::make('Isi Pesan')
                    ->schema([
                        Components\TextEntry::make('message')
                            ->label('')
                            ->formatStateUsing(function (string $state): \Illuminate\Support\HtmlString {
                                return new \Illuminate\Support\HtmlString(
                                    '<div class="prose prose-sm max-w-none dark:prose-invert whitespace-pre-wrap">' . 
                                    nl2br(e($state)) . 
                                    '</div>'
                                );
                            })
                            ->columnSpanFull(),
                    ]),

                Components\Section::make('Lampiran')
                    ->schema([
                        // PDF Document (Invoice)
                        Components\TextEntry::make('media_path')
                            ->label('Dokumen Invoice')
                            ->formatStateUsing(fn (string $state): \Illuminate\Support\HtmlString => new \Illuminate\Support\HtmlString(
                                '<div class="flex items-center gap-4 p-4 bg-gradient-to-r from-blue-50 to-indigo-50 dark:from-blue-900/20 dark:to-indigo-900/20 rounded-lg border-2 border-blue-200 dark:border-blue-800">
                                    <div class="flex-shrink-0 w-16 h-16 bg-red-100 dark:bg-red-900/30 rounded-lg flex items-center justify-center">
                                        <svg class="w-10 h-10 text-red-600 dark:text-red-400" fill="currentColor" viewBox="0 0 20 20">
                                            <path fill-rule="evenodd" d="M4 4a2 2 0 012-2h4.586A2 2 0 0112 2.586L15.414 6A2 2 0 0116 7.414V16a2 2 0 01-2 2H6a2 2 0 01-2-2V4zm2 6a1 1 0 011-1h6a1 1 0 110 2H7a1 1 0 01-1-1zm1 3a1 1 0 100 2h6a1 1 0 100-2H7z" clip-rule="evenodd"/>
                                        </svg>
                                    </div>
                                    <div class="flex-1 min-w-0">
                                        <p class="text-lg font-bold text-gray-900 dark:text-gray-100 mb-1">
                                            📄 ' . basename($state) . '
                                        </p>
                                        <p class="text-sm text-gray-600 dark:text-gray-400 mb-3">
                                            Dokumen PDF Invoice
                                        </p>
                                        <div class="flex gap-2">
                                            <a href="' . asset('storage/' . $state) . '" 
                                               target="_blank"
                                               class="inline-flex items-center gap-2 px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white text-sm font-medium rounded-lg transition-colors shadow-sm">
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                                                </svg>
                                                Lihat PDF
                                            </a>
                                            <a href="' . asset('storage/' . $state) . '" 
                                               download
                                               class="inline-flex items-center gap-2 px-4 py-2 bg-green-600 hover:bg-green-700 text-white text-sm font-medium rounded-lg transition-colors shadow-sm">
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/>
                                                </svg>
                                                Download
                                            </a>
                                        </div>
                                    </div>
                                </div>'
                            ))
                            ->visible(fn ($record) => !empty($record->media_path) && $record->media_type === 'document'),

                        // Image
                        Components\TextEntry::make('media_path')
                            ->label('Gambar')
                            ->formatStateUsing(fn (string $state): \Illuminate\Support\HtmlString => new \Illuminate\Support\HtmlString(
                                '<div class="rounded-lg overflow-hidden border-2 border-gray-200 dark:border-gray-700 inline-block">
                                    <img src="' . asset('storage/' . $state) . '" alt="Media" class="max-w-full h-auto" style="max-height: 400px; width: auto;" />
                                </div>'
                            ))
                            ->visible(fn ($record) => !empty($record->media_path) && $record->media_type === 'image'),
                    ])
                    ->visible(fn ($record) => !empty($record->media_path)),

                Components\Section::make('Response API')
                    ->schema([
                        Components\TextEntry::make('response')
                            ->label('')
                            ->formatStateUsing(function (?string $state): \Illuminate\Support\HtmlString {
                                if (!$state) {
                                    return new \Illuminate\Support\HtmlString('<span class="text-gray-500">Tidak ada response</span>');
                                }
                                
                                $decoded = json_decode($state, true);
                                if (json_last_error() === JSON_ERROR_NONE) {
                                    $formatted = json_encode($decoded, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
                                    return new \Illuminate\Support\HtmlString(
                                        '<div class="w-full" style="max-width: 100%; overflow-x: auto;">
                                            <div class="bg-gray-50 dark:bg-gray-900 p-4 rounded-lg border border-gray-200 dark:border-gray-700" style="word-wrap: break-word; overflow-wrap: anywhere;">
                                                <pre class="text-xs font-mono m-0" style="white-space: pre-wrap; word-break: break-all; overflow-wrap: anywhere; max-width: 100%;"><code>' . 
                                                htmlspecialchars($formatted) . 
                                            '</code></pre>
                                            </div>
                                        </div>'
                                    );
                                }
                                
                                return new \Illuminate\Support\HtmlString(
                                    '<div class="w-full" style="max-width: 100%; overflow-x: auto;">
                                        <div class="bg-gray-50 dark:bg-gray-900 p-4 rounded-lg border border-gray-200 dark:border-gray-700" style="word-wrap: break-word; overflow-wrap: anywhere;">
                                            <pre class="text-xs font-mono m-0" style="white-space: pre-wrap; word-break: break-all; overflow-wrap: anywhere; max-width: 100%;">' . 
                                            htmlspecialchars($state) . 
                                        '</pre>
                                        </div>
                                    </div>'
                                );
                            })
                            ->columnSpanFull(),
                    ])
                    ->collapsible()
                    ->collapsed(),
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
            'view' => Pages\ViewWhatsAppMessage::route('/{record}'),
        ];
    }
}

