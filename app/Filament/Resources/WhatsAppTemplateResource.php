<?php

namespace App\Filament\Resources;

use App\Filament\Resources\WhatsAppTemplateResource\Pages;
use App\Models\WhatsAppTemplate;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class WhatsAppTemplateResource extends Resource
{
    protected static ?string $model = WhatsAppTemplate::class;

    protected static ?string $navigationIcon = 'heroicon-o-document-text';

    protected static ?string $navigationLabel = 'Template Pesan';

    protected static ?string $modelLabel = 'Template Pesan';

    protected static ?string $navigationGroup = 'WhatsApp';

    protected static ?int $navigationSort = 3;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Informasi Template')
                    ->description('Tentukan jenis template dan informasi dasar')
                    ->schema([
                        Forms\Components\Select::make('template_type')
                            ->label('Jenis Template')
                            ->options(WhatsAppTemplate::getTemplateTypes())
                            ->required()
                            ->searchable()
                            ->native(false)
                            ->helperText('Pilih jenis template untuk menentukan kapan template ini digunakan'),

                        Forms\Components\Grid::make(2)
                            ->schema([
                                Forms\Components\TextInput::make('name')
                                    ->label('Nama Template')
                                    ->required()
                                    ->maxLength(255)
                                    ->placeholder('Contoh: Tagihan Baru Modern'),

                                Forms\Components\TextInput::make('code')
                                    ->label('Kode Template')
                                    ->required()
                                    ->maxLength(255)
                                    ->unique(ignoreRecord: true)
                                    ->placeholder('Contoh: billing.new.v2')
                                    ->helperText('Kode unik untuk template ini'),
                            ]),

                        Forms\Components\TextInput::make('order')
                            ->label('Urutan')
                            ->numeric()
                            ->default(0)
                            ->helperText('Urutan prioritas jika ada beberapa template dengan jenis yang sama (angka kecil = prioritas tinggi)'),

                        Forms\Components\Textarea::make('description')
                            ->label('Deskripsi')
                            ->rows(2)
                            ->maxLength(65535)
                            ->placeholder('Jelaskan kapan template ini digunakan')
                            ->columnSpanFull(),
                    ])
                    ->collapsible()
                    ->columns(1),

                Forms\Components\Section::make('Konten Pesan')
                    ->description('Tulis isi pesan template dengan variabel')
                    ->schema([
                        Forms\Components\Textarea::make('content')
                            ->label('Isi Pesan')
                            ->required()
                            ->rows(10)
                            ->maxLength(65535)
                            ->helperText('Gunakan {variable} untuk memasukkan variabel dinamis. Contoh: {customer_name}, {amount}')
                            ->placeholder('Yth. {customer_name},' . "\n\n" . 'Tagihan internet Anda...')
                            ->columnSpanFull(),

                        Forms\Components\TagsInput::make('variables')
                            ->label('Variabel yang Tersedia')
                            ->helperText('Daftar variabel yang dapat digunakan dalam template (tanpa kurung kurawal)')
                            ->placeholder('Contoh: customer_name, amount, due_date')
                            ->splitKeys(['Tab', ','])
                            ->columnSpanFull(),
                    ])
                    ->collapsible()
                    ->columns(1),

                Forms\Components\Section::make('Status')
                    ->schema([
                        Forms\Components\Toggle::make('is_active')
                            ->label('Aktif')
                            ->default(true)
                            ->required()
                            ->helperText('Hanya template yang aktif yang akan digunakan oleh sistem')
                            ->inline(false),
                    ])
                    ->collapsible()
                    ->columns(1),
                Forms\Components\Section::make('Preview')
                    ->description('Contoh pesan dengan variabel yang diisi')
                    ->schema([
                        Forms\Components\Placeholder::make('preview')
                            ->label('Preview Pesan')
                            ->content(function ($record) {
                                if (!$record) return 'Simpan template terlebih dahulu untuk melihat preview';
                                
                                $content = $record->content;
                                $variables = $record->variables ?? [];
                                
                                foreach ($variables as $var) {
                                    $content = str_replace("{{$var}}", "<{$var}>", $content);
                                }
                                
                                return nl2br(e($content));
                            }),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('template_type')
                    ->label('Jenis Template')
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => WhatsAppTemplate::getTemplateTypes()[$state] ?? $state)
                    ->color(fn (string $state): string => match($state) {
                        WhatsAppTemplate::TYPE_BILLING_NEW => 'info',
                        WhatsAppTemplate::TYPE_BILLING_REMINDER_1 => 'warning',
                        WhatsAppTemplate::TYPE_BILLING_REMINDER_2 => 'warning',
                        WhatsAppTemplate::TYPE_BILLING_REMINDER_3 => 'danger',
                        WhatsAppTemplate::TYPE_BILLING_OVERDUE => 'danger',
                        WhatsAppTemplate::TYPE_BILLING_PAID => 'success',
                        WhatsAppTemplate::TYPE_SERVICE_SUSPENDED => 'danger',
                        WhatsAppTemplate::TYPE_SERVICE_REACTIVATED => 'success',
                        default => 'gray',
                    })
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('name')
                    ->label('Nama Template')
                    ->searchable()
                    ->sortable()
                    ->weight('bold'),

                Tables\Columns\TextColumn::make('code')
                    ->label('Kode')
                    ->searchable()
                    ->copyable()
                    ->size('sm')
                    ->color('gray'),

                Tables\Columns\TextColumn::make('description')
                    ->label('Deskripsi')
                    ->limit(50)
                    ->wrap()
                    ->toggleable(),

                Tables\Columns\TextColumn::make('order')
                    ->label('Urutan')
                    ->sortable()
                    ->alignCenter()
                    ->toggleable(),

                Tables\Columns\IconColumn::make('is_active')
                    ->label('Status')
                    ->boolean()
                    ->trueIcon('heroicon-o-check-circle')
                    ->falseIcon('heroicon-o-x-circle')
                    ->trueColor('success')
                    ->falseColor('danger')
                    ->sortable(),

                Tables\Columns\TextColumn::make('updated_at')
                    ->label('Diperbarui')
                    ->dateTime('d M Y, H:i')
                    ->sortable()
                    ->toggleable(),
            ])
            ->defaultSort('order', 'asc')
            ->filters([
                Tables\Filters\SelectFilter::make('template_type')
                    ->label('Jenis Template')
                    ->options(WhatsAppTemplate::getTemplateTypes())
                    ->multiple()
                    ->searchable(),

                Tables\Filters\TernaryFilter::make('is_active')
                    ->label('Status')
                    ->placeholder('Semua Status')
                    ->trueLabel('Aktif')
                    ->falseLabel('Tidak Aktif'),
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

    public static function getPages(): array
    {
        return [
            'index' => Pages\ManageWhatsAppTemplates::route('/'),
        ];
    }

    public static function getNavigationBadge(): ?string
    {
        return WhatsAppTemplate::where('is_active', true)->count();
    }
}
