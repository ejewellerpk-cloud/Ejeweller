<?php

namespace App\Services;


use App\Enums\Status;
use App\Libraries\QueryExceptionLibrary;
use App\Models\City;
use App\Models\Country;
use App\Models\State;
use Exception;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Dipokhalder\Settings\Facades\Settings;



class CountryStateCityService
{
    private const CACHE_TTL_SECONDS = 3600;

    /**
     * @throws Exception
     */
    public function countries()
    {
        try {
            return Cache::remember('frontend_countries_active_v1', self::CACHE_TTL_SECONDS, function () {
                return Country::where('status', Status::ACTIVE)->orderBy('name', 'asc')->get();
            });
        } catch (Exception $exception) {
            Log::info($exception->getMessage());
            throw new Exception(QueryExceptionLibrary::message($exception), 422);
        }
    }

    public function statesByCountry($country_name)
    {
        $cacheKey = 'frontend_states_by_country_v1:' . md5(strtolower(trim((string) $country_name)));

        try {
            return Cache::remember($cacheKey, self::CACHE_TTL_SECONDS, function () use ($country_name) {
                $country = Country::where('name', '=', $country_name)->first();
                if (!$country) {
                    return [];
                }

                return State::where('country_id', $country->id)
                    ->where('status', Status::ACTIVE)
                    ->orderBy('name', 'asc')
                    ->get();
            });
        } catch (Exception $exception) {
            Log::info($exception->getMessage());
            throw new Exception(QueryExceptionLibrary::message($exception), 422);
        }
    }

    public function citiesByState($state_name)
    {
        $countryName = request('country');
        $state = null;

        if ($countryName) {
            $country = Country::where('name', '=', $countryName)->first();
            if ($country) {
                $state = State::where('name', '=', $state_name)
                    ->where('country_id', $country->id)
                    ->first();
            }
        }

        if (!$state) {
            $companyCountryCode = Settings::group('company')->get('company_country_code');
            if ($companyCountryCode) {
                $companyCountry = Country::where('code', '=', $companyCountryCode)->first();
                if ($companyCountry) {
                    $state = State::where('name', '=', $state_name)
                        ->where('country_id', $companyCountry->id)
                        ->first();
                }
            }
        }

        if (!$state) {
            $state = State::where('name', '=', $state_name)->first();
        }

        if (!$state) {
            return [];
        }

        try {
            $cacheKey = 'frontend_cities_by_state_v1:' . $state->id;
            return Cache::remember($cacheKey, self::CACHE_TTL_SECONDS, function () use ($state) {
                return City::where('state_id', $state->id)->where('status', Status::ACTIVE)->orderBy('name', 'asc')->get();
            });
        } catch (Exception $exception) {
            Log::info($exception->getMessage());
            throw new Exception(QueryExceptionLibrary::message($exception), 422);
        }
    }
}
