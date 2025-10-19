<?php

namespace Database\Seeders;

use App\Models\WhatsAppSetting;
use Illuminate\Database\Seeder;

class WhatsAppSettingSeeder extends Seeder
{
    public function run(): void
    {
        WhatsAppSetting::create([
            'api_token' => '',
            'api_url' => 'https://waha-pj8tw4c4otz1.wax.biz.id',
            'default_country_code' => '62',
            'is_active' => true,
        ]);
    }
}
