<?php

namespace App\Services;

use App\Enums\Activity;
use App\Http\Requests\RelatedProductsCarouselRequest;
use App\Libraries\QueryExceptionLibrary;
use Dipokhalder\Settings\Facades\Settings;
use Exception;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Log;

class RelatedProductsCarouselService
{
    public const DEFAULT_AUTOPLAY_DELAY = 3800;

    /**
     * @throws Exception
     */
    public function list(): array
    {
        try {
            $settings = Settings::group('related_products_carousel')->all();

            return [
                'related_products_carousel_status' => isset($settings['related_products_carousel_status'])
                    ? (int) $settings['related_products_carousel_status']
                    : Activity::ENABLE,
                'related_products_carousel_speed' => isset($settings['related_products_carousel_speed'])
                    ? (int) $settings['related_products_carousel_speed']
                    : self::DEFAULT_AUTOPLAY_DELAY,
            ];
        } catch (Exception $exception) {
            Log::info($exception->getMessage());
            throw new Exception(QueryExceptionLibrary::message($exception), 422);
        }
    }

    /**
     * @throws Exception
     */
    public function update(RelatedProductsCarouselRequest $request): array
    {
        try {
            Settings::group('related_products_carousel')->set($request->validated());
            SettingService::clearCache();
            Artisan::call('optimize:clear');

            return $this->list();
        } catch (Exception $exception) {
            Log::info($exception->getMessage());
            throw new Exception(QueryExceptionLibrary::message($exception), 422);
        }
    }
}
