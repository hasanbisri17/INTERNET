<?php

namespace App\Filament\Pages;

use App\Models\Setting;
use App\Models\WhatsAppTemplate;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Pages\Page;
use Filament\Support\Exceptions\Halt;
use Filament\Notifications\Notification;

class WhatsAppTemplateSettings extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-document-text';
    protected static ?string $navigationLabel = 'Template Default Layanan';
    protected static ?string $title = 'Pengaturan Template Default Layanan';
    protected static ?string $navigationGroup = 'WhatsApp';
    protected static ?int $navigationSort = 5;
    
    // Hide from navigation - sekarang digabung di "Pengaturan Sistem"
    protected static bool $shouldRegisterNavigation = false;

    protected static string $view = 'filament.pages.whatsapp-template-settings';

    public ?array $data = [];

    public function mount(): void
    {
        $this->form->fill([
            'template_billing_new' => Setting::get('whatsapp_template_billing_new'),
            'template_billing_paid' => Setting::get('whatsapp_template_billing_paid'),
            'template_status_overdue' => Setting::get('whatsapp_template_status_overdue'),
            'template_service_suspended' => Setting::get('whatsapp_template_service_suspended'),
            'template_service_reactivated' => Setting::get('whatsapp_template_service_reactivated'),
        ]);
    }

    public function form(Form $form): Form
    {
        return $form
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
                    ->collapsible()
                    ->collapsed(false),

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
                            ->helperText('Template yang akan digunakan saat generate tagihan bulanan baru (dengan PDF invoice)')
                            ->suffixAction(
                                Forms\Components\Actions\Action::make('preview_billing_new')
                                    ->icon('heroicon-o-eye')
                                    ->color('info')
                                    ->tooltip('Preview Template')
                                    ->action(function ($state) {
                                        if ($state) {
                                            $this->dispatch('open-modal', id: 'preview-template-' . $state);
                                        }
                                    })
                            ),
                    ])
                    ->columns(1)
                    ->collapsible(),

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
                        
                        // Template untuk status payment overdue
                        Forms\Components\Select::make('template_status_overdue')
                            ->label('Status Payment Overdue')
                            ->options(function () {
                                return WhatsAppTemplate::where('template_type', WhatsAppTemplate::TYPE_STATUS_OVERDUE)
                                    ->where('is_active', true)
                                    ->orderBy('order', 'asc')
                                    ->pluck('name', 'id')
                                    ->toArray();
                            })
                            ->searchable()
                            ->native(false)
                            ->placeholder('Pilih template untuk status payment overdue')
                            ->helperText('Template yang akan digunakan saat status payment berubah menjadi overdue (sebelum suspend)'),
                    ])
                    ->columns(1)
                    ->collapsible(),

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
                    ])
                    ->columns(1)
                    ->collapsible(),

                Forms\Components\Section::make('📝 Catatan Penting')
                    ->schema([
                        Forms\Components\Placeholder::make('info')
                            ->label('')
                            ->content('
                                **Tentang Template Default:**
                                - ✅ Menu ini hanya untuk mengatur template **non-reminder** (Tagihan Baru, Konfirmasi Pembayaran, Suspend/Reactivate)
                                - 🔔 Untuk **reminder tagihan**, gunakan menu **"Pengaturan Reminder"** yang lebih fleksibel
                                - 📋 Hanya template yang aktif yang bisa dipilih
                                - 🔄 Jika tidak ada template yang dipilih, sistem akan menggunakan template pertama yang aktif (berdasarkan urutan)
                                - ✨ Anda bisa membuat multiple template untuk setiap jenis dan memilih mana yang ingin digunakan di menu **Template Pesan**
                                - ⚡ Perubahan akan langsung diterapkan setelah disimpan
                            ')
                            ->columnSpanFull(),
                    ])
                    ->collapsible()
                    ->collapsed(),
            ])
            ->statePath('data');
    }

    public function save(): void
    {
        try {
            $data = $this->form->getState();

            // Hanya simpan template untuk service non-reminder
            // Reminder sekarang diatur di menu "Pengaturan Reminder"
            $mappings = [
                'whatsapp_template_billing_new' => $data['template_billing_new'] ?? null,
                'whatsapp_template_billing_paid' => $data['template_billing_paid'] ?? null,
                'whatsapp_template_status_overdue' => $data['template_status_overdue'] ?? null,
                'whatsapp_template_service_suspended' => $data['template_service_suspended'] ?? null,
                'whatsapp_template_service_reactivated' => $data['template_service_reactivated'] ?? null,
            ];

            foreach ($mappings as $key => $value) {
                if ($value) {
                    Setting::set($key, $value);
                } else {
                    // Remove setting if null (use default)
                    Setting::where('key', $key)->delete();
                }
            }

            Notification::make()
                ->success()
                ->title('Pengaturan Template Berhasil Disimpan')
                ->body('Template default untuk layanan non-reminder telah dikonfigurasi.')
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

