<?php

namespace App\Filament\Pages;

use App\Models\Setting;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\View;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Illuminate\Support\Facades\Storage;

class InvoiceSettings extends Page implements HasForms
{
    use InteractsWithForms;

    protected static ?string $navigationIcon = 'heroicon-o-document-text';

    protected static ?string $navigationLabel = 'Pengaturan Invoice';

    protected static ?string $title = 'Pengaturan Invoice';

    protected static ?string $navigationGroup = 'Sistem';

    protected static string $view = 'filament.pages.invoice-settings';

    public ?array $data = [];

    public function mount(): void
    {
        $this->form->fill([
            'invoice_logo' => Setting::get('invoice_logo'),
            'invoice_footer' => Setting::get('invoice_footer', 'Terima kasih atas pembayaran Anda.'),
            'invoice_notes' => Setting::get('invoice_notes', 'Catatan: Pembayaran harus dilakukan sebelum tanggal jatuh tempo.'),
        ]);
        
        $this->registerListeners();
    }
    
    protected function registerListeners(): void
    {
        $this->listeners = [
            'logo-uploaded' => 'handleLogoUploaded',
            'logo-removed' => 'handleLogoRemoved',
        ];
    }
    
    public function handleLogoUploaded($data): void
    {
        $this->data['invoice_logo'] = $data['path'];
    }
    
    public function handleLogoRemoved(): void
    {
        $this->data['invoice_logo'] = null;
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Section::make('Logo Invoice')
                    ->description('Upload logo untuk ditampilkan pada invoice')
                    ->schema([
                        View::make('components.logo-drag-drop')
                            ->label('Logo')
                            ->columnSpan(2),
                        FileUpload::make('invoice_logo')
                            ->label('Logo (Alternatif)')
                            ->helperText('Anda juga dapat menggunakan uploader standar ini jika drag-drop tidak berfungsi.')
                            ->image()
                            ->imageEditor()
                            ->disk('public')
                            ->directory('logos')
                            ->visibility('public')
                            ->imagePreviewHeight('150')
                            ->maxSize(1024)
                            ->hidden(fn () => true) // Sembunyikan uploader standar, tapi tetap berfungsi
                    ])->columnSpan(2),

                Section::make('Teks Invoice')
                    ->description('Kustomisasi teks yang ditampilkan pada invoice')
                    ->schema([
                        RichEditor::make('invoice_footer')
                            ->label('Footer Invoice')
                            ->helperText('Teks yang akan ditampilkan di bagian bawah invoice')
                            ->columnSpan(2),

                        RichEditor::make('invoice_notes')
                            ->label('Catatan Invoice')
                            ->helperText('Catatan tambahan yang akan ditampilkan pada invoice')
                            ->columnSpan(2),
                    ])->columns(2),
            ])
            ->statePath('data');
    }

    public function save(): void
    {
        $data = $this->form->getState();

        foreach ($data as $key => $value) {
            Setting::set($key, $value);
        }

        Notification::make()
            ->title('Pengaturan invoice berhasil disimpan')
            ->success()
            ->send();
    }
}