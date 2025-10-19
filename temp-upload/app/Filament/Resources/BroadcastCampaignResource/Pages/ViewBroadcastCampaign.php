<?php

namespace App\Filament\Resources\BroadcastCampaignResource\Pages;

use App\Filament\Resources\BroadcastCampaignResource;
use Filament\Actions;
use Filament\Infolists\Infolist;
use Filament\Infolists\Components;
use Filament\Resources\Pages\ViewRecord;
use Filament\Support\Enums\IconPosition;
use Illuminate\Support\HtmlString;
use OpenSpout\Writer\XLSX\Writer;
use OpenSpout\Common\Entity\Row;
use OpenSpout\Common\Entity\Cell;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ViewBroadcastCampaign extends ViewRecord
{
    protected static string $resource = BroadcastCampaignResource::class;

    protected static string $view = 'filament.resources.broadcast-campaign-resource.pages.view-broadcast-campaign';
    
    // Auto-refresh every 3 seconds when status is processing
    protected int $refreshInterval = 3;
    
    public function getRefreshInterval(): ?int
    {
        // Only auto-refresh when campaign is still processing
        if ($this->record->status === 'processing') {
            return $this->refreshInterval;
        }
        
        return null;
    }

    public function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                Components\Section::make('Informasi Broadcast')
                    ->description(function ($record) {
                        if ($record->status === 'processing') {
                            $progress = $record->total_recipients > 0 
                                ? round((($record->success_count + $record->failed_count) / $record->total_recipients) * 100) 
                                : 0;
                            return "⏳ Sedang mengirim pesan... Progress: {$progress}% ({$record->success_count} + {$record->failed_count} / {$record->total_recipients})";
                        }
                        return null;
                    })
                    ->schema([
                        Components\Grid::make(2)
                            ->schema([
                                Components\TextEntry::make('title')
                                    ->label('Judul')
                                    ->size('lg')
                                    ->weight('bold')
                                    ->columnSpanFull(),
                                
                                Components\TextEntry::make('status')
                                    ->label('Status')
                                    ->badge()
                                    ->formatStateUsing(fn (string $state): string => match($state) {
                                        'completed' => 'Selesai',
                                        'processing' => 'Diproses',
                                        'pending' => 'Menunggu',
                                        'failed' => 'Gagal',
                                        default => $state,
                                    })
                                    ->color(fn (string $state): string => match ($state) {
                                        'completed' => 'success',
                                        'processing' => 'info',
                                        'pending' => 'warning',
                                        'failed' => 'danger',
                                        default => 'gray',
                                    }),

                                Components\TextEntry::make('total_recipients')
                                    ->label('Total Penerima')
                                    ->badge()
                                    ->color('info'),

                                Components\TextEntry::make('success_count')
                                    ->label('Berhasil')
                                    ->badge()
                                    ->color('success')
                                    ->suffix(fn ($record) => ' (' . number_format($record->success_rate, 1) . '%)'),

                                Components\TextEntry::make('failed_count')
                                    ->label('Gagal')
                                    ->badge()
                                    ->color('danger'),

                                Components\TextEntry::make('recipient_type_label')
                                    ->label('Tipe Penerima'),

                                Components\TextEntry::make('creator.name')
                                    ->label('Dibuat Oleh')
                                    ->default('-'),

                                Components\TextEntry::make('sent_at')
                                    ->label('Tanggal Dikirim')
                                    ->dateTime('d F Y, H:i')
                                    ->default('-'),

                                Components\TextEntry::make('created_at')
                                    ->label('Tanggal Dibuat')
                                    ->dateTime('d F Y, H:i'),
                            ]),
                    ]),

                Components\Section::make('Pesan')
                    ->schema([
                        Components\TextEntry::make('message')
                            ->label('')
                            ->formatStateUsing(function (string $state): HtmlString {
                                // Convert HTML tags to readable format
                                $formatted = $state;
                                
                                // Bold: <strong> or <b> -> keep with styling
                                $formatted = preg_replace('/<(strong|b)>(.*?)<\/(strong|b)>/is', '<strong>$2</strong>', $formatted);
                                
                                // Italic: <em> or <i> -> keep with styling
                                $formatted = preg_replace('/<(em|i)>(.*?)<\/(em|i)>/is', '<em>$2</em>', $formatted);
                                
                                // Strike: <s> or <del> or <strike> -> keep with styling
                                $formatted = preg_replace('/<(s|del|strike)>(.*?)<\/(s|del|strike)>/is', '<del>$2</del>', $formatted);
                                
                                // Code/Monospace: <code> -> keep with styling
                                $formatted = preg_replace('/<code>(.*?)<\/code>/is', '<code class="px-1 py-0.5 bg-gray-100 dark:bg-gray-700 rounded text-sm font-mono">$1</code>', $formatted);
                                
                                // Line breaks: <br> -> \n
                                $formatted = preg_replace('/<br\s*\/?>/i', "\n", $formatted);
                                
                                // Paragraphs: <p> -> \n\n
                                $formatted = preg_replace('/<\/p>\s*<p>/i', "\n\n", $formatted);
                                $formatted = preg_replace('/<\/?p>/i', '', $formatted);
                                
                                // Lists: <ul><li> -> • item\n
                                $formatted = preg_replace('/<li>(.*?)<\/li>/is', "• $1\n", $formatted);
                                $formatted = preg_replace('/<\/?ul>/i', '', $formatted);
                                
                                // Ordered lists
                                $formatted = preg_replace_callback('/<ol>(.*?)<\/ol>/is', function($matches) {
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
                                }, $formatted);
                                
                                // Headings: <h2>, <h3> -> bold + larger
                                $formatted = preg_replace('/<h[2-6]>(.*?)<\/h[2-6]>/is', "<strong class='text-lg'>$1</strong>\n", $formatted);
                                
                                // Blockquote: <blockquote>
                                $formatted = preg_replace('/<blockquote>(.*?)<\/blockquote>/is', '<blockquote class="border-l-4 border-gray-300 pl-4 italic">$1</blockquote>', $formatted);
                                
                                // Convert newlines to <br> for display
                                $formatted = nl2br($formatted);
                                
                                return new HtmlString('<div class="prose prose-sm max-w-none dark:prose-invert">' . $formatted . '</div>');
                            })
                            ->columnSpanFull(),
                    ]),

                Components\Section::make('Lampiran')
                    ->schema([
                        Components\TextEntry::make('media_path')
                            ->label('Gambar')
                            ->formatStateUsing(fn (string $state): HtmlString => new HtmlString(
                                '<div class="rounded-lg overflow-hidden border border-gray-200 dark:border-gray-700 inline-block">
                                    <img src="' . asset('storage/' . $state) . '" alt="Broadcast Image" class="max-w-full h-auto" style="max-height: 500px; width: auto;" />
                                </div>'
                            ))
                            ->visible(fn ($record) => $record->media_type === 'image'),

                        Components\TextEntry::make('media_path')
                            ->label('Dokumen')
                            ->formatStateUsing(fn (string $state): HtmlString => new HtmlString(
                                '<div class="flex items-center gap-3 p-4 bg-gray-50 dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700">
                                    <div class="flex-shrink-0">
                                        <svg class="w-12 h-12 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z"/>
                                        </svg>
                                    </div>
                                    <div class="flex-1 min-w-0">
                                        <p class="text-sm font-semibold text-gray-900 dark:text-gray-100">' . basename($state) . '</p>
                                        <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">Dokumen PDF</p>
                                    </div>
                                    <a href="' . asset('storage/' . $state) . '" target="_blank" download class="flex-shrink-0 inline-flex items-center gap-2 px-4 py-2 bg-primary-600 hover:bg-primary-700 text-white text-sm font-medium rounded-lg transition-colors">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/>
                                        </svg>
                                        Download
                                    </a>
                                </div>'
                            ))
                            ->visible(fn ($record) => $record->media_type === 'document'),
                    ])
                    ->visible(fn ($record) => !empty($record->media_path)),
            ]);
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('downloadExcel')
                ->label('Download Hasil')
                ->icon('heroicon-o-arrow-down-tray')
                ->color('success')
                ->action(function () {
                    return $this->downloadExcel();
                }),
        ];
    }

    public function downloadExcel(): StreamedResponse
    {
        $campaign = $this->record;
        $messages = $campaign->messages()->with('customer.internetPackage')->get();
        
        $fileName = 'broadcast_' . str_replace(' ', '_', strtolower($campaign->title)) . '_' . now()->format('Y-m-d_His') . '.xlsx';
        
        return response()->stream(function () use ($messages, $campaign) {
            $writer = new Writer();
            $writer->openToFile('php://output');
            
            // Header row
            $headerRow = Row::fromValues([
                'No',
                'Nama Pelanggan',
                'No. WhatsApp',
                'Paket Internet',
                'Status',
                'Waktu Kirim',
                'Response'
            ]);
            $writer->addRow($headerRow);
            
            // Data rows
            $no = 1;
            foreach ($messages as $message) {
                $status = match($message->status) {
                    'sent' => 'Berhasil',
                    'failed' => 'Gagal',
                    'pending' => 'Menunggu',
                    default => ucfirst($message->status),
                };
                
                $dataRow = Row::fromValues([
                    $no++,
                    $message->customer->name ?? 'N/A',
                    $message->customer->phone ?? '-',
                    $message->customer->internetPackage->name ?? '-',
                    $status,
                    $message->sent_at ? $message->sent_at->format('d/m/Y H:i') : '-',
                    $message->response ? (is_string($message->response) ? 'Success' : 'Success') : '-'
                ]);
                $writer->addRow($dataRow);
            }
            
            $writer->close();
        }, 200, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'Content-Disposition' => 'attachment; filename="' . $fileName . '"',
            'Cache-Control' => 'max-age=0',
        ]);
    }
}

