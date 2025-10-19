<?php

namespace App\Filament\Resources;

use App\Filament\Resources\BroadcastCampaignResource\Pages;
use App\Models\BroadcastCampaign;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Infolists\Infolist;
use Filament\Infolists\Components;
use Illuminate\Support\HtmlString;

class BroadcastCampaignResource extends Resource
{
    protected static ?string $model = BroadcastCampaign::class;

    protected static ?string $navigationIcon = 'heroicon-o-megaphone';
    
    protected static ?string $navigationLabel = 'Riwayat Broadcast';
    
    protected static ?string $modelLabel = 'Broadcast';
    
    protected static ?string $pluralModelLabel = 'Broadcast';
    
    protected static ?string $navigationGroup = 'WhatsApp';
    
    protected static ?int $navigationSort = 2;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('title')
                    ->label('Judul')
                    ->required()
                    ->maxLength(255),
                Forms\Components\Textarea::make('message')
                    ->label('Pesan')
                    ->required()
                    ->rows(5),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('title')
                    ->label('Judul Broadcast')
                    ->searchable()
                    ->sortable()
                    ->description(fn (BroadcastCampaign $record): string => 
                        $record->recipient_type_label
                    )
                    ->weight('bold'),

                Tables\Columns\TextColumn::make('messages_count')
                    ->label('Total Kontak')
                    ->counts('messages')
                    ->alignCenter()
                    ->badge()
                    ->color('info'),

                Tables\Columns\TextColumn::make('creator.name')
                    ->label('Dikirim Oleh')
                    ->searchable()
                    ->sortable()
                    ->default('-'),

                Tables\Columns\TextColumn::make('sent_at')
                    ->label('Tanggal Dibuat')
                    ->dateTime('d M Y H:i')
                    ->sortable()
                    ->default('-'),

                Tables\Columns\TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => match($state) {
                        'completed' => 'Selesai',
                        'processing' => 'Diproses',
                        'pending' => 'Menunggu',
                        'failed' => 'Gagal',
                        default => $state,
                    })
                    ->color(fn (string $state): string => match ($state) {
                        'completed' => 'success',
                        'processing' => 'info',
                        'pending' => 'warning',
                        'failed' => 'danger',
                        default => 'gray',
                    }),

                Tables\Columns\TextColumn::make('success_count')
                    ->label('Berhasil')
                    ->alignCenter()
                    ->badge()
                    ->color('success'),

                Tables\Columns\TextColumn::make('failed_count')
                    ->label('Gagal')
                    ->alignCenter()
                    ->badge()
                    ->color('danger'),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->label('Status')
                    ->options([
                        'completed' => 'Selesai',
                        'processing' => 'Diproses',
                        'pending' => 'Menunggu',
                        'failed' => 'Gagal',
                    ]),
                Tables\Filters\SelectFilter::make('recipient_type')
                    ->label('Tipe Penerima')
                    ->options([
                        'all' => 'Semua Pelanggan',
                        'active' => 'Pelanggan Aktif',
                        'custom' => 'Pilihan Manual',
                    ]),
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
            ->defaultSort('created_at', 'desc')
            ->poll('30s');
    }

    public static function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                Components\Section::make('Informasi Broadcast')
                    ->schema([
                        Components\Grid::make(2)
                            ->schema([
                                Components\TextEntry::make('title')
                                    ->label('Judul')
                                    ->size('lg')
                                    ->weight('bold')
                                    ->columnSpanFull(),
                                
                                Components\TextEntry::make('status')
                                    ->label('Status')
                                    ->badge()
                                    ->formatStateUsing(fn (string $state): string => match($state) {
                                        'completed' => 'Selesai',
                                        'processing' => 'Diproses',
                                        'pending' => 'Menunggu',
                                        'failed' => 'Gagal',
                                        default => $state,
                                    })
                                    ->color(fn (string $state): string => match ($state) {
                                        'completed' => 'success',
                                        'processing' => 'info',
                                        'pending' => 'warning',
                                        'failed' => 'danger',
                                        default => 'gray',
                                    }),

                                Components\TextEntry::make('total_recipients')
                                    ->label('Total Penerima')
                                    ->badge()
                                    ->color('info'),

                                Components\TextEntry::make('success_count')
                                    ->label('Berhasil')
                                    ->badge()
                                    ->color('success'),

                                Components\TextEntry::make('failed_count')
                                    ->label('Gagal')
                                    ->badge()
                                    ->color('danger'),

                                Components\TextEntry::make('recipient_type_label')
                                    ->label('Tipe Penerima'),

                                Components\TextEntry::make('creator.name')
                                    ->label('Dibuat Oleh')
                                    ->default('-'),

                                Components\TextEntry::make('sent_at')
                                    ->label('Tanggal Dikirim')
                                    ->dateTime('d F Y, H:i')
                                    ->default('-'),

                                Components\TextEntry::make('created_at')
                                    ->label('Tanggal Dibuat')
                                    ->dateTime('d F Y, H:i'),
                            ]),
                    ]),

                Components\Section::make('Pesan')
                    ->schema([
                        Components\TextEntry::make('message')
                            ->label('')
                            ->markdown()
                            ->columnSpanFull(),
                    ]),

                Components\Section::make('Lampiran')
                    ->schema([
                        Components\ImageEntry::make('media_path')
                            ->label('Gambar')
                            ->disk('public')
                            ->height(300)
                            ->visible(fn (BroadcastCampaign $record) => $record->media_type === 'image'),

                        Components\TextEntry::make('media_path')
                            ->label('Dokumen')
                            ->formatStateUsing(fn (string $state): HtmlString => new HtmlString(
                                '<a href="' . asset('storage/' . $state) . '" target="_blank" class="text-primary-600 hover:underline flex items-center gap-2">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z"/>
                                    </svg>
                                    ' . basename($state) . '
                                </a>'
                            ))
                            ->visible(fn (BroadcastCampaign $record) => $record->media_type === 'document'),
                    ])
                    ->visible(fn (BroadcastCampaign $record) => !empty($record->media_path)),
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
            'index' => Pages\ListBroadcastCampaigns::route('/'),
            'view' => Pages\ViewBroadcastCampaign::route('/{record}'),
        ];
    }
}

