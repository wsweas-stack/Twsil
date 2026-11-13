<?php

namespace Tests\Feature;

use App\Models\Setting;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SettingsTest extends TestCase
{
    use RefreshDatabase;

    public function test_defaults_are_30_percent_driver_and_70_percent_company(): void
    {
        $this->assertSame('30', Setting::get(Setting::DRIVER_COMMISSION_RATE));
        $this->assertSame('70', Setting::get(Setting::COMPANY_SHARE_RATE));
    }

    public function test_rates_are_configurable(): void
    {
        Setting::set(Setting::DRIVER_COMMISSION_RATE, '25');
        Setting::set(Setting::COMPANY_SHARE_RATE, '75');

        $this->assertSame('25', Setting::get(Setting::DRIVER_COMMISSION_RATE));
        $this->assertSame('75', Setting::get(Setting::COMPANY_SHARE_RATE));
    }

    public function test_otp_defaults_apply(): void
    {
        $this->assertSame('10', Setting::get(Setting::OTP_TTL_MINUTES));
        $this->assertSame('3', Setting::get(Setting::OTP_MAX_ATTEMPTS));

        Setting::set(Setting::OTP_TTL_MINUTES, '15');
        $this->assertSame('15', Setting::get(Setting::OTP_TTL_MINUTES));
    }
}
