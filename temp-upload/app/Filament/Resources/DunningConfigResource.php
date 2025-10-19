<?php

namespace App\Filament\Resources;

use App\Filament\Resources\DunningConfigResource\Pages;
use App\Models\DunningConfig;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class DunningConfigResource extends Resource
{
    protected static ?string $model = DunningConfig::class;
    protected static ?string $navigationIcon = 'heroicon-o-cog';
    protected static ?string $navigationGroup = 'Konfigurasi Sistem';
    protected static ?string $navigationLabel = 'Penagihan Otomatis';
    protected static ?int $navigationSort = 1;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Card::make()
                    ->schema([
                        Forms\Components\TextInput::make('name')
                            ->label('Nama Konfigurasi')
                            ->required()
                            ->maxLength(255),
                        
                        Forms\Components\Textarea::make('description')
                            ->label('Deskripsi')
                            ->maxLength(1000),
                        
                        Forms\Components\Toggle::make('is_active')
                            ->label('Aktif')
                            ->default(true),
                        
                        Forms\Components\Fieldset::make('Pengaturan Penagihan')
                            ->schema([
                                Forms\Components\TextInput::make('grace_period_days')
                                    ->label('Masa Tenggang (hari)')
                                    ->numeric()
                                    ->default(0)
                                    ->minValue(0)
                                    ->maxValue(30)
                                    ->helperText('Hari toleransi setelah jatuh tempo sebelum sistem mulai proses dunning'),
                            ]),
                        
                        Forms\Components\Fieldset::make('Pengaturan Penangguhan')
                            ->schema([
                                Forms\Components\Toggle::make('auto_suspend')
                                    ->label('Tangguhkan Otomatis')
                                    ->default(false),
                                
                                Forms\Components\TextInput::make('suspend_after_days')
                                    ->label('Tangguhkan Setelah (hari)')
                                    ->numeric()
                                    ->default(7)
                                    ->minValue(1)
                                    ->maxValue(30),
                                
                                Forms\Components\Toggle::make('auto_unsuspend_on_payment')
                                    ->label('Aktifkan Kembali Otomatis Setelah Pembayaran')
                                    ->default(true)
                                    ->helperText('Otomatis aktifkan kembali layanan saat customer melakukan pembayaran'),
                            ]),
                        
                        Forms\Components\Fieldset::make('Integrasi n8n')
                            ->schema([
                                Forms\Components\Toggle::make('n8n_enabled')
                                    ->label('Aktifkan Integrasi n8n')
                                    ->default(false)
                                    ->live()
                                    ->helperText('Kirim webhook ke n8n untuk suspend otomatis via Mikrotik/Router'),
                                
                                Forms\Components\TextInput::make('n8n_webhook_url')
                                    ->label('URL Webhook n8n')
                                    ->url()
                                    ->placeholder('https://your-n8n-instance.com/webhook/suspend-customer')
                                    ->helperText('URL webhook n8n yang akan menerima data customer overdue')
                                    ->visible(fn ($get) => $get('n8n_enabled'))
                                    ->required(fn ($get) => $get('n8n_enabled')),
                                
                                Forms\Components\TextInput::make('n8n_trigger_after_days')
                                    ->label('Trigger Setelah (hari keterlambatan)')
                                    ->numeric()
                                    ->default(7)
                                    ->minValue(1)
                                    ->maxValue(30)
                                    ->helperText('Berapa hari setelah jatuh tempo baru trigger webhook n8n')
                                    ->visible(fn ($get) => $get('n8n_enabled'))
                                    ->required(fn ($get) => $get('n8n_enabled')),
                                
                                Forms\Components\Toggle::make('n8n_auto_unsuspend')
                                    ->label('Auto Unsuspend saat Customer Bayar')
                                    ->default(true)
                                    ->helperText('Kirim webhook unsuspend ke n8n saat customer melakukan pembayaran')
                                    ->visible(fn ($get) => $get('n8n_enabled')),
                            ]),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label('Nama Konfigurasi')
                    ->searchable()
                    ->sortable(),
                
                Tables\Columns\TextColumn::make('grace_period_days')
                    ->label('Masa Tenggang')
                    ->suffix(' hari')
                    ->sortable(),
                
                Tables\Columns\IconColumn::make('n8n_enabled')
                    ->label('n8n Aktif')
                    ->boolean()
                    ->sortable(),
                
                Tables\Columns\TextColumn::make('n8n_trigger_after_days')
                    ->label('Trigger Setelah')
                    ->suffix(' hari')
                    ->sortable()
                    ->default('-'),
                
                Tables\Columns\IconColumn::make('auto_suspend')
                    ->label('Auto Suspend')
                    ->boolean()
                    ->sortable(),
                
                Tables\Columns\IconColumn::make('is_active')
                    ->label('Status')
                    ->boolean()
                    ->sortable(),
                
                Tables\Columns\TextColumn::make('updated_at')
                    ->label('Terakhir Diperbarui')
                    ->dateTime('d M Y H:i')
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\Filter::make('is_active')
                    ->label('Hanya yang Aktif')
                    ->query(fn ($query) => $query->where('is_active', true)),
            ])
            ->actions([
                Tables\Actions\Action::make('test_webhook')
                    ->label('Test Webhook')
                    ->icon('heroicon-o-bolt')
                    ->color('warning')
                    ->visible(fn ($record) => $record->n8n_enabled && $record->n8n_webhook_url)
                    ->requiresConfirmation()
                    ->modalHeading('Test Webhook n8n')
                    ->modalDescription('Kirim data customer REAL ke webhook n8n untuk testing & debugging. Flag test_mode=true akan dikirim agar n8n bisa skip aksi Mikrotik.')
                    ->modalSubmitActionLabel('Kirim Test')
                    ->action(function ($record) {
                        $dunningService = app(\App\Services\DunningService::class);
                        $result = $dunningService->testN8nWebhook($record);
                        
                        if ($result['success']) {
                            \Filament\Notifications\Notification::make()
                                ->success()
                                ->title('Webhook Berhasil!')
                                ->body("Status Code: {$result['status_code']} - {$result['message']}")
                                ->duration(5000)
                                ->send();
                        } else {
                            \Filament\Notifications\Notification::make()
                                ->danger()
                                ->title('Webhook Gagal!')
                                ->body($result['message'])
                                ->duration(8000)
                                ->send();
                        }
                    }),
                    
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
            'index' => Pages\ListDunningConfigs::route('/'),
            'create' => Pages\CreateDunningConfig::route('/create'),
            'edit' => Pages\EditDunningConfig::route('/{record}/edit'),
        ];
    }
}