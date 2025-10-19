<?php

namespace App\Filament\Pages;

use App\Models\Setting;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\View;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Artisan;

class GeneralSettings extends Page implements HasForms
{
    use InteractsWithForms;

    protected static ?string $navigationIcon = 'heroicon-o-cog-6-tooth';

    protected static ?string $navigationLabel = 'Pengaturan Aplikasi';

    protected static ?string $title = 'Pengaturan Aplikasi';
    
    protected static bool $shouldRegisterNavigation = false;
    
    protected static ?int $navigationSort = 1;

    protected static string $view = 'filament.pages.general-settings';

    public ?array $data = [];

    public function mount(): void
    {
        $this->form->fill([
            'app_name' => Setting::get('app_name', config('app.name')),
            'app_logo' => Setting::get('app_logo'),
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
        $this->data['app_logo'] = $data['path'];
    }
    
    public function handleLogoRemoved(): void
    {
        $this->data['app_logo'] = null;
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Section::make('Pengaturan Umum')
                    ->description('Pengaturan dasar aplikasi')
                    ->schema([
                        TextInput::make('app_name')
                            ->label('Nama Aplikasi')
                            ->required()
                            ->maxLength(255)
                            ->helperText('Nama aplikasi yang akan ditampilkan di judul halaman dan tempat lainnya'),
                    ])->columnSpan(2),

                Section::make('Logo Aplikasi')
                    ->description('Upload logo untuk ditampilkan pada aplikasi')
                    ->schema([
                        View::make('components.logo-drag-drop')
                            ->label('Logo')
                            ->columnSpan(2),
                        FileUpload::make('app_logo')
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
            ])
            ->statePath('data');
    }

    public function save(): void
    {
        $data = $this->form->getState();

        foreach ($data as $key => $value) {
            Setting::set($key, $value);
        }

        // Clear config cache to apply changes immediately
        Artisan::call('config:clear');

        Notification::make()
            ->title('Pengaturan aplikasi berhasil disimpan')
            ->success()
            ->send();
    }
}