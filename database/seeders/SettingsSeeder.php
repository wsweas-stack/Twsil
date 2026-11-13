<?php

namespace Database\Seeders;

use App\Models\Setting;
use Illuminate\Database\Seeder;

class SettingsSeeder extends Seeder
{
    public function run(): void
    {
        Setting::set(Setting::DRIVER_COMMISSION_RATE, '30');
        Setting::set(Setting::COMPANY_SHARE_RATE, '70');
        Setting::set(Setting::OTP_TTL_MINUTES, '10');
        Setting::set(Setting::OTP_MAX_ATTEMPTS, '3');
    }
}
