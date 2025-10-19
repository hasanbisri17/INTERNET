<?php

namespace Database\Seeders;

use App\Models\PaymentReminderRule;
use App\Models\WhatsAppTemplate;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class PaymentReminderRuleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Get template references (nullable - will use defaults if not found)
        $templateH3 = WhatsAppTemplate::where('template_type', 'billing_reminder_1')->first();
        $templateH1 = WhatsAppTemplate::where('template_type', 'billing_reminder_2')->first();
        $templateH0 = WhatsAppTemplate::where('template_type', 'billing_reminder_3')->first();
        $templateOverdue = WhatsAppTemplate::where('template_type', 'billing_overdue')->first();

        // Default reminder rules
        $rules = [
            [
                'name' => 'Pengingat 7 Hari Sebelum Jatuh Tempo',
                'days_before_due' => -7,
                'whatsapp_template_id' => null, // Will use default template
                'is_active' => true,
                'send_time' => '09:00:00',
                'priority' => 10,
                'description' => 'Reminder pertama, 1 minggu sebelum jatuh tempo',
            ],
            [
                'name' => 'Pengingat 3 Hari Sebelum Jatuh Tempo',
                'days_before_due' => -3,
                'whatsapp_template_id' => $templateH3?->id,
                'is_active' => true,
                'send_time' => '09:00:00',
                'priority' => 20,
                'description' => 'Reminder kedua, 3 hari sebelum jatuh tempo',
            ],
            [
                'name' => 'Pengingat 1 Hari Sebelum Jatuh Tempo',
                'days_before_due' => -1,
                'whatsapp_template_id' => $templateH1?->id,
                'is_active' => true,
                'send_time' => '09:00:00',
                'priority' => 30,
                'description' => 'Reminder ketiga, 1 hari sebelum jatuh tempo',
            ],
            [
                'name' => 'Pengingat di Hari Jatuh Tempo',
                'days_before_due' => 0,
                'whatsapp_template_id' => $templateH0?->id,
                'is_active' => true,
                'send_time' => '09:00:00',
                'priority' => 40,
                'description' => 'Reminder tepat di hari jatuh tempo',
            ],
            [
                'name' => 'Pengingat 1 Hari Setelah Jatuh Tempo (Overdue)',
                'days_before_due' => 1,
                'whatsapp_template_id' => $templateOverdue?->id,
                'is_active' => true,
                'send_time' => '09:00:00',
                'priority' => 50,
                'description' => 'Pengingat pertama untuk tagihan yang terlambat',
            ],
            [
                'name' => 'Pengingat 3 Hari Setelah Jatuh Tempo (Overdue)',
                'days_before_due' => 3,
                'whatsapp_template_id' => $templateOverdue?->id,
                'is_active' => true,
                'send_time' => '09:00:00',
                'priority' => 60,
                'description' => 'Pengingat kedua untuk tagihan yang terlambat',
            ],
            [
                'name' => 'Pengingat 7 Hari Setelah Jatuh Tempo (Overdue)',
                'days_before_due' => 7,
                'whatsapp_template_id' => $templateOverdue?->id,
                'is_active' => false, // Default inactive untuk yang ekstrim
                'send_time' => '09:00:00',
                'priority' => 70,
                'description' => 'Pengingat ketiga untuk tagihan yang sangat terlambat',
            ],
        ];

        foreach ($rules as $rule) {
            PaymentReminderRule::updateOrCreate(
                [
                    'days_before_due' => $rule['days_before_due'],
                    'priority' => $rule['priority'],
                ],
                $rule
            );
        }
    }
}
