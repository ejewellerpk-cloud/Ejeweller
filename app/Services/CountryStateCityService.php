<?php

namespace App\Services;


use App\Enums\Status;
use App\Libraries\QueryExceptionLibrary;
use App\Models\City;
use App\Models\Country;
use App\Models\State;
use Exception;
use Illuminate\Support\Facades\Log;
use Dipokhalder\Settings\Facades\Settings;



class CountryStateCityService
{

    /**
     * @throws Exception
     */
    public function countries()
    {
        try {
            return Country::where('status', Status::ACTIVE)->orderBy('name', 'asc')->get();
        } catch (Exception $exception) {
            Log::info($exception->getMessage());
            throw new Exception(QueryExceptionLibrary::message($exception), 422);
        }
    }

    public function statesByCountry($country_name)
    {
        $country  = Country::where('name', '=', $country_name)->first();
        if (!$country) {
            return [];
        }
        try {
            return State::where('country_id', $country->id)->where('status', Status::ACTIVE)->orderBy('name', 'asc')->get();
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
            return City::where('state_id', $state->id)->where('status', Status::ACTIVE)->orderBy('name', 'asc')->get();
        } catch (Exception $exception) {
            Log::info($exception->getMessage());
            throw new Exception(QueryExceptionLibrary::message($exception), 422);
        }
    }
}
