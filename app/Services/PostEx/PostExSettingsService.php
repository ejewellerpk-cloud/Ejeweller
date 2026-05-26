<?php

namespace App\Services\PostEx;

use App\Enums\Activity;
use App\Http\Requests\PostExRequest;
use App\Libraries\QueryExceptionLibrary;
use Dipokhalder\Settings\Facades\Settings;
use Exception;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class PostExSettingsService
{
    /**
     * @throws Exception
     */
    public function list(): array
    {
        try {
            $settings = Settings::group('postex')->all();

            return array_merge($this->defaults(), $settings);
        } catch (Exception $exception) {
            Log::info($exception->getMessage());
            throw new Exception(QueryExceptionLibrary::message($exception), 422);
        }
    }

    public function isEnabled(): bool
    {
        $settings = $this->list();

        return (int) ($settings['postex_status'] ?? Activity::DISABLE) === Activity::ENABLE
            && !empty($settings['postex_api_token']);
    }

    /**
     * @throws Exception
     */
    public function update(PostExRequest $request): array
    {
        try {
            Settings::group('postex')->set($request->validated());
            Cache::forget('global_settings');
            Cache::forget('global_settings_v2');

            return $this->list();
        } catch (Exception $exception) {
            Log::info($exception->getMessage());
            throw new Exception(QueryExceptionLibrary::message($exception), 422);
        }
    }

    protected function defaults(): array
    {
        return [
            'postex_status'              => Activity::DISABLE,
            'postex_api_token'           => '',
            'postex_base_url'            => config('postex.base_url'),
            'postex_pickup_address_code' => '',
            'postex_default_order_type'  => 'Normal',
            'postex_invoice_division'    => 1,
            'postex_booking_weight'      => '',
            'postex_auto_ship'           => Activity::DISABLE,
        ];
    }
}
