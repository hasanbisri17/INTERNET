<?php

namespace Database\Seeders;

use App\Models\WhatsAppSetting;
use Illuminate\Database\Seeder;

class WhatsAppSettingSeeder extends Seeder
{
    public function run(): void
    {
        WhatsAppSetting::create([
            'api_token' => env('GOWA_API_TOKEN', ''),
            'api_url' => env('GOWA_API_URL', 'http://localhost:3000'),
            'default_country_code' => '62',
            'is_active' => true,
        ]);
    }
}
