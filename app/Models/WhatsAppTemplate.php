<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class WhatsAppTemplate extends Model
{
    use LogsActivity;

    protected $table = 'whatsapp_templates';

    protected $fillable = [
        'name',
        'code',
        'template_type',
        'order',
        'content',
        'description',
        'variables',
        'is_active',
    ];

    protected $casts = [
        'variables' => 'array',
        'is_active' => 'boolean',
    ];

    /**
     * Template type constants
     */
    public const TYPE_BILLING_NEW = 'billing_new';
    public const TYPE_BILLING_REMINDER_1 = 'billing_reminder_1';
    public const TYPE_BILLING_REMINDER_2 = 'billing_reminder_2';
    public const TYPE_BILLING_REMINDER_3 = 'billing_reminder_3';
    public const TYPE_BILLING_OVERDUE = 'billing_overdue';
    public const TYPE_BILLING_PAID = 'billing_paid';
    public const TYPE_SERVICE_SUSPENDED = 'service_suspended';
    public const TYPE_SERVICE_REACTIVATED = 'service_reactivated';
    public const TYPE_CUSTOM = 'custom';

    /**
     * Get all available template types
     */
    public static function getTemplateTypes(): array
    {
        return [
            self::TYPE_BILLING_NEW => 'Tagihan Baru',
            self::TYPE_BILLING_REMINDER_1 => 'Pengingat Tagihan (H-3)',
            self::TYPE_BILLING_REMINDER_2 => 'Pengingat Tagihan (H-1)',
            self::TYPE_BILLING_REMINDER_3 => 'Pengingat Tagihan (Jatuh Tempo)',
            self::TYPE_BILLING_OVERDUE => 'Tagihan Terlambat',
            self::TYPE_BILLING_PAID => 'Konfirmasi Pembayaran',
            self::TYPE_SERVICE_SUSPENDED => 'Penangguhan Layanan',
            self::TYPE_SERVICE_REACTIVATED => 'Pengaktifan Kembali Layanan',
            self::TYPE_CUSTOM => 'Custom / Lainnya',
        ];
    }

    /**
     * Get template type label
     */
    public function getTemplateTypeLabelAttribute(): string
    {
        $types = self::getTemplateTypes();
        return $types[$this->template_type] ?? 'Unknown';
    }

    /**
     * Replace variables in template content
     */
    public function formatMessage(array $data): string
    {
        $content = $this->content;

        foreach ($data as $key => $value) {
            $content = str_replace('{' . $key . '}', $value, $content);
        }

        return $content;
    }

    /**
     * Get template by code
     */
    public static function findByCode(string $code): ?self
    {
        return static::where('code', $code)
            ->where('is_active', true)
            ->first();
    }

    /**
     * Get template by type
     */
    public static function findByType(string $type): ?self
    {
        return static::where('template_type', $type)
            ->where('is_active', true)
            ->orderBy('order', 'asc')
            ->first();
    }

    /**
     * Get all templates by type
     */
    public static function getByType(string $type)
    {
        return static::where('template_type', $type)
            ->where('is_active', true)
            ->orderBy('order', 'asc')
            ->get();
    }

    /**
     * Default templates data
     */
    public static function getDefaultTemplates(): array
    {
        return [
            [
                'name' => 'Tagihan Baru',
                'code' => 'billing.new',
                'template_type' => self::TYPE_BILLING_NEW,
                'order' => 1,
                'content' => "Yth. {customer_name},\n\n".
                            "Tagihan internet Anda untuk periode {period} telah dibuat:\n".
                            "No. Invoice: {invoice_number}\n".
                            "Jumlah: Rp {amount}\n".
                            "Jatuh Tempo: {due_date}\n\n".
                            "Mohon melakukan pembayaran sebelum jatuh tempo.\n".
                            "Terima kasih.",
                'description' => 'Template untuk tagihan baru yang dikirim otomatis setiap bulan',
                'variables' => ['customer_name', 'period', 'invoice_number', 'amount', 'due_date', 'invoice_pdf'],
                'is_active' => true,
            ],
            [
                'name' => 'Pengingat Tagihan (H-3)',
                'code' => 'billing.reminder.1',
                'template_type' => self::TYPE_BILLING_REMINDER_1,
                'order' => 1,
                'content' => "Yth. {customer_name},\n\n".
                            "Mengingatkan tagihan internet Anda yang akan jatuh tempo 3 hari lagi:\n".
                            "No. Invoice: {invoice_number}\n".
                            "Jumlah: Rp {amount}\n".
                            "Jatuh Tempo: {due_date}\n\n".
                            "Mohon segera melakukan pembayaran.\n".
                            "Terima kasih.",
                'description' => 'Template pengingat 3 hari sebelum jatuh tempo',
                'variables' => ['customer_name', 'invoice_number', 'amount', 'due_date', 'days_left'],
                'is_active' => true,
            ],
            [
                'name' => 'Pengingat Tagihan (H-1)',
                'code' => 'billing.reminder.2',
                'template_type' => self::TYPE_BILLING_REMINDER_2,
                'order' => 1,
                'content' => "Yth. {customer_name},\n\n".
                            "Mengingatkan tagihan internet Anda yang akan jatuh tempo BESOK:\n".
                            "No. Invoice: {invoice_number}\n".
                            "Jumlah: Rp {amount}\n".
                            "Jatuh Tempo: {due_date}\n\n".
                            "Mohon segera melakukan pembayaran untuk menghindari denda keterlambatan.\n".
                            "Terima kasih.",
                'description' => 'Template pengingat 1 hari sebelum jatuh tempo',
                'variables' => ['customer_name', 'invoice_number', 'amount', 'due_date', 'days_left'],
                'is_active' => true,
            ],
            [
                'name' => 'Pengingat Tagihan (Jatuh Tempo)',
                'code' => 'billing.reminder.3',
                'template_type' => self::TYPE_BILLING_REMINDER_3,
                'order' => 1,
                'content' => "Yth. {customer_name},\n\n".
                            "Hari ini adalah tanggal jatuh tempo tagihan internet Anda:\n".
                            "No. Invoice: {invoice_number}\n".
                            "Jumlah: Rp {amount}\n".
                            "Jatuh Tempo: {due_date}\n\n".
                            "Mohon segera melakukan pembayaran hari ini untuk menghindari pemutusan layanan.\n".
                            "Terima kasih.",
                'description' => 'Template pengingat pada hari jatuh tempo',
                'variables' => ['customer_name', 'invoice_number', 'amount', 'due_date'],
                'is_active' => true,
            ],
            [
                'name' => 'Tagihan Terlambat',
                'code' => 'billing.overdue',
                'template_type' => self::TYPE_BILLING_OVERDUE,
                'order' => 1,
                'content' => "Yth. {customer_name},\n\n".
                            "Tagihan internet Anda telah melewati jatuh tempo:\n".
                            "No. Invoice: {invoice_number}\n".
                            "Jumlah: Rp {amount}\n".
                            "Jatuh Tempo: {due_date}\n".
                            "Terlambat: {days_overdue} hari\n\n".
                            "Mohon segera melakukan pembayaran untuk menghindari pemutusan layanan.\n".
                            "Terima kasih.",
                'description' => 'Template untuk tagihan yang sudah terlambat',
                'variables' => ['customer_name', 'invoice_number', 'amount', 'due_date', 'days_overdue'],
                'is_active' => true,
            ],
            [
                'name' => 'Konfirmasi Pembayaran',
                'code' => 'billing.paid',
                'template_type' => self::TYPE_BILLING_PAID,
                'order' => 1,
                'content' => "Yth. {customer_name},\n\n".
                            "Terima kasih! Pembayaran tagihan internet Anda telah kami terima:\n".
                            "No. Invoice: {invoice_number}\n".
                            "Jumlah: Rp {amount}\n".
                            "Tanggal Pembayaran: {payment_date}\n\n".
                            "Terima kasih atas kerjasamanya.",
                'description' => 'Template konfirmasi saat pembayaran berhasil diterima',
                'variables' => ['customer_name', 'invoice_number', 'amount', 'payment_date'],
                'is_active' => true,
            ],
        ];
    }

    public function getActivitylogOptions(): LogOptions
    {
        $verbs = [
            'created' => 'dibuat',
            'updated' => 'diperbarui',
            'deleted' => 'dihapus',
        ];

        return LogOptions::defaults()
            ->useLogName('whatsapp_templates')
            ->logOnly(['name', 'code', 'description', 'is_active', 'content', 'variables'])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs()
            ->setDescriptionForEvent(function (string $eventName) use ($verbs) {
                $name = $this->name ?? ("Template #{$this->id}");
                $verb = $verbs[$eventName] ?? $eventName;
                return "Template WhatsApp $name $verb.";
            });
    }
}
