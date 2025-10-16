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
use Filament\Tables\Columns\ImageColumn;
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
    
    protected static bool $shouldRegisterNavigation = false;

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
                    ->formatStateUsing(function ($state, WhatsAppMessage $record): HtmlString {
                        $html = '';
                        
                        // Show media preview if exists
                        if ($record->media_path && $record->media_type === 'image') {
                            // Handle path - remove 'storage/' prefix if exists, then add it back
                            $cleanPath = str_replace('storage/', '', $record->media_path);
                            $imageUrl = asset('storage/' . $cleanPath);
                            
                            // Check if file exists
                            $fullPath = public_path('storage/' . $cleanPath);
                            if (file_exists($fullPath)) {
                                $html .= '<div class="mb-2">';
                                $html .= '<a href="' . $imageUrl . '" target="_blank">';
                                $html .= '<img src="' . $imageUrl . '" class="w-20 h-20 object-cover rounded border border-gray-200 hover:border-gray-400 transition" alt="Preview" onerror="this.src=\'data:image/svg+xml,%3Csvg xmlns=\'http://www.w3.org/2000/svg\' width=\'80\' height=\'80\'%3E%3Crect fill=\'%23f3f4f6\' width=\'80\' height=\'80\'/%3E%3Ctext x=\'50%25\' y=\'50%25\' dominant-baseline=\'middle\' text-anchor=\'middle\' fill=\'%239ca3af\' font-size=\'12\'%3EError%3C/text%3E%3C/svg%3E\'" />';
                                $html .= '</a>';
                                $html .= '</div>';
                            } else {
                                $html .= '<div class="mb-2 text-xs text-red-600">';
                                $html .= '📷 <span class="italic">Gambar tidak ditemukan</span>';
                                $html .= '</div>';
                            }
                        } elseif ($record->media_path && $record->media_type === 'document') {
                            // Handle path - remove 'storage/' prefix if exists, then add it back
                            $cleanPath = str_replace('storage/', '', $record->media_path);
                            $documentUrl = asset('storage/' . $cleanPath);
                            $filename = basename($record->media_path);
                            
                            // Check if file exists
                            $fullPath = public_path('storage/' . $cleanPath);
                            if (file_exists($fullPath)) {
                                $html .= '<div class="mb-2">';
                                $html .= '<a href="' . $documentUrl . '" target="_blank" class="flex items-center space-x-2 text-xs text-blue-600 hover:text-blue-800 hover:underline">';
                                $html .= '<svg class="w-4 h-4 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">';
                                $html .= '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z"/>';
                                $html .= '</svg>';
                                $html .= '<span class="font-medium">📄 ' . $filename . '</span>';
                                $html .= '</a>';
                                $html .= '</div>';
                            } else {
                                $html .= '<div class="mb-2 text-xs text-red-600">';
                                $html .= '📄 <span class="italic">Dokumen tidak ditemukan: ' . htmlspecialchars($filename) . '</span>';
                                $html .= '</div>';
                            }
                        }
                        
                        // Add message text (handle null/empty state)
                        if (!empty($state)) {
                            $html .= nl2br(e($state));
                        }
                        
                        return new HtmlString($html);
                    })
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
                                'response' => json_encode($result),
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
