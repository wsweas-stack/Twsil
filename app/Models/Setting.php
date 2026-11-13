<?php

namespace App\Models;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;

class Setting
{
    public const DRIVER_COMMISSION_RATE = 'driver_commission_rate'; // percent, default 30
    public const COMPANY_SHARE_RATE = 'company_share_rate';         // percent, default 70
    public const OTP_TTL_MINUTES = 'otp_ttl_minutes';               // default 10
    public const OTP_MAX_ATTEMPTS = 'otp_max_attempts';             // default 3

    private const DEFAULTS = [
        self::DRIVER_COMMISSION_RATE => '30',
        self::COMPANY_SHARE_RATE => '70',
        self::OTP_TTL_MINUTES => '10',
        self::OTP_MAX_ATTEMPTS => '3',
    ];

    /** All keys/values as a cached collection. */
    public static function all(): Collection
    {
        // Store plain key/value arrays in cache (never objects) so unserialization
        // cannot produce __PHP_Incomplete_Class if class names change later.
        $data = Cache::rememberForever('settings.all', function () {
            $db = \App\Models\DbSetting::query()->pluck('value', 'key');

            return collect(self::DEFAULTS)->merge($db)->all();
        });

        return collect(is_array($data) ? $data : self::DEFAULTS);
    }

    public static function get(string $key, ?string $default = null): ?string
    {
        return self::all()->get($key, $default);
    }

    public static function set(string $key, string $value): void
    {
        \App\Models\DbSetting::updateOrCreate(['key' => $key], ['value' => $value]);
        Cache::forget('settings.all');
    }
}
