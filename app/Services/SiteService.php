<?php

namespace App\Services;


use App\Enums\Activity;
use App\Http\Requests\SiteRequest;
use App\Libraries\QueryExceptionLibrary;
use App\Models\Currency;
use Dipokhalder\EnvEditor\EnvEditor;
use Exception;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Log;
use Dipokhalder\Settings\Facades\Settings;

class SiteService
{
    public EnvEditor $envService;

    public function __construct(EnvEditor $envEditor)
    {
        $this->envService = $envEditor;
    }

    /**
     * @throws Exception
     */
    public function list()
    {
        try {
            return Settings::group('site')->all();
        } catch (Exception $exception) {
            Log::info($exception->getMessage());
            throw new Exception(QueryExceptionLibrary::message($exception), 422);
        }
    }

    /**
     * @throws Exception
     */
    public function update(SiteRequest $request)
    {
        try {
            $currency = Currency::find($request->site_default_currency);
            $currency_symbol = $currency ? $currency->symbol : '$';
            $currency_code = $currency ? $currency->code : 'USD';
            $app_debug = $this->envService->getValue('DEMO') ? Activity::DISABLE : $request->site_app_debug;

            $data = $request->validated();
            $data['site_default_currency_symbol'] = $currency_symbol;
            $data['site_app_debug'] = $app_debug;

            // Normalize checkout country to a plain string (vue-select can send odd shapes).
            if (array_key_exists('site_default_checkout_country', $data)) {
                $country = $data['site_default_checkout_country'];
                if (is_array($country)) {
                    $country = $country['$value'] ?? $country['name'] ?? null;
                }
                $country = is_string($country) ? trim($country) : '';
                $data['site_default_checkout_country'] = $country !== '' ? $country : 'Pakistan';
            }

            // Always write via the settings package so payload stays {$value,$cast}.
            // Raw json_encode() broke reads/display for newer keys like checkout country.
            Settings::group('site')->set($data);
            SettingService::clearCache();

            $this->envService->addData([
                'APP_DEBUG'              => $app_debug == Activity::ENABLE ? 'true' : 'false',
                'TIMEZONE'               => $request->site_default_timezone,
                'CURRENCY'               => $currency_code,
                'CURRENCY_SYMBOL'        => $currency_symbol,
                'CURRENCY_POSITION'      => $request->site_currency_position,
                'CURRENCY_DECIMAL_POINT' => $request->site_digit_after_decimal_point,
                'DATE_FORMAT'            => $request->site_date_format,
                'TIME_FORMAT'            => $request->site_time_format,
            ]);

            Artisan::call('optimize:clear');
            return $this->list();
        } catch (Exception $exception) {
            Log::info($exception->getMessage());
            throw new Exception(QueryExceptionLibrary::message($exception), 422);
        }
    }
}
