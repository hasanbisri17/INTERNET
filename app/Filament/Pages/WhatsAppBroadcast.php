<?php

namespace App\Filament\Pages;

use App\Models\BroadcastCampaign;
use App\Models\Customer;
use App\Models\User;
use App\Models\WhatsAppMessage;
use App\Services\WhatsAppService;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Radio;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Illuminate\Support\Facades\Log;

class WhatsAppBroadcast extends Page implements HasForms
{
    use InteractsWithForms;

    protected static ?string $navigationIcon = 'heroicon-o-megaphone';
    protected static ?string $navigationLabel = 'Broadcast WhatsApp';
    protected static ?string $title = 'Kirim Pesan Broadcast WhatsApp';
    protected static string $view = 'filament.pages.whats-app-broadcast';
    protected static ?string $navigationGroup = 'WhatsApp';
    protected static ?int $navigationSort = 1;

    public ?array $data = [];
    public string $recipientType = 'all';
    public array $selectedCustomers = [];
    public string $broadcastTitle = '';
    public string $message = '';
    public ?string $mediaPath = null;
    public ?string $mediaType = null;
    public ?string $documentPath = null;

    public function mount(): void
    {
        $this->form->fill([
            'title' => '',
            'recipient_type' => 'all',
            'selected_customers' => [],
            'message' => '',
            'media' => null,
            'document' => null,
        ]);
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Section::make('Informasi Broadcast')
                    ->description('Berikan judul untuk broadcast ini')
                    ->schema([
                        TextInput::make('title')
                            ->label('Judul Broadcast')
                            ->placeholder('Contoh: Pemberitahuan Libur Lebaran')
                            ->required()
                            ->maxLength(255)
                            ->live(debounce: 500)
                            ->afterStateUpdated(function ($state) {
                                $this->broadcastTitle = $state ?? '';
                            })
                            ->helperText('Berikan judul yang jelas untuk memudahkan identifikasi broadcast ini nanti'),
                    ])
                    ->collapsible(),

                Section::make('Penerima')
                    ->description('Pilih pelanggan yang akan menerima pesan broadcast')
                    ->schema([
                        Radio::make('recipient_type')
                            ->label('Tipe Penerima')
                            ->options([
                                'all' => 'Semua Pelanggan',
                                'active' => 'Pelanggan Aktif Saja',
                                'custom' => 'Pilih Manual',
                            ])
                            ->default('all')
                            ->live()
                            ->afterStateUpdated(function ($state) {
                                $this->recipientType = $state;
                            })
                            ->inline()
                            ->required(),

                        Select::make('selected_customers')
                            ->label('Pilih Pelanggan')
                            ->multiple()
                            ->searchable()
                            ->preload()
                            ->options(function () {
                                return Customer::query()
                                    ->whereNotNull('phone')
                                    ->orderBy('name')
                                    ->pluck('name', 'id');
                            })
                            ->visible(fn ($get) => $get('recipient_type') === 'custom')
                            ->required(fn ($get) => $get('recipient_type') === 'custom')
                            ->live()
                            ->afterStateUpdated(function ($state) {
                                $this->selectedCustomers = $state ?? [];
                            }),
                    ])
                    ->collapsible(),

                Section::make('Pesan')
                    ->description('Tulis pesan yang akan dikirim')
                    ->schema([
                        RichEditor::make('message')
                            ->label('Isi Pesan')
                            ->placeholder('Ketik pesan Anda di sini... (Tekan Win+. untuk emoji)')
                            ->required()
                            ->live(debounce: 500)
                            ->afterStateUpdated(function ($state) {
                                $this->message = $state ?? '';
                            })
                            ->toolbarButtons([
                                'bold',
                                'italic',
                                'underline',
                                'strike',
                                'bulletList',
                                'orderedList',
                                'h2',
                                'h3',
                                'blockquote',
                                'codeBlock',
                                'undo',
                                'redo',
                            ])
                            ->helperText('📌 Gunakan variabel: {nama}, {paket}, {tagihan}, {status} | 😊 Emoji: Win+. (Windows) atau Cmd+Ctrl+Space (Mac) | Formatting akan diubah ke format WhatsApp')
                            ->columnSpanFull(),
                    ])
                    ->collapsible(),

                Section::make('Lampiran')
                    ->description('Upload gambar atau dokumen (opsional)')
                    ->schema([
                        FileUpload::make('media')
                            ->label('Gambar')
                            ->image()
                            ->maxSize(5120) // 5MB
                            ->directory('whatsapp-media')
                            ->disk('public')
                            ->visibility('public')
                            ->acceptedFileTypes(['image/jpeg', 'image/png', 'image/gif', 'image/webp'])
                            ->preserveFilenames()
                            ->imagePreviewHeight('0')
                            ->loadingIndicatorPosition('left')
                            ->panelAspectRatio('1:1')
                            ->live()
                            ->afterStateUpdated(function ($state) {
                                if ($state) {
                                    $this->mediaPath = $state;
                                    $this->mediaType = 'image';
                                } else {
                                    $this->mediaPath = null;
                                    $this->mediaType = null;
                                }
                                // Force Livewire to refresh the preview
                                $this->dispatch('$refresh');
                            })
                            ->helperText('✅ File tersimpan! Lihat preview di sebelah kanan →'),

                        FileUpload::make('document')
                            ->label('Dokumen')
                            ->acceptedFileTypes(['application/pdf', 'application/msword', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document'])
                            ->maxSize(10240) // 10MB
                            ->directory('whatsapp-documents')
                            ->disk('public')
                            ->visibility('public')
                            ->preserveFilenames()
                            ->loadingIndicatorPosition('left')
                            ->panelAspectRatio('1:1')
                            ->live()
                            ->afterStateUpdated(function ($state) {
                                $this->documentPath = $state;
                                // Force Livewire to refresh the preview
                                $this->dispatch('$refresh');
                            })
                            ->helperText('✅ File tersimpan! Lihat preview di sebelah kanan →'),
                    ])
                    ->collapsible(),
            ])
            ->statePath('data');
    }

    public function sendBroadcast(): \Livewire\Features\SupportRedirects\Redirector
    {
        $data = $this->form->getState();

        try {
            Log::info('=== Starting Broadcast ===', [
                'data' => [
                    'recipient_type' => $data['recipient_type'],
                    'has_media' => !empty($data['media']),
                    'has_document' => !empty($data['document']),
                    'message_length' => strlen($data['message'] ?? ''),
                ]
            ]);

            // Get recipients based on selection
            $recipients = $this->getRecipients($data['recipient_type'], $data['selected_customers'] ?? []);

            if ($recipients->isEmpty()) {
                Log::warning('No recipients found for broadcast');
                
                Notification::make()
                    ->title('Tidak Ada Penerima')
                    ->body('Tidak ada pelanggan yang dipilih atau memiliki nomor WhatsApp.')
                    ->warning()
                    ->send();
                return redirect()->back();
            }

            Log::info('Recipients found', ['count' => $recipients->count()]);

            // Create broadcast campaign record
            $campaign = BroadcastCampaign::create([
                'title' => $data['title'] ?? 'Broadcast ' . now()->format('d M Y H:i'),
                'message' => $data['message'],
                'media_path' => $data['media'] ?? $data['document'] ?? null,
                'media_type' => !empty($data['media']) ? 'image' : (!empty($data['document']) ? 'document' : null),
                'recipient_type' => $data['recipient_type'],
                'recipient_ids' => $data['recipient_type'] === 'custom' ? $data['selected_customers'] : null,
                'total_recipients' => $recipients->count(),
                'status' => 'processing',
                'created_by' => auth()->id(),
                'sent_at' => now(),
            ]);

            Log::info('Broadcast campaign created', ['campaign_id' => $campaign->id]);

            // Get total recipients count
            $totalRecipients = $recipients->count();

            // Dispatch job to send messages in background
            \App\Jobs\SendBroadcastMessagesJob::dispatch(
                $campaign,
                $recipients->toArray(),
                $data['message'],
                $data['media'] ?? null,
                $data['document'] ?? null
            );
            
            // Show notification and redirect immediately
            Notification::make()
                ->title('📢 Broadcast Sedang Diproses')
                ->body("Campaign '{$campaign->title}' sedang dikirim ke {$totalRecipients} penerima. Proses berlangsung di background.")
                ->success()
                ->icon('heroicon-o-clock')
                ->actions([
                    \Filament\Notifications\Actions\Action::make('view')
                        ->label('Lihat Progress')
                        ->url(route('filament.admin.resources.broadcast-campaigns.view', $campaign))
                        ->button(),
                ])
                ->persistent()
                ->send();
            
            // Reset form
            $this->form->fill([
                'title' => '',
                'recipient_type' => 'all',
                'selected_customers' => [],
                'message' => '',
                'media' => null,
                'document' => null,
            ]);
            $this->broadcastTitle = '';
            $this->message = '';
            $this->mediaPath = null;
            $this->mediaType = null;
            $this->documentPath = null;
            
            // Redirect to campaign detail page
            return redirect(route('filament.admin.resources.broadcast-campaigns.view', $campaign));
            
        } catch (\Exception $e) {
            Log::error('Broadcast error: ' . $e->getMessage());
            
            Notification::make()
                ->title('Terjadi Kesalahan')
                ->body('Gagal mengirim broadcast: ' . $e->getMessage())
                ->danger()
                ->send();
                
            return redirect()->back();
        }
    }

    protected function getRecipients(string $type, array $selectedIds)
    {
        $query = Customer::whereNotNull('phone')->where('phone', '!=', '');

        switch ($type) {
            case 'active':
                $query->where('status', 'active');
                break;
            case 'custom':
                if (empty($selectedIds)) {
                    return collect([]);
                }
                $query->whereIn('id', $selectedIds);
                break;
            case 'all':
            default:
                // No additional filter
                break;
        }

        return $query->get();
    }

    protected function personalizeMessage(string $message, $customer): string
    {
        // Convert HTML to WhatsApp formatting
        $message = $this->convertHtmlToWhatsApp($message);
        
        $replacements = [
            '{nama}' => $customer->name ?? '',
            '{paket}' => $customer->internetPackage->name ?? '-',
            '{tagihan}' => 'Rp ' . number_format($customer->latestBill->amount ?? 0, 0, ',', '.'),
            '{status}' => $customer->status ?? '-',
        ];

        return str_replace(
            array_keys($replacements),
            array_values($replacements),
            $message
        );
    }

    protected function convertHtmlToWhatsApp(string $html): string
    {
        // Remove HTML tags and convert to WhatsApp formatting
        // Bold: <strong> or <b> -> *text*
        $html = preg_replace('/<(strong|b)>(.*?)<\/(strong|b)>/is', '*$2*', $html);
        
        // Italic: <em> or <i> -> _text_
        $html = preg_replace('/<(em|i)>(.*?)<\/(em|i)>/is', '_$2_', $html);
        
        // Strike: <s> or <del> or <strike> -> ~text~
        $html = preg_replace('/<(s|del|strike)>(.*?)<\/(s|del|strike)>/is', '~$2~', $html);
        
        // Code/Monospace: <code> -> ```text```
        $html = preg_replace('/<code>(.*?)<\/code>/is', '```$1```', $html);
        
        // Line breaks: <br> -> \n
        $html = preg_replace('/<br\s*\/?>/i', "\n", $html);
        
        // Paragraphs: <p> -> \n\n
        $html = preg_replace('/<\/p>\s*<p>/i', "\n\n", $html);
        $html = preg_replace('/<\/?p>/i', '', $html);
        
        // Lists: <ul><li> -> • item\n
        $html = preg_replace('/<li>(.*?)<\/li>/is', "• $1\n", $html);
        $html = preg_replace('/<\/?ul>/i', '', $html);
        
        // Ordered lists: <ol><li> -> 1. item\n (simple version)
        $html = preg_replace_callback('/<ol>(.*?)<\/ol>/is', function($matches) {
            $items = preg_split('/<li>(.*?)<\/li>/is', $matches[1], -1, PREG_SPLIT_DELIM_CAPTURE | PREG_SPLIT_NO_EMPTY);
            $result = '';
            $num = 1;
            foreach ($items as $item) {
                $item = trim($item);
                if (!empty($item)) {
                    $result .= $num . ". " . $item . "\n";
                    $num++;
                }
            }
            return $result;
        }, $html);
        
        // Headings: <h2>, <h3> -> *HEADING*\n
        $html = preg_replace('/<h[2-6]>(.*?)<\/h[2-6]>/is', "*$1*\n", $html);
        
        // Blockquote: <blockquote> -> > text
        $html = preg_replace('/<blockquote>(.*?)<\/blockquote>/is', "> $1", $html);
        
        // Remove any remaining HTML tags
        $html = strip_tags($html);
        
        // Clean up multiple newlines
        $html = preg_replace('/\n{3,}/', "\n\n", $html);
        
        // Decode HTML entities
        $html = html_entity_decode($html, ENT_QUOTES | ENT_HTML5, 'UTF-8');
        
        return trim($html);
    }

    public function getRecipientCount(): int
    {
        try {
            $data = $this->form->getState();
            $recipients = $this->getRecipients(
                $data['recipient_type'] ?? 'all',
                $data['selected_customers'] ?? []
            );
            return $recipients->count();
        } catch (\Exception $e) {
            return 0;
        }
    }
    
    /**
     * Send database notification for successful broadcast
     */
    protected function sendBroadcastNotification(BroadcastCampaign $campaign, int $successCount, int $failedCount): void
    {
        try {
            $adminUsers = User::where('is_admin', true)->get();
            
            Notification::make()
                ->title('📢 Broadcast WhatsApp Selesai')
                ->body("Campaign '{$campaign->title}' selesai dikirim. Berhasil: {$successCount}, Gagal: {$failedCount}")
                ->success()
                ->icon('heroicon-o-megaphone')
                ->actions([
                    \Filament\Notifications\Actions\Action::make('view')
                        ->label('Lihat Detail')
                        ->url(route('filament.admin.resources.broadcast-campaigns.view', $campaign))
                        ->button(),
                ])
                ->sendToDatabase($adminUsers);
        } catch (\Exception $e) {
            Log::error("Failed to send broadcast notification: {$e->getMessage()}");
        }
    }
    
    /**
     * Send database notification for failed broadcast
     */
    protected function sendBroadcastFailureNotification(BroadcastCampaign $campaign, int $totalRecipients): void
    {
        try {
            $adminUsers = User::where('is_admin', true)->get();
            
            Notification::make()
                ->title('❌ Broadcast WhatsApp Gagal')
                ->body("Campaign '{$campaign->title}' gagal dikirim ke semua {$totalRecipients} penerima. Silakan cek konfigurasi WhatsApp Gateway.")
                ->danger()
                ->icon('heroicon-o-x-circle')
                ->actions([
                    \Filament\Notifications\Actions\Action::make('view')
                        ->label('Lihat Detail')
                        ->url(route('filament.admin.resources.broadcast-campaigns.view', $campaign))
                        ->button(),
                    \Filament\Notifications\Actions\Action::make('settings')
                        ->label('Cek Pengaturan')
                        ->url(route('filament.admin.resources.whatsapp-settings.index'))
                        ->button()
                        ->color('gray'),
                ])
                ->sendToDatabase($adminUsers);
        } catch (\Exception $e) {
            Log::error("Failed to send broadcast failure notification: {$e->getMessage()}");
        }
    }
}

