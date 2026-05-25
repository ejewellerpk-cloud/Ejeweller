<?php

use App\Analytics\Http\Controllers\IngestController;
use App\Analytics\Http\Middleware\AnalyticsCorsMiddleware;
use App\Analytics\Http\Middleware\AnalyticsSiteKeyMiddleware;
use Illuminate\Support\Facades\Route;

/*
| Analytics platform routes (ingestion + admin intelligence APIs)
*/

Route::prefix('analytics/v1')->group(function () {
    Route::options('/collect', fn () => response('', 204));

    Route::middleware([
        'throttle:' . config('analytics.ingest.rate_limit_per_minute', 1200) . ',1',
        AnalyticsSiteKeyMiddleware::class,
        AnalyticsCorsMiddleware::class,
    ])->group(function () {
        Route::post('/collect', [IngestController::class, 'collect']);
    });
});

Route::prefix('admin/setting/intelligence-analytics')
    ->middleware(['installed', 'apiKey', 'auth:sanctum', 'localization'])
    ->group(function () {
        Route::get('/', [\App\Analytics\Http\Controllers\Admin\IntelligenceSettingsController::class, 'show']);
        Route::match(['put', 'patch'], '/', [\App\Analytics\Http\Controllers\Admin\IntelligenceSettingsController::class, 'update']);
        Route::post('/regenerate-keys', [\App\Analytics\Http\Controllers\Admin\IntelligenceSettingsController::class, 'regenerateKeys']);
    });
