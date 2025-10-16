<?php

namespace App\Filament\Pages;

use App\Models\Setting;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Pages\Page;
use Filament\Support\Exceptions\Halt;
use Filament\Notifications\Notification;

class InvoiceSettings extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-document-text';
    protected static ?string $navigationLabel = 'Pengaturan Invoice';
    protected static ?string $title = 'Pengaturan Invoice';
    protected static ?string $navigationGroup = 'Pengaturan';
    protected static ?int $navigationSort = 10;
    
    // Hide from navigation - sekarang digabung di "Pengaturan Sistem"
    protected static bool $shouldRegisterNavigation = false;

    protected static string $view = 'filament.pages.invoice-settings';

    public ?array $data = [];

    public function mount(): void
    {
        $this->form->fill([
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
        ]);
    }

    public function form(Form $form): Form
    {
        return $form
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
            ])
            ->statePath('data');
    }

    public function save(): void
    {
        try {
            $data = $this->form->getState();

            foreach ($data as $key => $value) {
                Setting::set($key, $value);
            }

            Notification::make()
                ->success()
                ->title('Pengaturan Berhasil Disimpan')
                ->body('Semua pengaturan invoice telah diperbarui.')
                ->send();

        } catch (Halt $exception) {
            return;
        }
    }

    protected function getFormActions(): array
    {
        return [
            Forms\Components\Actions\Action::make('save')
                ->label('Simpan Pengaturan')
                ->submit('save')
                ->color('primary'),
        ];
    }
}
