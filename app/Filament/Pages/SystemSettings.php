<?php

namespace App\Filament\Pages;

use App\Models\Setting;
use App\Models\WhatsAppTemplate;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Pages\Page;
use Filament\Support\Exceptions\Halt;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class SystemSettings extends Page implements HasForms
{
    use InteractsWithForms;
    protected static ?string $navigationIcon = 'heroicon-o-cog-6-tooth';
    protected static ?string $navigationLabel = 'Pengaturan Sistem';
    protected static ?string $title = 'Pengaturan Sistem';
    protected static ?string $navigationGroup = 'Pengaturan';
    protected static ?int $navigationSort = 1;

    protected static string $view = 'filament.pages.system-settings';

    public ?array $data = [];

    public function mount(): void
    {
        $this->form->fill([
            // WhatsApp Templates
            'template_billing_new' => Setting::get('whatsapp_template_billing_new'),
            'template_billing_paid' => Setting::get('whatsapp_template_billing_paid'),
            'template_service_suspended' => Setting::get('whatsapp_template_service_suspended'),
            'template_service_reactivated' => Setting::get('whatsapp_template_service_reactivated'),
            
            // Invoice Settings
            'company_name' => Setting::get('company_name', config('app.name', 'Internet Provider')),
            'company_address' => Setting::get('company_address', ''),
            'company_phone' => Setting::get('company_phone', ''),
            'company_email' => Setting::get('company_email', ''),
            'bank_name' => Setting::get('bank_name', ''),
            'bank_account' => Setting::get('bank_account', ''),
            'bank_account_name' => Setting::get('bank_account_name', ''),
            'payment_notes' => Setting::get('payment_notes', 'Silakan transfer ke rekening di atas atau hubungi kami untuk metode pembayaran lainnya.'),
            'invoice_footer' => Setting::get('invoice_footer', 'Terima kasih atas kepercayaan Anda menggunakan layanan kami.'),
            'billing_due_day' => Setting::get('billing_due_day', '25'),
            
            // Application Settings
            'app_timezone' => Setting::get('app_timezone', 'Asia/Jakarta'),
            
            // Analytics Widgets
            'header_widgets' => $this->getHeaderWidgetsData(),
            'footer_widgets' => $this->getFooterWidgetsData(),
            
            // AI Settings
            'openrouter_api_key' => Setting::get('openrouter_api_key', env('OPENROUTER_API_KEY', '')),
            'openrouter_model' => Setting::get('openrouter_model', env('OPENROUTER_MODEL', 'meta-llama/llama-3.2-3b-instruct')),
        ]);
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Tabs::make('Settings')
                    ->tabs([
                        // Tab 0: Pengaturan Aplikasi
                        Forms\Components\Tabs\Tab::make('Pengaturan Aplikasi')
                            ->icon('heroicon-o-cog-6-tooth')
                            ->schema([
                                Forms\Components\Section::make('Zona Waktu')
                                    ->description('Atur zona waktu yang digunakan untuk menampilkan tanggal dan waktu di seluruh aplikasi')
                                    ->schema([
                                        Forms\Components\Placeholder::make('timezone_info')
                                            ->label('')
                                            ->content(new \Illuminate\Support\HtmlString('
                                                <div class="rounded-lg bg-blue-50 dark:bg-blue-950 p-4 border border-blue-200 dark:border-blue-800">
                                                    <div class="flex items-start gap-3">
                                                        <div class="flex-shrink-0">
                                                            <svg class="h-6 w-6 text-blue-500" fill="currentColor" viewBox="0 0 20 20">
                                                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm1-12a1 1 0 10-2 0v4a1 1 0 00.293.707l2.828 2.829a1 1 0 101.415-1.415L11 9.586V6z" clip-rule="evenodd"/>
                                                            </svg>
                                                        </div>
                                                        <div class="flex-1">
                                                            <p class="text-sm text-blue-800 dark:text-blue-200 font-semibold mb-2">
                                                                🌏 Zona Waktu Aplikasi
                                                            </p>
                                                            <p class="text-sm text-blue-700 dark:text-blue-300 mb-2">
                                                                Zona waktu yang dipilih akan diterapkan ke <strong>semua tampilan tanggal dan waktu</strong> di aplikasi, termasuk:
                                                            </p>
                                                            <ul class="text-xs text-blue-600 dark:text-blue-400 list-disc list-inside space-y-1">
                                                                <li>Dashboard dan widget analytics</li>
                                                                <li>Tanggal tagihan dan jatuh tempo</li>
                                                                <li>Riwayat pembayaran customer</li>
                                                                <li>Waktu pengiriman WhatsApp</li>
                                                                <li>Invoice PDF dan dokumen lainnya</li>
                                                                <li>Activity logs dan laporan</li>
                                                            </ul>
                                                            <p class="text-xs text-blue-600 dark:text-blue-400 mt-2">
                                                                💡 <strong>Tip:</strong> Pilih zona waktu sesuai lokasi mayoritas customer Anda agar reminder dan notifikasi terkirim di waktu yang tepat.
                                                            </p>
                                                        </div>
                                                    </div>
                                                </div>
                                            '))
                                            ->columnSpanFull(),
                                        
                                        Forms\Components\Select::make('app_timezone')
                                            ->label('Zona Waktu')
                                            ->required()
                                            ->options([
                                                'Asia/Jakarta' => '🇮🇩 WIB - Jakarta, Sumatera (GMT+7)',
                                                'Asia/Makassar' => '🇮🇩 WITA - Makassar, Bali, Kalimantan (GMT+8)',
                                                'Asia/Jayapura' => '🇮🇩 WIT - Jayapura, Papua, Maluku (GMT+9)',
                                                'Asia/Singapore' => '🇸🇬 Singapore (GMT+8)',
                                                'Asia/Kuala_Lumpur' => '🇲🇾 Kuala Lumpur (GMT+8)',
                                                'Asia/Bangkok' => '🇹🇭 Bangkok (GMT+7)',
                                                'Asia/Manila' => '🇵🇭 Manila (GMT+8)',
                                                'Asia/Tokyo' => '🇯🇵 Tokyo (GMT+9)',
                                                'Asia/Seoul' => '🇰🇷 Seoul (GMT+9)',
                                                'Asia/Hong_Kong' => '🇭🇰 Hong Kong (GMT+8)',
                                                'Asia/Taipei' => '🇹🇼 Taipei (GMT+8)',
                                                'UTC' => '🌍 UTC - Coordinated Universal Time (GMT+0)',
                                            ])
                                            ->default('Asia/Jakarta')
                                            ->searchable()
                                            ->helperText('Zona waktu yang digunakan untuk menampilkan tanggal dan waktu di seluruh aplikasi.')
                                            ->live()
                                            ->afterStateUpdated(function ($state) {
                                                // Preview current time in selected timezone
                                                $currentTime = now()->timezone($state)->format('d F Y H:i:s');
                                                $timezoneName = match($state) {
                                                    'Asia/Jakarta' => 'WIB (Waktu Indonesia Barat)',
                                                    'Asia/Makassar' => 'WITA (Waktu Indonesia Tengah)',
                                                    'Asia/Jayapura' => 'WIT (Waktu Indonesia Timur)',
                                                    default => $state,
                                                };
                                                
                                                \Filament\Notifications\Notification::make()
                                                    ->title('⏰ Preview Waktu')
                                                    ->body("Waktu saat ini di {$timezoneName}:\n{$currentTime}")
                                                    ->info()
                                                    ->duration(5000)
                                                    ->send();
                                            }),
                                    ])
                                    ->collapsible()
                                    ->collapsed(false),
                                    
                                Forms\Components\Section::make('Informasi Zona Waktu Indonesia')
                                    ->description('Referensi zona waktu di Indonesia')
                                    ->schema([
                                        Forms\Components\Placeholder::make('wib_info')
                                            ->label('WIB (Waktu Indonesia Barat) - GMT+7')
                                            ->content('Mencakup: Jakarta, Sumatera, Kalimantan Barat & Tengah'),
                                        
                                        Forms\Components\Placeholder::make('wita_info')
                                            ->label('WITA (Waktu Indonesia Tengah) - GMT+8')
                                            ->content('Mencakup: Bali, NTB, NTT, Kalimantan Selatan & Timur, Sulawesi'),
                                        
                                        Forms\Components\Placeholder::make('wit_info')
                                            ->label('WIT (Waktu Indonesia Timur) - GMT+9')
                                            ->content('Mencakup: Maluku, Papua'),
                                    ])
                                    ->columns(1)
                                    ->collapsible()
                                    ->collapsed(true),
                                    
                                Forms\Components\Section::make('Backup & Restore')
                                    ->description('Backup database dan data aplikasi Anda secara berkala untuk keamanan data')
                                    ->schema([
                                        Forms\Components\Placeholder::make('backup_info')
                                            ->label('')
                                            ->content(new \Illuminate\Support\HtmlString('
                                                <div class="rounded-lg bg-amber-50 dark:bg-amber-950 p-4 border border-amber-200 dark:border-amber-800">
                                                    <div class="flex items-start gap-3">
                                                        <div class="flex-shrink-0">
                                                            <svg class="h-6 w-6 text-amber-500" fill="currentColor" viewBox="0 0 20 20">
                                                                <path d="M7 9a2 2 0 012-2h6a2 2 0 012 2v6a2 2 0 01-2 2H9a2 2 0 01-2-2V9z"/>
                                                                <path d="M5 3a2 2 0 00-2 2v6a2 2 0 002 2V5h8a2 2 0 00-2-2H5z"/>
                                                            </svg>
                                                        </div>
                                                        <div class="flex-1">
                                                            <p class="text-sm text-amber-800 dark:text-amber-200 font-semibold mb-2">
                                                                💾 Backup Database
                                                            </p>
                                                            <p class="text-sm text-amber-700 dark:text-amber-300 mb-2">
                                                                Fitur backup akan membuat salinan lengkap database Anda termasuk:
                                                            </p>
                                                            <ul class="text-xs text-amber-600 dark:text-amber-400 list-disc list-inside space-y-1">
                                                                <li>Data customer dan paket internet</li>
                                                                <li>Riwayat tagihan dan pembayaran</li>
                                                                <li>Template dan pengaturan WhatsApp</li>
                                                                <li>Log aktivitas dan pesan</li>
                                                                <li>Semua pengaturan sistem</li>
                                                            </ul>
                                                            <div class="mt-3 p-2 bg-amber-100 dark:bg-amber-900 rounded border border-amber-300 dark:border-amber-700">
                                                                <p class="text-xs text-amber-800 dark:text-amber-200">
                                                                    ⚠️ <strong>Penting:</strong> Lakukan backup secara berkala (minimal 1x seminggu) dan simpan di tempat yang aman.
                                                                </p>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                            '))
                                            ->columnSpanFull(),
                                        
                                        Forms\Components\Grid::make(2)
                                            ->schema([
                                                Forms\Components\Placeholder::make('last_backup')
                                                    ->label('Backup Terakhir')
                                                    ->content(function () {
                                                        $lastBackup = Setting::get('last_backup_date');
                                                        if ($lastBackup) {
                                                            $date = \Carbon\Carbon::parse($lastBackup);
                                                            $diff = $date->diffForHumans();
                                                            return new \Illuminate\Support\HtmlString(
                                                                "<div class='text-sm'>
                                                                    <span class='font-semibold'>{$date->format('d F Y H:i:s')}</span>
                                                                    <br><span class='text-gray-500'>({$diff})</span>
                                                                </div>"
                                                            );
                                                        }
                                                        return new \Illuminate\Support\HtmlString(
                                                            "<span class='text-gray-500 italic'>Belum ada backup</span>"
                                                        );
                                                    }),
                                                
                                                Forms\Components\Placeholder::make('backup_size')
                                                    ->label('Ukuran Database')
                                                    ->content(function () {
                                                        try {
                                                            $dbName = config('database.connections.mysql.database');
                                                            $size = DB::select("
                                                                SELECT 
                                                                    ROUND(SUM(data_length + index_length) / 1024 / 1024, 2) AS size_mb
                                                                FROM information_schema.TABLES 
                                                                WHERE table_schema = ?
                                                            ", [$dbName]);
                                                            
                                                            if ($size && isset($size[0]->size_mb)) {
                                                                $sizeMB = $size[0]->size_mb;
                                                                return new \Illuminate\Support\HtmlString(
                                                                    "<span class='font-semibold text-sm'>{$sizeMB} MB</span>"
                                                                );
                                                            }
                                                        } catch (\Exception $e) {
                                                            // Silent fail
                                                        }
                                                        return new \Illuminate\Support\HtmlString(
                                                            "<span class='text-gray-500 italic'>-</span>"
                                                        );
                                                    }),
                                            ]),
                                        
                                        Forms\Components\Actions::make([
                                            Forms\Components\Actions\Action::make('backup_database')
                                                ->label('Backup Database Sekarang')
                                                ->icon('heroicon-o-arrow-down-tray')
                                                ->color('success')
                                                ->requiresConfirmation()
                                                ->modalHeading('Backup Database')
                                                ->modalDescription('Apakah Anda yakin ingin membuat backup database sekarang? File SQL akan otomatis terdownload.')
                                                ->modalSubmitActionLabel('Ya, Backup Sekarang')
                                                ->action(function () {
                                                    return $this->backupDatabase();
                                                }),
                                        ])
                                        ->columnSpanFull(),
                                    ])
                                    ->collapsible()
                                    ->collapsed(false),
                            ]),
                        
                        // Tab 1: Template WhatsApp
                        Forms\Components\Tabs\Tab::make('Template WhatsApp')
                            ->icon('heroicon-o-chat-bubble-left-right')
                            ->schema([
                                Forms\Components\Section::make('ℹ️ Penting: Pengingat Tagihan')
                                    ->description('Pengingat tagihan (reminder) sekarang diatur di menu "Pengaturan Reminder" yang lebih fleksibel')
                                    ->schema([
                                        Forms\Components\Placeholder::make('reminder_info')
                                            ->label('')
                                            ->content(new \Illuminate\Support\HtmlString('
                                                <div class="rounded-lg bg-blue-50 dark:bg-blue-950 p-4 border border-blue-200 dark:border-blue-800">
                                                    <div class="flex items-start gap-3">
                                                        <div class="flex-shrink-0">
                                                            <svg class="h-6 w-6 text-blue-500" fill="currentColor" viewBox="0 0 20 20">
                                                                <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd"/>
                                                            </svg>
                                                        </div>
                                                        <div class="flex-1">
                                                            <p class="text-sm text-blue-800 dark:text-blue-200 font-semibold mb-2">
                                                                💡 Untuk mengatur reminder tagihan (H-7, H-3, H-1, Jatuh Tempo, Overdue, dll):
                                                            </p>
                                                            <p class="text-sm text-blue-700 dark:text-blue-300 mb-2">
                                                                Silakan gunakan menu <strong class="font-bold">"WhatsApp → Pengaturan Reminder"</strong> yang lebih powerful dan fleksibel.
                                                            </p>
                                                            <p class="text-xs text-blue-600 dark:text-blue-400">
                                                                Di sana Anda bisa membuat reminder custom dengan tanggal yang Anda inginkan dan memilih template untuk setiap reminder.
                                                            </p>
                                                        </div>
                                                    </div>
                                                </div>
                                            '))
                                            ->columnSpanFull(),
                                    ])
                                    ->collapsible(),

                                Forms\Components\Section::make('Template untuk Tagihan Baru')
                                    ->description('Template default untuk tagihan bulanan baru')
                                    ->schema([
                                        Forms\Components\Select::make('template_billing_new')
                                            ->label('Template Tagihan Baru')
                                            ->options(function () {
                                                return WhatsAppTemplate::where('template_type', WhatsAppTemplate::TYPE_BILLING_NEW)
                                                    ->where('is_active', true)
                                                    ->orderBy('order', 'asc')
                                                    ->pluck('name', 'id')
                                                    ->toArray();
                                            })
                                            ->searchable()
                                            ->native(false)
                                            ->placeholder('Pilih template untuk tagihan baru')
                                            ->helperText('Template yang akan digunakan saat generate tagihan bulanan baru (dengan PDF invoice)'),
                                    ]),

                                Forms\Components\Section::make('Template untuk Pembayaran')
                                    ->description('Pilih template untuk notifikasi terkait pembayaran')
                                    ->schema([
                                        Forms\Components\Select::make('template_billing_paid')
                                            ->label('Konfirmasi Pembayaran')
                                            ->options(function () {
                                                return WhatsAppTemplate::where('template_type', WhatsAppTemplate::TYPE_BILLING_PAID)
                                                    ->where('is_active', true)
                                                    ->orderBy('order', 'asc')
                                                    ->pluck('name', 'id')
                                                    ->toArray();
                                            })
                                            ->searchable()
                                            ->native(false)
                                            ->placeholder('Pilih template untuk konfirmasi pembayaran')
                                            ->helperText('Template yang akan digunakan saat pembayaran berhasil diterima'),
                                    ]),

                                Forms\Components\Section::make('Template untuk Layanan')
                                    ->description('Pilih template untuk notifikasi terkait status layanan')
                                    ->schema([
                                        Forms\Components\Select::make('template_service_suspended')
                                            ->label('Penangguhan Layanan')
                                            ->options(function () {
                                                return WhatsAppTemplate::where('template_type', WhatsAppTemplate::TYPE_SERVICE_SUSPENDED)
                                                    ->where('is_active', true)
                                                    ->orderBy('order', 'asc')
                                                    ->pluck('name', 'id')
                                                    ->toArray();
                                            })
                                            ->searchable()
                                            ->native(false)
                                            ->placeholder('Pilih template untuk penangguhan layanan')
                                            ->helperText('Template yang akan digunakan saat layanan ditangguhkan'),

                                        Forms\Components\Select::make('template_service_reactivated')
                                            ->label('Pengaktifan Kembali Layanan')
                                            ->options(function () {
                                                return WhatsAppTemplate::where('template_type', WhatsAppTemplate::TYPE_SERVICE_REACTIVATED)
                                                    ->where('is_active', true)
                                                    ->orderBy('order', 'asc')
                                                    ->pluck('name', 'id')
                                                    ->toArray();
                                            })
                                            ->searchable()
                                            ->native(false)
                                            ->placeholder('Pilih template untuk pengaktifan kembali')
                                            ->helperText('Template yang akan digunakan saat layanan diaktifkan kembali'),
                                    ]),
                            ]),

                        // Tab 2: Invoice Settings
                        Forms\Components\Tabs\Tab::make('Pengaturan Invoice')
                            ->icon('heroicon-o-document-text')
                            ->schema([
                                Forms\Components\Section::make('Informasi Perusahaan')
                                    ->description('Informasi ini akan ditampilkan di bagian header invoice')
                                    ->schema([
                                        Forms\Components\TextInput::make('company_name')
                                            ->label('Nama Perusahaan')
                                            ->required()
                                            ->maxLength(255)
                                            ->placeholder('PT. Internet Provider Indonesia')
                                            ->helperText('Nama perusahaan yang akan ditampilkan di invoice'),

                                        Forms\Components\Textarea::make('company_address')
                                            ->label('Alamat Perusahaan')
                                            ->required()
                                            ->rows(3)
                                            ->placeholder('Jl. Contoh No. 123, Kota, Provinsi 12345')
                                            ->helperText('Alamat lengkap perusahaan'),

                                        Forms\Components\Grid::make(2)
                                            ->schema([
                                                Forms\Components\TextInput::make('company_phone')
                                                    ->label('Nomor Telepon')
                                                    ->required()
                                                    ->tel()
                                                    ->placeholder('021-12345678 atau 0812-3456-7890')
                                                    ->helperText('Nomor telepon yang bisa dihubungi'),

                                                Forms\Components\TextInput::make('company_email')
                                                    ->label('Email Perusahaan')
                                                    ->required()
                                                    ->email()
                                                    ->placeholder('info@company.com')
                                                    ->helperText('Email untuk korespondensi'),
                                            ]),
                                    ])
                                    ->collapsible(),

                                Forms\Components\Section::make('Informasi Pembayaran')
                                    ->description('Informasi rekening bank yang akan ditampilkan di invoice untuk pembayaran')
                                    ->schema([
                                        Forms\Components\Grid::make(2)
                                            ->schema([
                                                Forms\Components\TextInput::make('bank_name')
                                                    ->label('Nama Bank')
                                                    ->required()
                                                    ->placeholder('Bank BCA / Bank Mandiri / Bank BRI')
                                                    ->helperText('Nama bank untuk transfer'),

                                                Forms\Components\TextInput::make('bank_account')
                                                    ->label('Nomor Rekening')
                                                    ->required()
                                                    ->placeholder('1234567890')
                                                    ->helperText('Nomor rekening bank'),
                                            ]),

                                        Forms\Components\TextInput::make('bank_account_name')
                                            ->label('Nama Pemilik Rekening')
                                            ->required()
                                            ->placeholder('PT. Internet Provider Indonesia')
                                            ->helperText('Atas nama rekening bank'),

                                        Forms\Components\Textarea::make('payment_notes')
                                            ->label('Catatan Pembayaran')
                                            ->rows(3)
                                            ->placeholder('Silakan transfer ke rekening di atas...')
                                            ->helperText('Catatan tambahan mengenai pembayaran (opsional)'),
                                    ])
                                    ->collapsible(),

                                Forms\Components\Section::make('Pengaturan Tagihan')
                                    ->description('Pengaturan tanggal jatuh tempo untuk tagihan bulanan')
                                    ->schema([
                                        Forms\Components\Select::make('billing_due_day')
                                            ->label('Tanggal Jatuh Tempo Default')
                                            ->required()
                                            ->options(array_combine(range(1, 31), range(1, 31)))
                                            ->default('25')
                                            ->helperText('Tanggal jatuh tempo default untuk tagihan bulanan (1-31). Jika tanggal tidak ada dalam bulan tertentu, akan menggunakan tanggal terakhir bulan tersebut.')
                                            ->searchable(),
                                    ])
                                    ->collapsible(),

                                Forms\Components\Section::make('Footer Invoice')
                                    ->description('Pesan yang ditampilkan di bagian bawah invoice')
                                    ->schema([
                                        Forms\Components\Textarea::make('invoice_footer')
                                            ->label('Pesan Footer')
                                            ->rows(2)
                                            ->placeholder('Terima kasih atas kepercayaan Anda...')
                                            ->helperText('Pesan ucapan terima kasih atau informasi tambahan'),
                                    ])
                                    ->collapsible(),
                            ]),

                        // Tab 3: AI Settings
                        Forms\Components\Tabs\Tab::make('AI Assistant')
                            ->icon('heroicon-o-sparkles')
                            ->schema([
                                Forms\Components\Section::make('OpenRouter API')
                                    ->description('Konfigurasi API Key dan Model AI untuk AI Assistant (Chat Widget)')
                                    ->schema([
                                        Forms\Components\Placeholder::make('openrouter_info')
                                            ->label('')
                                            ->content(new \Illuminate\Support\HtmlString('
                                                <div class="rounded-lg bg-blue-50 dark:bg-blue-950 p-4 border border-blue-200 dark:border-blue-800">
                                                    <div class="flex items-start gap-3">
                                                        <div class="flex-shrink-0">
                                                            <svg class="h-6 w-6 text-blue-500" fill="currentColor" viewBox="0 0 20 20">
                                                                <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/>
                                                            </svg>
                                                        </div>
                                                        <div class="flex-1">
                                                            <p class="text-sm text-blue-800 dark:text-blue-200 font-semibold mb-2">
                                                                🤖 AI Assistant dengan OpenRouter
                                                            </p>
                                                            <p class="text-sm text-blue-700 dark:text-blue-300 mb-2">
                                                                Chat widget menggunakan OpenRouter untuk mengakses berbagai model AI gratis. Pilih model yang sesuai dengan kebutuhan Anda.
                                                            </p>
                                                            <ul class="text-xs text-blue-600 dark:text-blue-400 list-disc list-inside space-y-1 mb-2">
                                                                <li>Query data KAS, Hutang, Piutang, Tagihan, dan Customer</li>
                                                                <li>Filter berdasarkan waktu dan status</li>
                                                                <li>Response cepat dan akurat</li>
                                                                <li>Banyak pilihan model AI gratis</li>
                                                            </ul>
                                                            <div class="mt-3 p-2 bg-blue-100 dark:bg-blue-900 rounded border border-blue-300 dark:border-blue-700">
                                                                <p class="text-xs text-blue-800 dark:text-blue-200">
                                                                    💡 <strong>Cara mendapatkan API Key:</strong> Kunjungi <a href="https://openrouter.ai/keys" target="_blank" class="underline font-semibold">OpenRouter</a> dan buat API key baru (gratis). Semua model yang tersedia di sini adalah model gratis.
                                                                </p>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                            '))
                                            ->columnSpanFull(),
                                        
                                        Forms\Components\Grid::make(2)
                                            ->schema([
                                                Forms\Components\TextInput::make('openrouter_api_key')
                                                    ->label('OpenRouter API Key')
                                                    ->password()
                                                    ->revealable()
                                                    ->placeholder('Masukkan API Key OpenRouter Anda')
                                                    ->helperText('API Key akan disimpan dengan aman di database. Jika kosong, sistem akan menggunakan OPENROUTER_API_KEY dari file .env')
                                                    ->maxLength(255)
                                                    ->required(),
                                                
                                        Forms\Components\Select::make('openrouter_model')
                                            ->label('Model AI (Gratis)')
                                            ->options(function (Forms\Get $get) {
                                                $apiKey = $get('openrouter_api_key');
                                                // Try to get models from API if API key is provided
                                                if (!empty($apiKey)) {
                                                    $apiModels = \App\Services\AIService::getAvailableModelsFromAPI($apiKey);
                                                    if (!empty($apiModels)) {
                                                        return $apiModels;
                                                    }
                                                }
                                                // Fallback to static list
                                                return \App\Services\AIService::getFreeModels();
                                            })
                                            ->default('meta-llama/llama-3.2-3b-instruct')
                                            ->searchable()
                                            ->helperText('Pilih model AI yang ingin digunakan. Semua model ini gratis. Rekomendasi: Meta Llama 3.2 3B (Paling stabil, jarang rate-limited).')
                                            ->required()
                                            ->live(),
                                            ]),
                                        
                                        Forms\Components\Actions::make([
                                            Forms\Components\Actions\Action::make('test_connection')
                                                ->label('Test Koneksi')
                                                ->icon('heroicon-o-arrow-path')
                                                ->color('success')
                                                ->action(function (Forms\Get $get) {
                                                    $apiKey = $get('openrouter_api_key');
                                                    $model = $get('openrouter_model') ?: 'meta-llama/llama-3.2-3b-instruct';
                                                    
                                                    if (empty($apiKey)) {
                                                        Notification::make()
                                                            ->title('API Key Kosong')
                                                            ->body('Silakan masukkan API Key terlebih dahulu')
                                                            ->warning()
                                                            ->send();
                                                        return;
                                                    }
                                                    
                                                    try {
                                                        // Test connection dengan OpenRouter API
                                                        $response = \Illuminate\Support\Facades\Http::timeout(15)
                                                            ->withHeaders([
                                                                'Authorization' => 'Bearer ' . $apiKey,
                                                                'HTTP-Referer' => config('app.url', 'https://apps.fastbiz.my.id'),
                                                                'X-Title' => config('app.name', 'FastBiz'),
                                                                'Content-Type' => 'application/json',
                                                            ])
                                                            ->post('https://openrouter.ai/api/v1/chat/completions', [
                                                                'model' => $model,
                                                                'messages' => [
                                                                    [
                                                                        'role' => 'user',
                                                                        'content' => 'Hello',
                                                                    ],
                                                                ],
                                                                'max_tokens' => 10,
                                                            ]);
                                                        
                                                        if ($response->successful()) {
                                                            $data = $response->json();
                                                            $message = $data['choices'][0]['message']['content'] ?? 'Response received';
                                                            
                                                            Notification::make()
                                                                ->title('Koneksi Berhasil')
                                                                ->body('API Key OpenRouter valid dan model ' . $model . ' terhubung dengan baik!')
                                                                ->success()
                                                                ->send();
                                                        } else {
                                                            $statusCode = $response->status();
                                                            $errorBody = $response->body();
                                                            $errorJson = $response->json();
                                                            
                                                            $errorMessage = 'Unknown error';
                                                            $helpText = '';
                                                            
                                                            // Handle specific error codes
                                                            if ($statusCode == 429) {
                                                                $errorMessage = 'Rate limit exceeded atau quota habis';
                                                                $helpText = 'Coba lagi dalam beberapa saat, atau coba model lain.';
                                                            } elseif ($statusCode == 401) {
                                                                $errorMessage = 'API Key tidak valid atau tidak memiliki akses';
                                                                $helpText = 'Pastikan API Key dari OpenRouter benar dan memiliki akses ke model gratis.';
                                                            } elseif ($statusCode == 400) {
                                                                $errorMessage = 'Request tidak valid';
                                                                $helpText = 'Model mungkin tidak tersedia atau format request salah.';
                                                            } elseif ($statusCode == 404) {
                                                                $errorMessage = 'Model tidak ditemukan';
                                                                $helpText = 'Model yang dipilih mungkin tidak tersedia. Coba pilih model lain.';
                                                            }
                                                            
                                                            if (isset($errorJson['error']['message'])) {
                                                                $errorMessage = $errorJson['error']['message'];
                                                            } elseif (isset($errorJson['error'])) {
                                                                $errorMessage = is_string($errorJson['error']) ? $errorJson['error'] : json_encode($errorJson['error']);
                                                            } elseif (!empty($errorBody)) {
                                                                $errorMessage = $errorBody;
                                                            }
                                                            
                                                            // Log error for debugging
                                                            \Illuminate\Support\Facades\Log::error('OpenRouter test connection failed', [
                                                                'status_code' => $statusCode,
                                                                'error' => $errorJson,
                                                                'model' => $model,
                                                                'api_key_prefix' => substr($apiKey, 0, 10) . '...',
                                                            ]);
                                                            
                                                            $fullMessage = 'HTTP ' . $statusCode . ': ' . $errorMessage;
                                                            if ($helpText) {
                                                                $fullMessage .= "\n\n" . $helpText;
                                                            }
                                                            
                                                            Notification::make()
                                                                ->title('Koneksi Gagal')
                                                                ->body($fullMessage)
                                                                ->danger()
                                                                ->persistent()
                                                                ->send();
                                                        }
                                                    } catch (\Illuminate\Http\Client\ConnectionException $e) {
                                                        Notification::make()
                                                            ->title('Error Koneksi')
                                                            ->body('Tidak dapat terhubung ke OpenRouter API. Periksa koneksi internet Anda.')
                                                            ->danger()
                                                            ->persistent()
                                                            ->send();
                                                    } catch (\Exception $e) {
                                                        \Illuminate\Support\Facades\Log::error('OpenRouter test connection exception', [
                                                            'error' => $e->getMessage(),
                                                            'trace' => $e->getTraceAsString(),
                                                        ]);
                                                        
                                                        Notification::make()
                                                            ->title('Error')
                                                            ->body('Terjadi kesalahan: ' . $e->getMessage())
                                                            ->danger()
                                                            ->persistent()
                                                            ->send();
                                                    }
                                                }),
                                        ])
                                        ->columnSpanFull(),
                                    ])
                                    ->collapsible()
                                    ->collapsed(false),
                            ]),

                        // Tab 4: Analytics Widgets
                        Forms\Components\Tabs\Tab::make('Widget Analisis')
                            ->icon('heroicon-o-chart-bar')
                            ->schema([
                                Forms\Components\Section::make('Widget Header')
                                    ->description('Widget yang ditampilkan di bagian atas halaman analisis')
                                    ->schema([
                                        Forms\Components\Repeater::make('header_widgets')
                                            ->schema([
                                                Forms\Components\Select::make('widget')
                                                    ->label('Pilih Widget')
                                                    ->options($this->getAvailableWidgets())
                                                    ->required()
                                                    ->searchable(),
                                                Forms\Components\TextInput::make('title')
                                                    ->label('Judul Kustom')
                                                    ->placeholder('Biarkan kosong untuk menggunakan judul default'),
                                                Forms\Components\TextInput::make('order')
                                                    ->label('Urutan')
                                                    ->numeric()
                                                    ->default(1)
                                                    ->required(),
                                                Forms\Components\Toggle::make('enabled')
                                                    ->label('Aktif')
                                                    ->default(true),
                                            ])
                                            ->columns(4)
                                            ->addActionLabel('Tambah Widget Header')
                                            ->collapsible()
                                            ->itemLabel(fn (array $state): ?string => $this->getAvailableWidgets()[$state['widget']] ?? null),
                                    ])
                                    ->collapsible(),

                                Forms\Components\Section::make('Widget Footer')
                                    ->description('Widget yang ditampilkan di bagian bawah halaman analisis')
                                    ->schema([
                                        Forms\Components\Repeater::make('footer_widgets')
                                            ->schema([
                                                Forms\Components\Select::make('widget')
                                                    ->label('Pilih Widget')
                                                    ->options($this->getAvailableWidgets())
                                                    ->required()
                                                    ->searchable(),
                                                Forms\Components\TextInput::make('title')
                                                    ->label('Judul Kustom')
                                                    ->placeholder('Biarkan kosong untuk menggunakan judul default'),
                                                Forms\Components\TextInput::make('order')
                                                    ->label('Urutan')
                                                    ->numeric()
                                                    ->default(1)
                                                    ->required(),
                                                Forms\Components\Toggle::make('enabled')
                                                    ->label('Aktif')
                                                    ->default(true),
                                            ])
                                            ->columns(4)
                                            ->addActionLabel('Tambah Widget Footer')
                                            ->collapsible()
                                            ->itemLabel(fn (array $state): ?string => $this->getAvailableWidgets()[$state['widget']] ?? null),
                                    ])
                                    ->collapsible(),
                            ]),
                    ])
                    ->columnSpanFull()
                    ->persistTabInQueryString(),
            ])
            ->statePath('data');
    }

    public function save(): void
    {
        try {
            $data = $this->form->getState();

            // Save WhatsApp Templates
            $templateMappings = [
                'whatsapp_template_billing_new' => $data['template_billing_new'] ?? null,
                'whatsapp_template_billing_paid' => $data['template_billing_paid'] ?? null,
                'whatsapp_template_service_suspended' => $data['template_service_suspended'] ?? null,
                'whatsapp_template_service_reactivated' => $data['template_service_reactivated'] ?? null,
            ];

            foreach ($templateMappings as $key => $value) {
                if ($value) {
                    Setting::set($key, $value);
                } else {
                    Setting::where('key', $key)->delete();
                }
            }

            // Save Invoice Settings
            $invoiceSettings = [
                'company_name',
                'company_address',
                'company_phone',
                'company_email',
                'bank_name',
                'bank_account',
                'bank_account_name',
                'payment_notes',
                'invoice_footer',
                'billing_due_day',
            ];

            foreach ($invoiceSettings as $key) {
                if (isset($data[$key])) {
                    Setting::set($key, $data[$key]);
                }
            }

            // Save Application Settings (Timezone)
            if (isset($data['app_timezone'])) {
                Setting::set('app_timezone', $data['app_timezone']);
                // Set timezone immediately for current request
                config(['app.timezone' => $data['app_timezone']]);
                date_default_timezone_set($data['app_timezone']);
                Cache::forget('setting_app_timezone');
            }

            // Save AI Settings (OpenRouter API Key & Model)
            if (isset($data['openrouter_api_key'])) {
                if (!empty($data['openrouter_api_key'])) {
                    Setting::set('openrouter_api_key', $data['openrouter_api_key']);
                    Cache::forget('setting_openrouter_api_key');
                } else {
                    // If empty, delete the setting (will fallback to .env)
                    Setting::where('key', 'openrouter_api_key')->delete();
                    Cache::forget('setting_openrouter_api_key');
                }
            }
            
            if (isset($data['openrouter_model'])) {
                Setting::set('openrouter_model', $data['openrouter_model']);
                Cache::forget('setting_openrouter_model');
            }

            // Save Analytics Widget Settings
            if (isset($data['header_widgets'])) {
                $headerWidgets = [];
                foreach ($data['header_widgets'] as $widget) {
                    $headerWidgets[$widget['widget']] = [
                        'enabled' => $widget['enabled'] ?? true,
                        'order' => $widget['order'] ?? 1,
                        'title' => $widget['title'] ?? null,
                    ];
                }
                Setting::set('analytics_widgets_header', json_encode($headerWidgets));
                Cache::forget('setting_analytics_widgets_header');
            }

            if (isset($data['footer_widgets'])) {
                $footerWidgets = [];
                foreach ($data['footer_widgets'] as $widget) {
                    $footerWidgets[$widget['widget']] = [
                        'enabled' => $widget['enabled'] ?? true,
                        'order' => $widget['order'] ?? 1,
                        'title' => $widget['title'] ?? null,
                    ];
                }
                Setting::set('analytics_widgets_footer', json_encode($footerWidgets));
                Cache::forget('setting_analytics_widgets_footer');
            }

            Notification::make()
                ->success()
                ->title('Pengaturan Berhasil Disimpan')
                ->body('Semua pengaturan sistem telah diperbarui.')
                ->send();

        } catch (Halt $exception) {
            return;
        }
    }

    protected function getFormActions(): array
    {
        return [];
    }
    
    protected function getHeaderActions(): array
    {
        return [
            \Filament\Actions\Action::make('save')
                ->label('Simpan Semua Pengaturan')
                ->action('save')
                ->color('primary')
                ->icon('heroicon-o-check-circle'),
        ];
    }

    // Analytics Widget Helper Methods
    protected function getHeaderWidgetsData(): array
    {
        $widgets = Setting::get('analytics_widgets_header', '[]');
        $config = json_decode($widgets, true) ?: [];
        
        // Konversi struktur data untuk form
        $widgetArray = [];
        foreach ($config as $widgetName => $widgetConfig) {
            $widgetArray[] = [
                'widget' => $widgetName,
                'enabled' => $widgetConfig['enabled'] ?? true,
                'order' => $widgetConfig['order'] ?? 1,
                'title' => $widgetConfig['title'] ?? null,
            ];
        }
        
        return $widgetArray;
    }

    protected function getFooterWidgetsData(): array
    {
        $widgets = Setting::get('analytics_widgets_footer', '[]');
        $config = json_decode($widgets, true) ?: [];
        
        // Konversi struktur data untuk form
        $widgetArray = [];
        foreach ($config as $widgetName => $widgetConfig) {
            $widgetArray[] = [
                'widget' => $widgetName,
                'enabled' => $widgetConfig['enabled'] ?? true,
                'order' => $widgetConfig['order'] ?? 1,
                'title' => $widgetConfig['title'] ?? null,
            ];
        }
        
        return $widgetArray;
    }

    protected function getAvailableWidgets(): array
    {
        $available = Setting::get('analytics_widgets_available', '{}');
        $widgets = json_decode($available, true) ?: [];
        
        $options = [];
        foreach ($widgets as $key => $config) {
            $options[$key] = $config['name'] ?? $key;
        }
        
        return $options;
    }
    
    /**
     * Backup database and download SQL file
     */
    public function backupDatabase()
    {
        try {
            $dbHost = config('database.connections.mysql.host');
            $dbName = config('database.connections.mysql.database');
            $dbUser = config('database.connections.mysql.username');
            $dbPass = config('database.connections.mysql.password');
            
            // Create backup directory if not exists
            $backupDir = storage_path('app/backups');
            if (!file_exists($backupDir)) {
                mkdir($backupDir, 0755, true);
            }
            
            // Generate backup filename
            $timestamp = now()->format('Y-m-d_His');
            $filename = "backup_database_{$dbName}_{$timestamp}.sql";
            $filepath = $backupDir . '/' . $filename;
            
            // Determine mysqldump path based on OS
            $mysqldumpPath = $this->getMysqldumpPath();
            
            if (!$mysqldumpPath) {
                // Fallback: Use PHP to export database
                $this->backupDatabaseWithPHP($filepath);
            } else {
                // Build mysqldump command
                $command = sprintf(
                    '%s --user=%s --password=%s --host=%s %s > %s 2>&1',
                    $mysqldumpPath,
                    escapeshellarg($dbUser),
                    escapeshellarg($dbPass),
                    escapeshellarg($dbHost),
                    escapeshellarg($dbName),
                    escapeshellarg($filepath)
                );
                
                // Execute backup
                exec($command, $output, $returnVar);
                
                // Check if backup was successful
                if ($returnVar !== 0 || !file_exists($filepath) || filesize($filepath) === 0) {
                    // Fallback: Use PHP to export database
                    $this->backupDatabaseWithPHP($filepath);
                }
            }
            
            // Update last backup date
            Setting::set('last_backup_date', now()->toDateTimeString());
            
            // Success notification
            Notification::make()
                ->success()
                ->title('Backup Berhasil!')
                ->body("Database berhasil di-backup: {$filename}")
                ->duration(5000)
                ->send();
            
            // Download the backup file
            return response()->download($filepath, $filename)->deleteFileAfterSend(true);
            
        } catch (\Exception $e) {
            Notification::make()
                ->danger()
                ->title('Backup Gagal')
                ->body('Error: ' . $e->getMessage())
                ->persistent()
                ->send();
            
            Log::error('Database backup failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return null;
        }
    }
    
    /**
     * Get mysqldump path based on OS
     */
    protected function getMysqldumpPath(): ?string
    {
        // Common paths for mysqldump
        $paths = [
            'C:\\xampp\\mysql\\bin\\mysqldump.exe', // XAMPP Windows
            'C:\\laragon\\bin\\mysql\\mysql-8.0.30\\bin\\mysqldump.exe', // Laragon
            '/usr/bin/mysqldump', // Linux
            '/usr/local/bin/mysqldump', // macOS
            'mysqldump', // Global PATH
        ];
        
        foreach ($paths as $path) {
            if (file_exists($path)) {
                return $path;
            }
        }
        
        // Try to find in PATH
        if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
            exec('where mysqldump', $output, $returnVar);
        } else {
            exec('which mysqldump', $output, $returnVar);
        }
        
        if ($returnVar === 0 && !empty($output[0])) {
            return trim($output[0]);
        }
        
        return null;
    }
    
    /**
     * Fallback: Backup database using PHP (slower but works everywhere)
     */
    protected function backupDatabaseWithPHP(string $filepath): void
    {
        $dbName = config('database.connections.mysql.database');
        
        // Get all tables
        $tables = DB::select('SHOW TABLES');
        $tableKey = 'Tables_in_' . $dbName;
        
        $sql = "-- Database Backup\n";
        $sql .= "-- Generated: " . now()->toDateTimeString() . "\n";
        $sql .= "-- Database: {$dbName}\n\n";
        $sql .= "SET FOREIGN_KEY_CHECKS=0;\n\n";
        
        foreach ($tables as $table) {
            $tableName = $table->$tableKey;
            
            // Get CREATE TABLE statement
            $createTable = DB::select("SHOW CREATE TABLE `{$tableName}`");
            $sql .= "DROP TABLE IF EXISTS `{$tableName}`;\n";
            $sql .= $createTable[0]->{'Create Table'} . ";\n\n";
            
            // Get table data
            $rows = DB::table($tableName)->get();
            
            if ($rows->count() > 0) {
                $sql .= "INSERT INTO `{$tableName}` VALUES\n";
                
                $values = [];
                foreach ($rows as $row) {
                    $rowData = [];
                    foreach ((array)$row as $value) {
                        if ($value === null) {
                            $rowData[] = 'NULL';
                        } else {
                            $rowData[] = "'" . addslashes($value) . "'";
                        }
                    }
                    $values[] = '(' . implode(', ', $rowData) . ')';
                }
                
                $sql .= implode(",\n", $values) . ";\n\n";
            }
        }
        
        $sql .= "SET FOREIGN_KEY_CHECKS=1;\n";
        
        file_put_contents($filepath, $sql);
    }
}

