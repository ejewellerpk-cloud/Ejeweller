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

        $storageLink = public_path('storage');
        $storageTarget = storage_path('app/public');
        if (!file_exists($storageLink)) {
            if (!is_dir($storageTarget)) {
                @mkdir($storageTarget, 0755, true);
            }
            @symlink($storageTarget, $storageLink);
        }
    }
}
