<?php

namespace App\Services;

use App\Models\Customer;
use App\Models\Payment;
use App\Models\Setting;
use App\Models\WhatsAppMessage;
use App\Models\WhatsAppSetting;
use App\Models\WhatsAppTemplate;
use Carbon\Carbon;
use GuzzleHttp\Client;
use Illuminate\Support\Facades\Log;
use App\Jobs\SendWhatsAppMessage;

class WhatsAppService
{
    protected $client;
    protected $settings;

    public function __construct(?WhatsAppSetting $settings = null)
    {
        $this->settings = $settings ?? WhatsAppSetting::getCurrentSettings();
        
        if (!$this->settings) {
            throw new \Exception('WhatsApp settings not configured. Please configure WhatsApp settings first in WhatsApp → Pengaturan WhatsApp menu.');
        }

        // Validate API token is not empty (WAHA API requires authentication)
        if (empty($this->settings->api_token)) {
            throw new \Exception('WhatsApp API Token is required. Please set your WAHA API token in WhatsApp → Pengaturan WhatsApp menu. Error: API Token tidak boleh kosong.');
        }

        // WAHA API endpoint
        $baseUrl = rtrim($this->settings->api_url, '/') . '/';

        $headers = [
            'Content-Type' => 'application/json',
            'Accept' => 'application/json',
        ];
        
        // Add API token to headers (required for WAHA API)
        $headers['X-API-Key'] = $this->settings->api_token;

        $this->client = new Client([
            'base_uri' => $baseUrl,
            'headers' => $headers,
            'verify' => false, // Skip SSL verification for local development
        ]);
    }

    /**
     * Format phone number to international format
     */
    protected function formatPhone(string $phone): string
    {
        // Remove any non-numeric characters
        $phone = preg_replace('/[^0-9]/', '', $phone);

        // If number starts with 0, replace it with country code
        if (str_starts_with($phone, '0')) {
            $phone = $this->settings->default_country_code . substr($phone, 1);
        }
        // If number doesn't start with country code, add it
        elseif (!str_starts_with($phone, $this->settings->default_country_code)) {
            $phone = $this->settings->default_country_code . $phone;
        }

        return $phone;
    }

    /**
     * Get template for a specific service type
     */
    protected function getTemplateForService(string $serviceType): ?WhatsAppTemplate
    {
        // Map service type to setting key and template type
        $mappings = [
            'new' => [
                'setting_key' => 'whatsapp_template_billing_new',
                'template_type' => WhatsAppTemplate::TYPE_BILLING_NEW,
                'legacy_code' => 'billing.new',
            ],
            'reminder' => [ // Legacy support
                'setting_key' => 'whatsapp_template_billing_reminder_1',
                'template_type' => WhatsAppTemplate::TYPE_BILLING_REMINDER_1,
                'legacy_code' => 'billing.reminder',
            ],
            'reminder_h3' => [ // H-3 reminder
                'setting_key' => 'whatsapp_template_billing_reminder_1',
                'template_type' => WhatsAppTemplate::TYPE_BILLING_REMINDER_1,
                'legacy_code' => 'billing.reminder.1',
            ],
            'reminder_h1' => [ // H-1 reminder
                'setting_key' => 'whatsapp_template_billing_reminder_2',
                'template_type' => WhatsAppTemplate::TYPE_BILLING_REMINDER_2,
                'legacy_code' => 'billing.reminder.2',
            ],
            'reminder_h0' => [ // H+0 reminder (jatuh tempo)
                'setting_key' => 'whatsapp_template_billing_reminder_3',
                'template_type' => WhatsAppTemplate::TYPE_BILLING_REMINDER_3,
                'legacy_code' => 'billing.reminder.3',
            ],
            'overdue' => [
                'setting_key' => 'whatsapp_template_billing_overdue',
                'template_type' => WhatsAppTemplate::TYPE_BILLING_OVERDUE,
                'legacy_code' => 'billing.overdue',
            ],
            'paid' => [
                'setting_key' => 'whatsapp_template_billing_paid',
                'template_type' => WhatsAppTemplate::TYPE_BILLING_PAID,
                'legacy_code' => 'billing.paid',
            ],
            'suspended' => [
                'setting_key' => 'whatsapp_template_service_suspended',
                'template_type' => WhatsAppTemplate::TYPE_SERVICE_SUSPENDED,
                'legacy_code' => 'service.suspended',
            ],
        ];

        if (!isset($mappings[$serviceType])) {
            Log::warning('Unknown service type', ['service_type' => $serviceType]);
            return null;
        }

        $mapping = $mappings[$serviceType];

        // 1. Try to get template from settings (configured by user)
        $templateId = Setting::get($mapping['setting_key']);
        if ($templateId) {
            $template = WhatsAppTemplate::find($templateId);
            if ($template && $template->is_active) {
                Log::info('Using configured template from settings', [
                    'service_type' => $serviceType,
                    'template_id' => $templateId,
                    'template_name' => $template->name,
                ]);
                return $template;
            }
        }

        // 2. Fallback: Get template by type (first active template with matching type)
        $template = WhatsAppTemplate::findByType($mapping['template_type']);
        if ($template) {
            Log::info('Using fallback template by type', [
                'service_type' => $serviceType,
                'template_type' => $mapping['template_type'],
                'template_name' => $template->name,
            ]);
            return $template;
        }

        // 3. Last fallback: Try legacy code-based lookup
        $template = WhatsAppTemplate::findByCode($mapping['legacy_code']);
        if ($template) {
            Log::info('Using legacy template by code', [
                'service_type' => $serviceType,
                'template_code' => $mapping['legacy_code'],
                'template_name' => $template->name,
            ]);
            return $template;
        }

        Log::error('No template found for service type', ['service_type' => $serviceType]);
        return null;
    }

    /**
     * Send a WhatsApp message using WAHA API
     */
    public function sendMessage(string $phone, string $message, array $options = []): array
    {
        try {
            $phone = $this->formatPhone($phone);
            
            // Build JSON data for WAHA API
            $jsonData = [
                'chatId' => $phone . '@c.us',
                'text' => $message,
                'session' => $this->settings->session ?? 'default',
            ];

            // Add any additional options
            if (!empty($options)) {
                $jsonData = array_merge($jsonData, $options);
            }

            // Log request data for debugging
            Log::info('WAHA API Request', [
                'endpoint' => 'api/sendText',
                'data' => $jsonData
            ]);

            // Send message using WAHA API
            $response = $this->client->post('api/sendText', [
                'json' => $jsonData
            ]);

            $result = json_decode($response->getBody()->getContents(), true);
            
            // Log full response for debugging
            Log::info('WAHA API Response', [
                'response' => $result
            ]);
            
            // WAHA API success handling
            if (isset($result['id']) || isset($result['key']) || isset($result['message']) || !empty($result)) {
                return [
                    'success' => true,
                    'response' => $result,
                ];
            }
            
            // Jika respons kosong, anggap gagal
            $errorMessage = 'Empty response from WAHA API';
            throw new \Exception($errorMessage);
        }
        catch (\Exception $e) {
            Log::error('WhatsApp message sending failed: ' . $e->getMessage(), [
                'phone' => $phone,
                'message' => $message,
                'options' => $options,
                'trace' => $e->getTraceAsString(),
            ]);

            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }
    
    /**
     * Send a document/file via WhatsApp using WAHA API
     */
    public function sendDocument(string $phone, string $filePath, string $caption = '', array $options = []): array
    {
        try {
            $phone = $this->formatPhone($phone);
            
            // Ensure file exists
            if (!file_exists($filePath)) {
                throw new \Exception("File not found: {$filePath}");
            }
            
            // Konversi file ke base64 untuk dikirim ke API
            $fileContent = file_get_contents($filePath);
            if ($fileContent === false) {
                throw new \Exception("Failed to read file: {$filePath}");
            }
            
            $base64File = base64_encode($fileContent);
            $fileName = basename($filePath);
            
            // Deteksi MIME type dan tentukan apakah ini gambar atau dokumen
            $extension = strtolower(pathinfo($filePath, PATHINFO_EXTENSION));
            $mimeType = 'application/octet-stream'; // Default MIME type
            $isImage = false;
            
            // Set MIME type berdasarkan ekstensi
            if (in_array($extension, ['jpg', 'jpeg'])) {
                $mimeType = 'image/jpeg';
                $isImage = true;
            } elseif ($extension === 'png') {
                $mimeType = 'image/png';
                $isImage = true;
            } elseif ($extension === 'gif') {
                $mimeType = 'image/gif';
                $isImage = true;
            } elseif ($extension === 'webp') {
                $mimeType = 'image/webp';
                $isImage = true;
            } elseif ($extension === 'pdf') {
                $mimeType = 'application/pdf';
            } elseif ($extension === 'doc') {
                $mimeType = 'application/msword';
            } elseif ($extension === 'docx') {
                $mimeType = 'application/vnd.openxmlformats-officedocument.wordprocessingml.document';
            }
            
            // Coba berbagai format endpoint yang mungkin didukung WAHA API
            $formats = [
                // Format 1: file.data (nested object)
                [
                    'endpoint' => $isImage ? 'api/sendImage' : 'api/sendFile',
                    'data' => [
                        'chatId' => $phone . '@c.us',
                        'file' => [
                            'mimetype' => $mimeType,
                            'filename' => $fileName,
                            'data' => $base64File,
                        ],
                        'caption' => !empty($caption) ? $caption : '',
                        'session' => $this->settings->session ?? 'default',
                    ],
                    'log_placeholder' => ['file', 'data'],
                ],
                // Format 2: Direct base64 with mimetype
                [
                    'endpoint' => $isImage ? 'api/sendImage' : 'api/sendFile',
                    'data' => [
                        'chatId' => $phone . '@c.us',
                        'file' => $base64File,
                        'mimetype' => $mimeType,
                        'filename' => $fileName,
                        'caption' => !empty($caption) ? $caption : '',
                        'session' => $this->settings->session ?? 'default',
                    ],
                    'log_placeholder' => ['file'],
                ],
                // Format 3: Data URI
                [
                    'endpoint' => $isImage ? 'api/sendImage' : 'api/sendFile',
                    'data' => [
                        'chatId' => $phone . '@c.us',
                        'file' => "data:{$mimeType};base64,{$base64File}",
                        'caption' => !empty($caption) ? $caption : '',
                        'session' => $this->settings->session ?? 'default',
                    ],
                    'log_placeholder' => ['file'],
                ],
                // Format 4: Using 'media' key instead of 'file'
                [
                    'endpoint' => $isImage ? 'api/sendImage' : 'api/sendFile',
                    'data' => [
                        'chatId' => $phone . '@c.us',
                        'media' => [
                            'mimetype' => $mimeType,
                            'filename' => $fileName,
                            'data' => $base64File,
                        ],
                        'caption' => !empty($caption) ? $caption : '',
                        'session' => $this->settings->session ?? 'default',
                    ],
                    'log_placeholder' => ['media', 'data'],
                ],
            ];
            
            // Coba setiap format sampai berhasil
            foreach ($formats as $index => $format) {
                try {
                    // Merge with additional options
                    $jsonData = $format['data'];
                    if (!empty($options)) {
                        $jsonData = array_merge($jsonData, $options);
                    }
                    
                    // Log request data for debugging (hide base64)
                    $logData = $jsonData;
                    foreach ($format['log_placeholder'] as $key) {
                        if (isset($logData[$key])) {
                            if (is_array($logData[$key]) && isset($logData[$key]['data'])) {
                                $logData[$key]['data'] = '[BASE64_CONTENT]';
                            } else {
                                $logData[$key] = '[BASE64_CONTENT]';
                            }
                        }
                    }
                    
                    Log::info("WAHA API Request (Format #{$index})", [
                        'endpoint' => $format['endpoint'],
                        'data' => $logData
                    ]);
                    
                    // Send to WAHA API
                    $response = $this->client->post($format['endpoint'], [
                        'json' => $jsonData
                    ]);
                    
                    $result = json_decode($response->getBody()->getContents(), true);
                    
                    // Log full response for debugging
                    Log::info("WAHA API Response (Format #{$index})", [
                        'response' => $result
                    ]);
                    
                    // WAHA API success handling
                    if (isset($result['id']) || isset($result['key']) || isset($result['message']) || !empty($result)) {
                        Log::info("✅ Success with Format #{$index}!");
                        return [
                            'success' => true,
                            'response' => $result,
                        ];
                    }
                } catch (\Exception $error) {
                    // Log error from this format
                    Log::warning("Failed Format #{$index} ({$format['endpoint']}): " . $error->getMessage());
                    
                    // Continue to next format
                    continue;
                }
            }
            
            // Jika semua metode gagal, throw exception
            $errorMessage = 'All sending methods failed. Check WAHA API configuration.';
            throw new \Exception($errorMessage);
        }
        catch (\Exception $e) {
            Log::error('WhatsApp document sending failed: ' . $e->getMessage(), [
                'phone' => $phone,
                'file' => $filePath,
                'caption' => $caption,
                'options' => $options,
                'trace' => $e->getTraceAsString(),
            ]);
            
            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Send a billing notification using template
     */
    public function sendBillingNotification(Payment $payment, string $type = 'new', bool $sendPDF = false, ?WhatsAppTemplate $customTemplate = null): void
    {
        $customer = $payment->customer;
        if (!$customer->phone) {
            Log::warning('Customer has no phone number', ['customer_id' => $customer->id]);
            return;
        }

        // Use custom template if provided, otherwise get template using the new method that respects settings
        $template = $customTemplate ?? $this->getTemplateForService($type);
        if (!$template) {
            Log::error('Template not found for service type', ['type' => $type]);
            return;
        }

        // Format message with template variables
        $messageData = [
            'customer_name' => $customer->name,
            'period' => Carbon::parse($payment->due_date)->format('F Y'),
            'invoice_number' => $payment->invoice_number,
            'amount' => number_format($payment->amount, 0, ',', '.'),
            'due_date' => Carbon::parse($payment->due_date)->format('d F Y'),
            'payment_date' => $payment->payment_date ? Carbon::parse($payment->payment_date)->format('d F Y') : '-',
            'days_overdue' => Carbon::now()->diffInDays($payment->due_date),
        ];
        
        $message = $template->formatMessage($messageData);

        // Create WhatsApp message record as pending
        $whatsappMessage = WhatsAppMessage::create([
            'customer_id' => $customer->id,
            'payment_id' => $payment->id,
            'message_type' => "billing.{$type}",
            'message' => $message,
            'status' => 'pending',
        ]);
        
        try {
            // Generate PDF invoice if needed (for new bill, paid invoice, reminders, or overdue)
            $pdfPath = null;
            $typesWithPDF = ['new', 'paid', 'reminder', 'reminder_h3', 'reminder_h1', 'reminder_h0', 'overdue'];
            
            if ($sendPDF && in_array($type, $typesWithPDF)) {
                try {
                    $pdfPath = $this->generateInvoicePDF($payment);
                    Log::info('Invoice PDF generated', [
                        'invoice' => $payment->invoice_number,
                        'type' => $type,
                        'status' => $payment->status,
                        'path' => $pdfPath
                    ]);
                } catch (\Exception $e) {
                    Log::error('Failed to generate invoice PDF', [
                        'invoice' => $payment->invoice_number,
                        'type' => $type,
                        'error' => $e->getMessage()
                    ]);
                    // Continue without PDF if generation fails
                }
            }

            // Kirim pesan dengan atau tanpa PDF
            if ($pdfPath && file_exists($pdfPath)) {
                // Kirim dokumen PDF dengan caption (pesan)
                $result = $this->sendDocument($customer->phone, $pdfPath, $message);
                
                // Update media info di database
                $whatsappMessage->media_path = str_replace(storage_path('app/public/'), '', $pdfPath);
                $whatsappMessage->media_type = 'document';
            } else {
                // Kirim pesan teks saja
                $result = $this->sendMessage($customer->phone, $message);
            }
            
            // Update status pesan
            $whatsappMessage->status = $result['success'] ? 'sent' : 'failed';
            $whatsappMessage->response = json_encode($result);
            $whatsappMessage->sent_at = now();
            $whatsappMessage->save();
            
            // Log activity if successfully sent
            if ($result['success']) {
                $typeLabels = [
                    'new' => 'Tagihan Baru',
                    'paid' => 'Konfirmasi Pembayaran',
                    'reminder' => 'Reminder Pembayaran',
                    'reminder_h3' => 'Reminder H-3',
                    'reminder_h1' => 'Reminder H-1',
                    'reminder_h0' => 'Reminder Jatuh Tempo',
                    'overdue' => 'Tagihan Terlambat',
                    'suspended' => 'Penangguhan Layanan',
                ];
                $typeLabel = $typeLabels[$type] ?? ucfirst($type);
                $withPdf = !empty($pdfPath) ? ' (dengan PDF invoice)' : '';
                
                activity('whatsapp_notifications')
                    ->performedOn($payment)
                    ->withProperties([
                        'customer_id' => $customer->id,
                        'customer_name' => $customer->name,
                        'customer_phone' => $customer->phone,
                        'invoice_number' => $payment->invoice_number,
                        'notification_type' => $type,
                        'with_pdf' => !empty($pdfPath),
                        'whatsapp_message_id' => $whatsappMessage->id,
                    ])
                    ->log("WhatsApp '{$typeLabel}' terkirim ke {$customer->name} untuk invoice {$payment->invoice_number}{$withPdf}");
            }
            
            Log::info('Billing notification sent', [
                'customer' => $customer->name,
                'invoice' => $payment->invoice_number,
                'with_pdf' => !empty($pdfPath),
                'success' => $result['success']
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to send billing notification', [
                'customer' => $customer->name,
                'invoice' => $payment->invoice_number,
                'error' => $e->getMessage()
            ]);
            
            $whatsappMessage->status = 'failed';
            $whatsappMessage->response = json_encode(['error' => $e->getMessage()]);
            $whatsappMessage->save();
            
            // Log failed activity
            activity('whatsapp_notifications')
                ->performedOn($payment)
                ->withProperties([
                    'customer_id' => $customer->id,
                    'customer_name' => $customer->name,
                    'customer_phone' => $customer->phone,
                    'invoice_number' => $payment->invoice_number,
                    'notification_type' => $type,
                    'error' => $e->getMessage(),
                ])
                ->log("WhatsApp notification gagal dikirim ke {$customer->name} untuk invoice {$payment->invoice_number}: {$e->getMessage()}");
        }
    }

    /**
     * Generate PDF invoice for a payment
     */
    protected function generateInvoicePDF(Payment $payment): string
    {
        // Create temp directory if not exists
        $tempPath = storage_path('app/public/invoices');
        if (!file_exists($tempPath)) {
            mkdir($tempPath, 0755, true);
        }

        $filename = $payment->invoice_number . '.pdf';
        $filePath = $tempPath . '/' . $filename;

        // Generate PDF using DomPDF with modern template
        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('invoice-modern', ['payment' => $payment])
            ->setPaper('a4');

        // Save PDF to storage
        $pdf->save($filePath);

        return $filePath;
    }

    /**
     * Send a broadcast message to multiple customers
     */
    public function sendBroadcast(array $customerIds, string $message): array
    {
        $results = [
            'total' => count($customerIds),
            'sent' => 0,
            'failed' => 0,
        ];

        $customers = Customer::whereIn('id', $customerIds)
            ->whereNotNull('phone')
            ->get();

        foreach ($customers as $customer) {
            // Create WhatsApp message record
            $whatsappMessage = new WhatsAppMessage([
                'customer_id' => $customer->id,
                'message_type' => 'broadcast',
                'message' => $message,
                'status' => 'pending',
            ]);

            // Send message
            $result = $this->sendMessage($customer->phone, $message);

            // Update message status
            $whatsappMessage->status = $result['success'] ? 'sent' : 'failed';
            $whatsappMessage->response = json_encode($result); // Mengubah array menjadi JSON string
            $whatsappMessage->sent_at = now();
            $whatsappMessage->save();

            // Update results
            if ($result['success']) {
                $results['sent']++;
            } else {
                $results['failed']++;
            }

            // Add delay to avoid rate limiting
            usleep(5000000); // 0.5 second delay
        }

        return $results;
    }

    /**
     * Schedule a broadcast message to multiple customers
     */
    public function scheduleBroadcast(array $customerIds, string $message, string $scheduledAt): array
    {
        $customers = Customer::whereIn('id', $customerIds)
            ->whereNotNull('phone')
            ->get();

        $scheduled = 0;
        foreach ($customers as $customer) {
            // Create WhatsApp message record
            $whatsappMessage = new WhatsAppMessage([
                'customer_id' => $customer->id,
                'message_type' => 'broadcast',
                'message' => $message,
                'status' => 'pending',
                'scheduled_at' => $scheduledAt,
            ]);
            $whatsappMessage->save();
            $scheduled++;
        }

        return [
            'total' => count($customerIds),
            'scheduled' => $scheduled,
        ];
    }
}
