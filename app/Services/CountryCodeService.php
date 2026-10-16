<?php

namespace App\Services;

use App\Libraries\QueryExceptionLibrary;
use Exception;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use PragmaRX\Countries\Package\Countries;

class CountryCodeService
{
    private const LIST_CACHE_KEY = 'frontend_country_codes_list_v1';
    private const CALLING_MAP_CACHE_KEY = 'frontend_country_calling_map_v1';
    private const CACHE_TTL_SECONDS = 86400;

    /**
     * @throws Exception
     */
    public function list()
    {
        try {
            return Cache::remember(self::LIST_CACHE_KEY, self::CACHE_TTL_SECONDS, function () {
                $countryArray = [];
                $countries = Countries::all();
                foreach ($countries as $key => $country) {
                    if (isset($country['calling_codes'][0]) && isset($country['extra']['emoji'])) {
                        $countryArray[] = (object) [
                            'country_code' => $key,
                            'country_name' => $country['admin'] . ' (' . $key . ')',
                            'calling_code' => $country['calling_codes'][0] == '+1201' ? '+1' : ($country['calling_codes'][0] == '+73' ? '+7' : $country['calling_codes'][0]),
                            'flag_emoji' => $country['extra']['emoji'],
                        ];
                    }
                }

                return ['data' => $countryArray];
            });
        } catch (Exception $exception) {
            Log::info($exception->getMessage());
            throw new Exception(QueryExceptionLibrary::message($exception), 422);
        }
    }

    /**
     * @throws Exception
     */
    public function show($country)
    {
        try {
            return Cache::remember('frontend_country_code_show_v1:' . $country, self::CACHE_TTL_SECONDS, function () use ($country) {
                return Countries::where('cca3', $country)->first();
            });
        } catch (Exception $exception) {
            Log::info($exception->getMessage());
            throw new Exception(QueryExceptionLibrary::message($exception), 422);
        }
    }

    public function callingCode($callingCode)
    {
        try {
            $callingCodeArray = Cache::remember(self::CALLING_MAP_CACHE_KEY, self::CACHE_TTL_SECONDS, function () {
                $countries = Countries::all();

                return Collection::make($countries)
                    ->filter(function ($country) {
                        return isset($country['calling_codes'][0]) && isset($country['extra']['emoji']);
                    })
                    ->mapWithKeys(function ($country, $key) {
                        $code = $country['calling_codes'][0] == '+1201' ? '+1' : ($country['calling_codes'][0] == '+73' ? '+7' : $country['calling_codes'][0]);

                        return [
                            $code => (object) [
                                'country_code' => $key,
                                'calling_code' => $code,
                                'flag_emoji' => $country['extra']['emoji'],
                            ],
                        ];
                    })
                    ->toArray();
            });

            if (array_key_exists($callingCode, $callingCodeArray)) {
                return ['data' => $callingCodeArray[$callingCode]];
            }

            return ['data' => null];
        } catch (Exception $exception) {
            Log::info($exception->getMessage());
            throw new Exception(QueryExceptionLibrary::message($exception), 422);
        }
    }
}
