<?php

namespace App\Http\Controllers\Admin;

use Exception;
use App\Services\SettingService;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Log;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;

class CacheController extends AdminController implements HasMiddleware
{
    public static function middleware(): array
    {
        return [
            new Middleware('permission:settings'),
        ];
    }

    public function clear()
    {
        try {
            SettingService::clearCache();
            Artisan::call('optimize:clear');

            return response([
                'status'  => true,
                'message' => 'Cache cleared successfully.',
            ]);
        } catch (Exception $exception) {
            Log::info($exception->getMessage());

            return response([
                'status'  => false,
                'message' => $exception->getMessage(),
            ], 422);
        }
    }
}
