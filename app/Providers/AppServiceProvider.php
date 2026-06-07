<?php

namespace App\Providers;

use App\Models\PersonalAccessToken;
use App\MediaLibrary\SafeFileManipulator;
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

        if (!file_exists(public_path('storage'))) {
            @symlink(storage_path('app/public'), public_path('storage'));
        }
    }
}
