<?php

use App\Analytics\Http\Controllers\Admin\IntelligenceCommerceController;
use App\Analytics\Http\Controllers\Admin\IntelligenceDashboardController;
use App\Analytics\Http\Controllers\Admin\IntelligenceSettingsController;
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

Route::prefix('admin/intelligence')
    ->middleware(['installed', 'apiKey', 'auth:sanctum', 'localization'])
    ->group(function () {
        Route::get('/sites', [IntelligenceDashboardController::class, 'sites']);
        Route::get('/overview', [IntelligenceDashboardController::class, 'overview']);
        Route::get('/daily-series', [IntelligenceDashboardController::class, 'dailySeries']);
        Route::get('/realtime', [IntelligenceDashboardController::class, 'realtime']);
        Route::get('/funnel', [IntelligenceDashboardController::class, 'funnel']);
        Route::get('/sources', [IntelligenceDashboardController::class, 'sources']);
        Route::get('/products', [IntelligenceDashboardController::class, 'products']);
        Route::get('/export', [IntelligenceCommerceController::class, 'exportReport']);
        Route::get('/roles', [IntelligenceCommerceController::class, 'roles']);
        Route::get('/settings', [IntelligenceSettingsController::class, 'show']);
        Route::prefix('commerce')->group(function () {
            Route::get('/totals', [IntelligenceCommerceController::class, 'totals']);
            Route::get('/order-statistics', [IntelligenceCommerceController::class, 'orderStatistics']);
            Route::get('/sales-summary', [IntelligenceCommerceController::class, 'salesSummary']);
            Route::get('/order-summary', [IntelligenceCommerceController::class, 'orderSummary']);
            Route::get('/customer-activity', [IntelligenceCommerceController::class, 'customerActivity']);
            Route::get('/top-customers', [IntelligenceCommerceController::class, 'topCustomers']);
            Route::get('/top-selling-products', [IntelligenceCommerceController::class, 'topSellingProducts']);
            Route::get('/low-stock', [IntelligenceCommerceController::class, 'lowStock']);
            Route::get('/cart-insights', [IntelligenceCommerceController::class, 'cartInsights']);
            Route::get('/recent-orders', [IntelligenceCommerceController::class, 'recentOrders']);
        });
    });

Route::prefix('admin/setting/intelligence-analytics')
    ->middleware(['installed', 'apiKey', 'auth:sanctum', 'localization'])
    ->group(function () {
        Route::get('/', [\App\Analytics\Http\Controllers\Admin\IntelligenceSettingsController::class, 'show']);
        Route::match(['put', 'patch'], '/', [\App\Analytics\Http\Controllers\Admin\IntelligenceSettingsController::class, 'update']);
        Route::post('/regenerate-keys', [\App\Analytics\Http\Controllers\Admin\IntelligenceSettingsController::class, 'regenerateKeys']);
    });
