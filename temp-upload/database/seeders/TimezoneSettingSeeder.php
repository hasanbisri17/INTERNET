<?php

namespace Database\Seeders;

use App\Models\Setting;
use Illuminate\Database\Seeder;

class TimezoneSettingSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Set default timezone to Asia/Jakarta (WIB) if not already set
        $existingTimezone = Setting::where('key', 'app_timezone')->first();
        
        if (!$existingTimezone) {
            Setting::create([
                'key' => 'app_timezone',
                'value' => 'Asia/Jakarta',
            ]);
            
            $this->command->info('✅ Default timezone set to Asia/Jakarta (WIB)');
        } else {
            $this->command->info('ℹ️  Timezone already configured: ' . $existingTimezone->value);
        }
    }
}

