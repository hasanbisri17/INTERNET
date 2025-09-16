<?php

namespace App\Jobs;

use App\Models\WhatsAppMessage;
use App\Services\WhatsAppService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Throwable;

class SendWhatsAppMessage implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;
    public int $timeout = 60;

    protected string $phone;
    protected string $message;
    protected array $options;
    protected ?int $whatsAppMessageId;

    public function __construct(string $phone, string $message, array $options = [], ?int $whatsAppMessageId = null)
    {
        $this->phone = $phone;
        $this->message = $message;
        $this->options = $options;
        $this->whatsAppMessageId = $whatsAppMessageId;
    }

    public function handle(): void
    {
        $service = new WhatsAppService();
        $result = $service->sendMessage($this->phone, $this->message, $this->options);

        if ($this->whatsAppMessageId) {
            $record = WhatsAppMessage::find($this->whatsAppMessageId);
            if ($record) {
                $record->update([
                    'status' => $result['success'] ? 'sent' : 'failed',
                    'response' => $result,
                    'sent_at' => now(),
                ]);
            }
        }

        if (!($result['success'] ?? false)) {
            // throw to trigger retry logic
            throw new \RuntimeException($result['error'] ?? 'Unknown error while sending WhatsApp message');
        }
    }

    public function failed(Throwable $exception): void
    {
        if ($this->whatsAppMessageId) {
            $record = WhatsAppMessage::find($this->whatsAppMessageId);
            if ($record) {
                $record->update([
                    'status' => 'failed',
                    'response' => [
                        'error' => $exception->getMessage(),
                    ],
                ]);
            }
        }
    }
}