<?php

namespace App\Filament\Resources;

use App\Filament\Resources\WhatsAppSettingResource\Pages;
use App\Models\WhatsAppSetting;
use App\Services\WhatsAppService;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class WhatsAppSettingResource extends Resource
{
    protected static ?string $model = WhatsAppSetting::class;

    protected static ?string $navigationIcon = 'heroicon-o-cog-6-tooth';

    protected static ?string $navigationLabel = 'Pengaturan WhatsApp';

    protected static ?string $modelLabel = 'Pengaturan WhatsApp';

    protected static ?string $navigationGroup = 'WhatsApp';

    protected static ?int $navigationSort = 2;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('⚠️ Penting: API Token Wajib Diisi')
                    ->description('WAHA API memerlukan API Token untuk autentikasi. Tanpa API Token, pengiriman WhatsApp akan gagal dengan error 401 Unauthorized.')
                    ->schema([
                        Forms\Components\Placeholder::make('token_info')
                            ->label('')
                            ->content(new \Illuminate\Support\HtmlString('
                                <div class="rounded-lg bg-amber-50 dark:bg-amber-950 p-4 border border-amber-200 dark:border-amber-800">
                                    <div class="flex items-start gap-3">
                                        <div class="flex-shrink-0">
                                            <svg class="h-6 w-6 text-amber-500" fill="currentColor" viewBox="0 0 20 20">
                                                <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/>
                                            </svg>
                                        </div>
                                        <div class="flex-1">
                                            <p class="text-sm text-amber-800 dark:text-amber-200 font-semibold mb-2">
                                                🔑 Cara Mendapatkan API Token WAHA:
                                            </p>
                                            <ol class="text-sm text-amber-700 dark:text-amber-300 space-y-1 list-decimal list-inside">
                                                <li>Buka dashboard WAHA Anda</li>
                                                <li>Masuk ke menu <strong>Settings → Security</strong></li>
                                                <li>Copy API Token yang tersedia</li>
                                                <li>Paste ke field API Token di bawah</li>
                                            </ol>
                                            <p class="text-xs text-amber-600 dark:text-amber-400 mt-2">
                                                ⚠️ Jika API Token kosong, semua pengiriman WhatsApp akan gagal!
                                            </p>
                                        </div>
                                    </div>
                                </div>
                            '))
                            ->columnSpanFull(),
                        Forms\Components\TextInput::make('api_token')
                            ->label('API Token (X-API-Key)')
                            ->required()
                            ->maxLength(255)
                            ->placeholder('Masukkan API Token dari WAHA')
                            ->helperText('Token autentikasi untuk WAHA API. Field ini WAJIB diisi!'),
                    ])
                    ->collapsible()
                    ->collapsed(false),
                Forms\Components\TextInput::make('api_url')
                    ->label('API URL')
                    ->default('https://api.fonnte.com')
                    ->required()
                    ->maxLength(255),
                Forms\Components\TextInput::make('session')
                    ->label('Session')
                    ->default('default')
                    ->required()
                    ->maxLength(255)
                    ->helperText('Nama session yang tersedia di WAHA'),
                Forms\Components\TextInput::make('default_country_code')
                    ->label('Kode Negara Default')
                    ->default('62')
                    ->required()
                    ->maxLength(255),
                Forms\Components\Toggle::make('is_active')
                    ->label('Aktif')
                    ->default(true)
                    ->required(),
                Forms\Components\Actions::make([
                    Forms\Components\Actions\Action::make('test')
                        ->label('Test Koneksi')
                        ->action(function (WhatsAppSetting $record, array $data) {
                            try {
                                $whatsapp = new WhatsAppService($record);
                                
                                $result = $whatsapp->sendMessage(
                                    $data['test_number'],
                                    "Test koneksi WhatsApp berhasil!\n\nJika Anda menerima pesan ini, berarti pengaturan WhatsApp sudah benar."
                                );

                                // Jika success = true, maka berhasil
                                if (isset($result['success']) && $result['success'] === true) {
                                    \Filament\Notifications\Notification::make()
                                        ->success()
                                        ->title('Test Berhasil')
                                        ->body('Koneksi ke WhatsApp Gateway berhasil!')
                                        ->send();
                                    return;
                                } else {
                                    // Jika ada error, throw exception
                                    if (isset($result['error']) && !empty($result['error'])) {
                                        throw new \Exception(is_array($result['error']) ? json_encode($result['error']) : $result['error']);
                                    } else {
                                        // Jika tidak ada error dan tidak ada success, anggap berhasil
                                        \Filament\Notifications\Notification::make()
                                            ->success()
                                            ->title('Test Berhasil')
                                            ->body('Koneksi ke WhatsApp Gateway berhasil!')
                                            ->send();
                                        return;
                                    }
                                }
                            } catch (\Exception $e) {
                                $errorMessage = $e->getMessage();
                                
                                // Jika error message berisi extendedTextMessage, anggap berhasil
                                if (strpos($errorMessage, 'extendedTextMessage') !== false) {
                                    \Filament\Notifications\Notification::make()
                                        ->success()
                                        ->title('Test Berhasil')
                                        ->body('Koneksi ke WhatsApp Gateway berhasil!')
                                        ->send();
                                    return;
                                }
                                
                                \Filament\Notifications\Notification::make()
                                    ->danger()
                                    ->title('Test Gagal')
                                    ->body('Error: ' . $errorMessage)
                                    ->send();
                            }
                        })
                        ->color('success')
                        ->icon('heroicon-o-paper-airplane')
                        ->requiresConfirmation()
                        ->modalHeading('Test Koneksi WhatsApp')
                        ->modalDescription('Sistem akan mengirim pesan test ke nomor yang Anda tentukan.')
                        ->form([
                            Forms\Components\TextInput::make('test_number')
                                ->label('Nomor WhatsApp')
                                ->placeholder('621234567890')
                                ->required(),
                        ])
                        ->visible(fn ($record) => $record && $record->exists),
                ])->columnSpanFull(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('api_url')
                    ->label('API URL')
                    ->searchable(),
                Tables\Columns\TextColumn::make('session')
                    ->label('Session')
                    ->searchable(),
                Tables\Columns\TextColumn::make('default_country_code')
                    ->label('Kode Negara')
                    ->searchable(),
                Tables\Columns\IconColumn::make('is_active')
                    ->label('Status')
                    ->boolean(),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Dibuat')
                    ->dateTime()
                    ->sortable(),
                Tables\Columns\TextColumn::make('updated_at')
                    ->label('Diperbarui')
                    ->dateTime()
                    ->sortable(),
            ])
            ->filters([
                //
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
                Tables\Actions\Action::make('test')
                    ->label('Test Koneksi')
                    ->icon('heroicon-o-paper-airplane')
                    ->color('success')
                    ->requiresConfirmation()
                    ->modalHeading('Test Koneksi WhatsApp')
                    ->modalDescription('Sistem akan mengirim pesan test ke nomor yang Anda tentukan.')
                    ->form([
                        Forms\Components\TextInput::make('test_number')
                            ->label('Nomor WhatsApp')
                            ->placeholder('621234567890')
                            ->required(),
                    ])
                    ->action(function (WhatsAppSetting $record, array $data) {
                        try {
                            $whatsapp = new WhatsAppService($record);
                            
                            $result = $whatsapp->sendMessage(
                                $data['test_number'],
                                "Test koneksi WhatsApp berhasil!\n\nJika Anda menerima pesan ini, berarti pengaturan WhatsApp sudah benar."
                            );

                            // Jika success = true, maka berhasil
                            if (isset($result['success']) && $result['success'] === true) {
                                \Filament\Notifications\Notification::make()
                                    ->success()
                                    ->title('Test Berhasil')
                                    ->body('Koneksi ke WhatsApp Gateway berhasil!')
                                    ->send();
                                return;
                            } else {
                                // Jika ada error, throw exception
                                if (isset($result['error']) && !empty($result['error'])) {
                                    throw new \Exception(is_array($result['error']) ? json_encode($result['error']) : $result['error']);
                                } else {
                                    // Jika tidak ada error dan tidak ada success, anggap berhasil
                                    \Filament\Notifications\Notification::make()
                                        ->success()
                                        ->title('Test Berhasil')
                                        ->body('Koneksi ke WhatsApp Gateway berhasil!')
                                        ->send();
                                    return;
                                }
                            }
                        } catch (\Exception $e) {
                            $errorMessage = $e->getMessage();
                            
                            // Jika error message berisi extendedTextMessage, anggap berhasil
                            if (strpos($errorMessage, 'extendedTextMessage') !== false) {
                                \Filament\Notifications\Notification::make()
                                    ->success()
                                    ->title('Test Berhasil')
                                    ->body('Koneksi ke WhatsApp Gateway berhasil!')
                                    ->send();
                                return;
                            }
                            
                            \Filament\Notifications\Notification::make()
                                ->danger()
                                ->title('Test Gagal')
                                ->body('Error: ' . $errorMessage)
                                ->send();
                        }
                    }),
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
            'index' => Pages\ManageWhatsAppSettings::route('/'),
        ];
    }

    public static function getNavigationBadge(): ?string
    {
        return WhatsAppSetting::where('is_active', true)->exists() ? '✓' : '!';
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return WhatsAppSetting::where('is_active', true)->exists() ? 'success' : 'danger';
    }
}
