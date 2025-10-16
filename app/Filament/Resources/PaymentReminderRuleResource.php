<?php

namespace App\Filament\Resources;

use App\Filament\Resources\PaymentReminderRuleResource\Pages;
use App\Filament\Resources\PaymentReminderRuleResource\RelationManagers;
use App\Models\PaymentReminderRule;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class PaymentReminderRuleResource extends Resource
{
    protected static ?string $model = PaymentReminderRule::class;

    protected static ?string $navigationIcon = 'heroicon-o-bell-alert';

    protected static ?string $navigationLabel = 'Pengaturan Reminder';

    protected static ?string $modelLabel = 'Aturan Reminder';

    protected static ?string $pluralModelLabel = 'Pengaturan Reminder';

    protected static ?string $navigationGroup = 'WhatsApp';

    protected static ?int $navigationSort = 4;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Informasi Reminder')
                    ->schema([
                        Forms\Components\TextInput::make('name')
                            ->label('Nama Reminder')
                            ->required()
                            ->maxLength(255)
                            ->placeholder('Contoh: Pengingat 7 Hari Sebelum')
                            ->helperText('Nama untuk mengidentifikasi reminder ini'),

                        Forms\Components\TextInput::make('days_before_due')
                            ->label('Hari Sebelum/Sesudah Jatuh Tempo')
                            ->required()
                            ->numeric()
                            ->helperText('Gunakan angka negatif untuk sebelum jatuh tempo (contoh: -7, -3, -1), 0 untuk tepat jatuh tempo, positif untuk overdue (contoh: 1, 2, 3)')
                            ->placeholder('-7')
                            ->reactive()
                            ->afterStateUpdated(function ($state, $set) {
                                if ($state < 0) {
                                    $set('_timing_label', abs($state) . ' hari sebelum jatuh tempo');
                                } elseif ($state == 0) {
                                    $set('_timing_label', 'Tepat pada jatuh tempo');
                                } else {
                                    $set('_timing_label', $state . ' hari setelah jatuh tempo (overdue)');
                                }
                            }),

                        Forms\Components\Placeholder::make('_timing_label')
                            ->label('Waktu Pengiriman')
                            ->content(fn ($get) => $get('_timing_label') ?? '-')
                            ->visible(fn ($get) => $get('days_before_due') !== null),

                        Forms\Components\Select::make('whatsapp_template_id')
                            ->label('Template WhatsApp')
                            ->relationship('whatsappTemplate', 'name')
                            ->searchable()
                            ->preload()
                            ->nullable()
                            ->helperText('Template pesan yang akan digunakan. Kosongkan untuk menggunakan template default dari pengaturan.'),

                        Forms\Components\Textarea::make('description')
                            ->label('Deskripsi')
                            ->maxLength(500)
                            ->rows(3)
                            ->placeholder('Deskripsi opsional untuk reminder ini'),
                    ])
                    ->columns(1),

                Forms\Components\Section::make('Pengaturan Pengiriman')
                    ->schema([
                        Forms\Components\TimePicker::make('send_time')
                            ->label('Jam Pengiriman')
                            ->required()
                            ->seconds(false)
                            ->default('09:00')
                            ->helperText('Jam berapa reminder akan dikirim'),

                        Forms\Components\TextInput::make('priority')
                            ->label('Prioritas')
                            ->numeric()
                            ->default(0)
                            ->helperText('Urutan eksekusi (angka lebih kecil = prioritas lebih tinggi)'),

                        Forms\Components\Toggle::make('is_active')
                            ->label('Aktif')
                            ->default(true)
                            ->helperText('Nonaktifkan reminder tanpa menghapusnya'),
                    ])
                    ->columns(3),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('priority')
                    ->label('Prioritas')
                    ->sortable()
                    ->badge()
                    ->color('gray'),

                Tables\Columns\TextColumn::make('name')
                    ->label('Nama Reminder')
                    ->searchable()
                    ->sortable()
                    ->weight('medium'),

                Tables\Columns\TextColumn::make('days_before_due')
                    ->label('Waktu')
                    ->sortable()
                    ->formatStateUsing(function ($state) {
                        if ($state < 0) {
                            return 'H' . $state . ' (' . abs($state) . ' hari sebelum)';
                        } elseif ($state == 0) {
                            return 'H+0 (Jatuh tempo)';
                        } else {
                            return 'H+' . $state . ' (Overdue)';
                        }
                    })
                    ->badge()
                    ->color(fn ($state) => $state < 0 ? 'warning' : ($state == 0 ? 'info' : 'danger')),

                Tables\Columns\TextColumn::make('whatsappTemplate.name')
                    ->label('Template')
                    ->searchable()
                    ->default('Template Default')
                    ->color('gray'),

                Tables\Columns\TextColumn::make('send_time')
                    ->label('Jam Kirim')
                    ->time('H:i')
                    ->sortable(),

                Tables\Columns\IconColumn::make('is_active')
                    ->label('Status')
                    ->boolean()
                    ->sortable()
                    ->trueIcon('heroicon-o-check-circle')
                    ->falseIcon('heroicon-o-x-circle')
                    ->trueColor('success')
                    ->falseColor('danger'),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Dibuat')
                    ->dateTime('d M Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('priority', 'asc')
            ->filters([
                Tables\Filters\TernaryFilter::make('is_active')
                    ->label('Status')
                    ->placeholder('Semua')
                    ->trueLabel('Aktif')
                    ->falseLabel('Nonaktif'),

                Tables\Filters\SelectFilter::make('days_before_due')
                    ->label('Tipe Reminder')
                    ->options([
                        'before' => 'Sebelum Jatuh Tempo',
                        'on_due' => 'Tepat Jatuh Tempo',
                        'overdue' => 'Overdue',
                    ])
                    ->query(function (Builder $query, array $data) {
                        return $query->when(
                            $data['value'] === 'before',
                            fn (Builder $query) => $query->where('days_before_due', '<', 0)
                        )->when(
                            $data['value'] === 'on_due',
                            fn (Builder $query) => $query->where('days_before_due', '=', 0)
                        )->when(
                            $data['value'] === 'overdue',
                            fn (Builder $query) => $query->where('days_before_due', '>', 0)
                        );
                    }),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
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
            'index' => Pages\ListPaymentReminderRules::route('/'),
            'create' => Pages\CreatePaymentReminderRule::route('/create'),
            'edit' => Pages\EditPaymentReminderRule::route('/{record}/edit'),
        ];
    }
}
