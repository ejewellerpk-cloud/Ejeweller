<?php

namespace App\Providers;

use App\Models\PersonalAccessToken;
use App\Models\Product;
use App\MediaLibrary\SafeFileManipulator;
use App\Observers\ProductMetaCatalogObserver;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Schema;
use Laravel\Sanctum\Sanctum;
use Spatie\MediaLibrary\Conversions\FileManipulator;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->bind(FileManipulator::class, SafeFileManipulator::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Sanctum::usePersonalAccessTokenModel(PersonalAccessToken::class);

        Schema::defaultStringLength(191);

        Product::observe(ProductMetaCatalogObserver::class);

        $storageLink = public_path('storage');
        $storageTarget = storage_path('app/public');
        if (!file_exists($storageLink)) {
            if (!is_dir($storageTarget)) {
                @mkdir($storageTarget, 0755, true);
            }
            @symlink($storageTarget, $storageLink);
        }

        $this->app->booted(function () {
            if (!$this->app->runningInConsole()) {
                return;
            }

            /** @var Schedule $schedule */
            $schedule = $this->app->make(Schedule::class);
            $schedule->command('postex:sync-statuses')
                ->everyFifteenMinutes()
                ->withoutOverlapping(10);

            $schedule->command('campaigns:process-recovery --enqueue')
                ->everyThirtyMinutes()
                ->withoutOverlapping(15);

            $schedule->command('meta-catalog:sync-stale')
                ->everyFifteenMinutes()
                ->withoutOverlapping(10);
        });
    }
}
