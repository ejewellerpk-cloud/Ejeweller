<?php

namespace App\Http\Controllers\Admin;

use Exception;
use App\Services\SettingService;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Log;

class CacheController extends AdminController
{
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
