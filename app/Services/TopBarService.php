<?php

namespace App\Services;

use App\Http\Requests\TopBarRequest;
use App\Libraries\QueryExceptionLibrary;
use Dipokhalder\Settings\Facades\Settings;
use Exception;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Artisan;

class TopBarService
{
    /**
     * @throws Exception
     */
    public function list()
    {
        try {
            return Settings::group('top_bar')->all();
        } catch (Exception $exception) {
            Log::info($exception->getMessage());
            throw new Exception(QueryExceptionLibrary::message($exception), 422);
        }
    }

    /**
     * @throws Exception
     */
    public function update(TopBarRequest $request)
    {
        try {
            Settings::group('top_bar')->set($request->validated());
            Artisan::call('optimize:clear');
            return $this->list();
        } catch (Exception $exception) {
            Log::info($exception->getMessage());
            throw new Exception(QueryExceptionLibrary::message($exception), 422);
        }
    }
}
