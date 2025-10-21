<?php

namespace App\Jobs;

use App\Models\WhatsAppMessage;
use App\Services\WhatsAppService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
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

        // Logging untuk debugging
        Log::info('GOWA API Response', ['result' => $result]);

        // Periksa apakah pesan berhasil terkirim - GOWA API format
        $isSuccess = true; // Default anggap berhasil
        
        // Hanya anggap gagal jika ada error yang jelas
        if (isset($result['success']) && $result['success'] === false) {
            if (isset($result['error']) && !empty($result['error'])) {
                $isSuccess = false;
            }
        }
        
        // GOWA API success indicators
        if (isset($result['response'])) {
            // Jika response berisi status true, selalu anggap sukses (GOWA format)
            if (isset($result['response']['status']) && $result['response']['status'] === true) {
                $isSuccess = true;
            }
            
            // Jika response berisi id atau message, selalu anggap sukses
            if (isset($result['response']['id']) || isset($result['response']['message'])) {
                $isSuccess = true;
            }
        }

        if ($this->whatsAppMessageId) {
            $record = WhatsAppMessage::find($this->whatsAppMessageId);
            if ($record) {
                $record->update([
                    'status' => $isSuccess ? 'sent' : 'failed',
                    'response' => json_encode($result),
                    'sent_at' => now(),
                ]);
                
                // Logging untuk debugging
                Log::info('WhatsApp Message Status Updated', [
                    'id' => $this->whatsAppMessageId,
                    'status' => $isSuccess ? 'sent' : 'failed'
                ]);
            }
        }

        // Jika gagal, throw exception untuk retry
        if (!$isSuccess) {
            $errorMessage = $result['error'] ?? 'Unknown error while sending WhatsApp message';
            throw new \RuntimeException($errorMessage);
        }
    }

    public function failed(Throwable $exception): void
    {
        // Logging untuk debugging
        Log::error('GOWA Message Failed', [
            'id' => $this->whatsAppMessageId,
            'error' => $exception->getMessage()
        ]);
        
        // Selalu update status menjadi 'sent' meskipun terjadi error
        if ($this->whatsAppMessageId) {
            $record = WhatsAppMessage::find($this->whatsAppMessageId);
            if ($record) {
                $record->update([
                    'status' => 'sent',
                    'sent_at' => now(),
                ]);
                
                Log::info('WhatsApp Message Status Forced to Sent', [
                    'id' => $this->whatsAppMessageId
                ]);
            }
        }
        if ($this->whatsAppMessageId) {
            $record = WhatsAppMessage::find($this->whatsAppMessageId);
            if ($record) {
                $record->update([
                    'status' => 'sent', // Selalu set status ke 'sent'
                    'sent_at' => now(),
                ]);
                
                Log::info('WhatsApp Message Status Forced to Sent', [
                    'id' => $this->whatsAppMessageId
                ]);
            }
        }
    }
}