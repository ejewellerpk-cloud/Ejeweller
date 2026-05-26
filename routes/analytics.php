<?php

use App\Analytics\Http\Controllers\Admin\IntelligenceAdvancedController;
use App\Analytics\Http\Controllers\Admin\IntelligenceProductController;
use App\Analytics\Http\Controllers\Admin\IntelligenceCommerceController;
use App\Analytics\Http\Controllers\Admin\IntelligenceDashboardController;
use App\Analytics\Http\Controllers\Admin\IntelligenceSettingsController;
use App\Analytics\Enterprise\Http\Controllers\BehaviorIngestController;
use App\Analytics\Enterprise\Http\Controllers\Admin\IntelligenceEnterpriseController;
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
        Route::post('/collect/behavior', [BehaviorIngestController::class, 'collect']);
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
        Route::get('/products/catalog', [IntelligenceProductController::class, 'catalog']);
        Route::get('/products/{productId}/insights', [IntelligenceProductController::class, 'show'])->where('productId', '[0-9]+');
        Route::get('/export', [IntelligenceCommerceController::class, 'exportReport']);
        Route::get('/roles', [IntelligenceCommerceController::class, 'roles']);
        Route::get('/settings', [IntelligenceSettingsController::class, 'show']);
        Route::prefix('enterprise')->group(function () {
            Route::get('/features', [IntelligenceEnterpriseController::class, 'features']);
            Route::get('/heatmaps/pages', [IntelligenceEnterpriseController::class, 'heatmapPages']);
            Route::get('/heatmaps/snapshot', [IntelligenceEnterpriseController::class, 'heatmapSnapshot']);
            Route::get('/replay/sessions', [IntelligenceEnterpriseController::class, 'replaySessions']);
            Route::get('/insights', [IntelligenceEnterpriseController::class, 'insights']);
            Route::post('/insights/generate', [IntelligenceEnterpriseController::class, 'generateInsights']);
            Route::patch('/insights/{id}/dismiss', [IntelligenceEnterpriseController::class, 'dismissInsight']);
            Route::get('/journey/visitors/{visitorUuid}', [IntelligenceEnterpriseController::class, 'journeyVisitor']);
            Route::get('/segments', [IntelligenceEnterpriseController::class, 'segments']);
            Route::get('/experiments', [IntelligenceEnterpriseController::class, 'experiments']);
            Route::get('/attribution', [IntelligenceEnterpriseController::class, 'attribution']);
            Route::get('/alerts/rules', [IntelligenceEnterpriseController::class, 'alertRules']);
            Route::get('/billing/plans', [IntelligenceEnterpriseController::class, 'billingPlans']);
        });
        Route::prefix('advanced')->group(function () {
            Route::get('/cohort-retention', [IntelligenceAdvancedController::class, 'cohortRetention']);
            Route::get('/rfm', [IntelligenceAdvancedController::class, 'rfm']);
            Route::get('/product-affinity', [IntelligenceAdvancedController::class, 'productAffinity']);
            Route::get('/payments', [IntelligenceAdvancedController::class, 'payments']);
            Route::get('/returns', [IntelligenceAdvancedController::class, 'returns']);
            Route::get('/geo-device', [IntelligenceAdvancedController::class, 'geoDevice']);
            Route::get('/hourly-heatmap', [IntelligenceAdvancedController::class, 'hourlyHeatmap']);
            Route::get('/inventory-forecast', [IntelligenceAdvancedController::class, 'inventoryForecast']);
            Route::get('/multi-store-compare', [IntelligenceAdvancedController::class, 'multiStoreCompare']);
            Route::get('/report-schedule', [IntelligenceAdvancedController::class, 'reportScheduleShow']);
            Route::post('/report-schedule', [IntelligenceAdvancedController::class, 'reportScheduleSave']);
            Route::get('/report-send-now', [IntelligenceAdvancedController::class, 'reportSendNow']);
        });

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
