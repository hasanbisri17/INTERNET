<?php

namespace App\Jobs;

use App\Models\BroadcastCampaign;
use App\Models\Customer;
use App\Models\User;
use App\Models\WhatsAppMessage;
use App\Services\WhatsAppService;
use Filament\Notifications\Notification;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class SendBroadcastMessagesJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $campaign;
    public $recipients;
    public $message;
    public $mediaPath;
    public $documentPath;

    /**
     * Create a new job instance.
     */
    public function __construct(BroadcastCampaign $campaign, $recipients, $message, $mediaPath = null, $documentPath = null)
    {
        $this->campaign = $campaign;
        $this->recipients = $recipients;
        $this->message = $message;
        $this->mediaPath = $mediaPath;
        $this->documentPath = $documentPath;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        try {
            // Update status to processing
            $this->campaign->update(['status' => 'processing']);
            
            $successCount = 0;
            $failedCount = 0;
            $whatsappService = new WhatsAppService();
            $totalRecipients = count($this->recipients);

            foreach ($this->recipients as $index => $customerData) {
                try {
                    // Convert array to Customer model if needed
                    $customer = is_array($customerData) ? Customer::find($customerData['id']) : $customerData;
                    
                    if (!$customer) {
                        Log::warning('Customer not found', ['customer_data' => $customerData]);
                        $failedCount++;
                        continue;
                    }
                    
                    Log::info('Processing customer', [
                        'customer_id' => $customer->id,
                        'customer_name' => $customer->name,
                        'phone' => $customer->phone,
                        'progress' => ($index + 1) . '/' . $totalRecipients,
                    ]);

                    // Replace variables in message
                    $personalizedMessage = $this->personalizeMessage($this->message, $customer);

                    // Send message
                    if ($this->mediaPath) {
                        // Send with media
                        $fullPath = storage_path('app/public/' . $this->mediaPath);
                        
                        if (!file_exists($fullPath)) {
                            throw new \Exception("Media file not found: {$fullPath}");
                        }
                        
                        $response = $whatsappService->sendDocument(
                            $customer->phone,
                            $fullPath,
                            $personalizedMessage
                        );
                    } elseif ($this->documentPath) {
                        // Send with document
                        $fullPath = storage_path('app/public/' . $this->documentPath);
                        
                        if (!file_exists($fullPath)) {
                            throw new \Exception("Document file not found: {$fullPath}");
                        }
                        
                        $response = $whatsappService->sendDocument(
                            $customer->phone,
                            $fullPath,
                            $personalizedMessage
                        );
                    } else {
                        // Send text only
                        $response = $whatsappService->sendMessage(
                            $customer->phone,
                            $personalizedMessage
                        );
                    }
                    
                    Log::info('WhatsApp API Response', [
                        'customer' => $customer->name,
                        'response' => $response,
                    ]);

                    // Check if response is successful
                    $isSuccess = isset($response['success']) ? $response['success'] : !empty($response);
                    
                    // Save to database
                    WhatsAppMessage::create([
                        'customer_id' => $customer->id,
                        'broadcast_campaign_id' => $this->campaign->id,
                        'message' => $personalizedMessage,
                        'media_path' => $this->mediaPath ?: $this->documentPath,
                        'media_type' => $this->mediaPath ? 'image' : ($this->documentPath ? 'document' : null),
                        'status' => $isSuccess ? 'sent' : 'failed',
                        'sent_at' => now(),
                        'message_type' => 'broadcast',
                        'response' => json_encode($response),
                    ]);

                    if ($isSuccess) {
                        $successCount++;
                    } else {
                        $failedCount++;
                        Log::warning('Broadcast message failed', [
                            'customer' => $customer->name,
                            'phone' => $customer->phone,
                            'response' => $response,
                        ]);
                    }
                    
                    // Update campaign progress
                    $this->campaign->update([
                        'success_count' => $successCount,
                        'failed_count' => $failedCount,
                    ]);
                    
                    // Small delay to avoid rate limiting
                    usleep(100000); // 0.1 second
                    
                } catch (\Exception $e) {
                    $customerId = is_array($customerData) ? ($customerData['id'] ?? 'unknown') : ($customerData->id ?? 'unknown');
                    Log::error('Failed to send broadcast to customer: ' . $customerId, [
                        'error' => $e->getMessage(),
                        'customer_data' => $customerData,
                    ]);
                    $failedCount++;
                    
                    // Update failed count
                    $this->campaign->update([
                        'failed_count' => $failedCount,
                    ]);
                }
            }

            // Update campaign with final results
            $this->campaign->update([
                'success_count' => $successCount,
                'failed_count' => $failedCount,
                'status' => $successCount > 0 ? 'completed' : 'failed',
                'sent_at' => now(),
            ]);

            Log::info('Broadcast campaign completed', [
                'campaign_id' => $this->campaign->id,
                'success' => $successCount,
                'failed' => $failedCount,
            ]);

            // Send database notification to admins
            $adminUsers = User::where('is_admin', true)->get();
            
            if ($successCount > 0) {
                Notification::make()
                    ->title('📢 Broadcast WhatsApp Selesai')
                    ->body("Campaign '{$this->campaign->title}' selesai dikirim. Berhasil: {$successCount}, Gagal: {$failedCount}")
                    ->success()
                    ->icon('heroicon-o-megaphone')
                    ->actions([
                        \Filament\Notifications\Actions\Action::make('view')
                            ->label('Lihat Detail')
                            ->url(route('filament.admin.resources.broadcast-campaigns.view', $this->campaign))
                            ->button(),
                    ])
                    ->sendToDatabase($adminUsers);
            } else {
                Notification::make()
                    ->title('❌ Broadcast WhatsApp Gagal')
                    ->body("Campaign '{$this->campaign->title}' gagal dikirim ke semua penerima.")
                    ->danger()
                    ->icon('heroicon-o-x-circle')
                    ->actions([
                        \Filament\Notifications\Actions\Action::make('view')
                            ->label('Lihat Detail')
                            ->url(route('filament.admin.resources.broadcast-campaigns.view', $this->campaign))
                            ->button(),
                    ])
                    ->sendToDatabase($adminUsers);
            }
            
        } catch (\Exception $e) {
            Log::error('Broadcast job failed: ' . $e->getMessage(), [
                'campaign_id' => $this->campaign->id,
                'trace' => $e->getTraceAsString(),
            ]);
            
            $this->campaign->update([
                'status' => 'failed',
            ]);
            
            throw $e;
        }
    }
    
    /**
     * Personalize message with customer data
     */
    protected function personalizeMessage(string $message, Customer $customer): string
    {
        $replacements = [
            '{nama}' => $customer->name ?? '',
            '{name}' => $customer->name ?? '',
            '{customer_name}' => $customer->name ?? '',
            '{email}' => $customer->email ?? '',
            '{phone}' => $customer->phone ?? '',
            '{alamat}' => $customer->address ?? '',
            '{address}' => $customer->address ?? '',
            '{paket}' => $customer->internetPackage?->name ?? '',
            '{package}' => $customer->internetPackage?->name ?? '',
        ];

        return str_replace(
            array_keys($replacements),
            array_values($replacements),
            $message
        );
    }
}
