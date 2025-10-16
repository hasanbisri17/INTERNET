<?php

namespace App\Filament\Pages;

use App\Models\Setting;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Pages\Page;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\Cache;

class AnalyticsWidgetSettings extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-cog-6-tooth';
    protected static ?string $navigationGroup = 'Keuangan';
    protected static ?string $title = 'Pengaturan Widget Analisis';
    protected static ?string $slug = 'analytics-widget-settings';
    protected static ?int $navigationSort = 2;
    
    // Hide from navigation - sekarang digabung di "Pengaturan Sistem"
    protected static bool $shouldRegisterNavigation = false;

    protected static string $view = 'filament.pages.analytics-widget-settings';

    public ?array $data = [];

    public function mount(): void
    {
        $this->form->fill([
            'header_widgets' => $this->getHeaderWidgetsData(),
            'footer_widgets' => $this->getFooterWidgetsData(),
        ]);
    }

    public function form(Form $form): Form
    {
        $availableWidgets = $this->getAvailableWidgets();

        return $form
            ->schema([
                Forms\Components\Section::make('Widget Header')
                    ->description('Widget yang ditampilkan di bagian atas halaman analisis')
                    ->schema([
                        Forms\Components\Repeater::make('header_widgets')
                            ->schema([
                                Forms\Components\Select::make('widget')
                                    ->options($availableWidgets)
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
                            ->itemLabel(fn (array $state): ?string => $availableWidgets[$state['widget']] ?? null),
                    ]),

                Forms\Components\Section::make('Widget Footer')
                    ->description('Widget yang ditampilkan di bagian bawah halaman analisis')
                    ->schema([
                        Forms\Components\Repeater::make('footer_widgets')
                            ->schema([
                                Forms\Components\Select::make('widget')
                                    ->options($availableWidgets)
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
                            ->itemLabel(fn (array $state): ?string => $availableWidgets[$state['widget']] ?? null),
                    ]),
            ])
            ->statePath('data');
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('save')
                ->label('Simpan Pengaturan')
                ->action('save')
                ->color('primary'),
            Action::make('reset')
                ->label('Reset ke Default')
                ->action('resetToDefault')
                ->color('gray')
                ->requiresConfirmation(),
        ];
    }

    public function save(): void
    {
        $data = $this->form->getState();

        // Konversi struktur data untuk header widgets
        $headerWidgets = [];
        foreach ($data['header_widgets'] ?? [] as $widget) {
            $headerWidgets[$widget['widget']] = [
                'enabled' => $widget['enabled'] ?? true,
                'order' => $widget['order'] ?? 1,
                'title' => $widget['title'] ?? null,
            ];
        }

        // Konversi struktur data untuk footer widgets
        $footerWidgets = [];
        foreach ($data['footer_widgets'] ?? [] as $widget) {
            $footerWidgets[$widget['widget']] = [
                'enabled' => $widget['enabled'] ?? true,
                'order' => $widget['order'] ?? 1,
                'title' => $widget['title'] ?? null,
            ];
        }

        // Simpan konfigurasi header widgets
        Setting::set('analytics_widgets_header', json_encode($headerWidgets));
        
        // Simpan konfigurasi footer widgets
        Setting::set('analytics_widgets_footer', json_encode($footerWidgets));

        // Clear cache
        Cache::forget('setting_analytics_widgets_header');
        Cache::forget('setting_analytics_widgets_footer');

        Notification::make()
            ->title('Pengaturan Berhasil Disimpan')
            ->success()
            ->send();
    }

    public function resetToDefault(): void
    {
        // Jalankan seeder untuk reset ke default
        $seeder = new \Database\Seeders\AnalyticsWidgetSeeder();
        $seeder->run();

        // Reload form dengan data default
        $this->mount();

        Notification::make()
            ->title('Pengaturan Direset ke Default')
            ->success()
            ->send();
    }

    public function getHeaderWidgetsData(): array
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

    public function getFooterWidgetsData(): array
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

    public function getAvailableWidgets(): array
    {
        $available = Setting::get('analytics_widgets_available', '{}');
        $widgets = json_decode($available, true) ?: [];
        
        $options = [];
        foreach ($widgets as $key => $config) {
            $options[$key] = $config['name'];
        }
        
        return $options;
    }
}
