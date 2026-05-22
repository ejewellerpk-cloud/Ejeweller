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
            
            // Ensure newly added keys exist in DB so the package can update them
            // Ensure newly added keys exist in DB and update them manually
            $newKeys = ['site_facebook_pixel_id', 'site_facebook_capi_token', 'site_facebook_capi_status'];
            foreach ($newKeys as $key) {
                if (isset($data[$key])) {
                    $exists = \Illuminate\Support\Facades\DB::table(config('settings.repositories.database.table', 'settings'))
                        ->where('group', 'site')
                        ->where('key', $key)
                        ->exists();
                        
                    if (!$exists) {
                        \Illuminate\Support\Facades\DB::table(config('settings.repositories.database.table', 'settings'))->insert([
                            'group' => 'site',
                            'key' => $key,
                            'payload' => json_encode($data[$key]),
                            'created_at' => now(),
                            'updated_at' => now(),
                        ]);
                    } else {
                        \Illuminate\Support\Facades\DB::table(config('settings.repositories.database.table', 'settings'))
                            ->where('group', 'site')
                            ->where('key', $key)
                            ->update([
                                'payload' => json_encode($data[$key]),
                                'updated_at' => now(),
                            ]);
                    }
                }
            }

            Settings::group('site')->set($data);

            $this->envService->addData([
                'APP_DEBUG'              => $app_debug == Activity::ENABLE ? 'true' : 'false',
                'TIMEZONE'               => $request->site_default_timezone,
                'CURRENCY'               => $currency_code,
                'CURRENCY_SYMBOL'        => $currency_symbol,
                'CURRENCY_POSITION'      => $request->site_currency_position,
                'CURRENCY_DECIMAL_POINT' => $request->site_digit_after_decimal_point,
                'DATE_FORMAT'            => $request->site_date_format,
                'TIME_FORMAT'            => $request->site_time_format,
                'NON_PURCHASE_QUANTITY'  => $request->site_non_purchase_product_maximum_quantity
            ]);

            Artisan::call('optimize:clear');
            return $this->list();
        } catch (Exception $exception) {
            Log::info($exception->getMessage());
            throw new Exception(QueryExceptionLibrary::message($exception), 422);
        }
    }
}
