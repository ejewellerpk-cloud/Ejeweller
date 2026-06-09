<?php

namespace App\Services;

use Dipokhalder\Settings\Facades\Settings;

use Illuminate\Support\Facades\Cache;

class SettingService
{
    public function list(): array
    {
        return Cache::remember('global_settings_v2', 3600, function () {
            $array = [];
            $array = array_merge($array, Settings::group('company')->all());
            $array = array_merge($array, Settings::group('site')->all());
            $array = array_merge($array, Settings::group('shipping_setup')->all());
            $array = array_merge($array, Settings::group('theme')->all());
            $array = array_merge($array, Settings::group('otp')->all());
            $array = array_merge($array, Settings::group('social_media')->all());
            $array = array_merge($array, Settings::group('notification')->all());
            $array = array_merge($array, Settings::group('whatsapp')->all());
            $array = array_merge($array, Settings::group('top_bar')->all());
            $array = array_merge($array, Settings::group('product_page')->all());
            return array_merge($array, Settings::group('cookies')->all());
        });
    }

    public static function clearCache(): void
    {
        Cache::forget('global_settings');
        Cache::forget('global_settings_v2');
    }
}
