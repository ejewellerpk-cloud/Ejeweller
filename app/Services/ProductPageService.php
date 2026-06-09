<?php

namespace App\Services;

use App\Http\Requests\ProductPageRequest;
use App\Libraries\QueryExceptionLibrary;
use App\Services\SettingService;
use Dipokhalder\Settings\Facades\Settings;
use Exception;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Log;

class ProductPageService
{
    public const DEFAULT_SCROLL_SPEED = 3800;

    /**
     * @throws Exception
     */
    public function list(): array
    {
        try {
            return Settings::group('product_page')->all();
        } catch (Exception $exception) {
            Log::info($exception->getMessage());
            throw new Exception(QueryExceptionLibrary::message($exception), 422);
        }
    }

    /**
     * @throws Exception
     */
    public function update(ProductPageRequest $request): array
    {
        try {
            Settings::group('product_page')->set($request->validated());
            SettingService::clearCache();
            Artisan::call('optimize:clear');

            return $this->list();
        } catch (Exception $exception) {
            Log::info($exception->getMessage());
            throw new Exception(QueryExceptionLibrary::message($exception), 422);
        }
    }
}
