<?php

namespace App\Analytics\Http\Controllers\Admin;

use App\Analytics\Http\Requests\IntelligenceSettingsRequest;
use App\Analytics\Services\AnalyticsSettingsService;
use App\Http\Controllers\Admin\AdminController;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;

class IntelligenceSettingsController extends AdminController implements HasMiddleware
{
    public function __construct(private readonly AnalyticsSettingsService $settings) {}

    public static function middleware(): array
    {
        return [
            new Middleware('permission:settings', only: ['show', 'update', 'regenerateKeys']),
        ];
    }

    public function show(Request $request): JsonResponse
    {
        try {
            return response()->json([
                'success' => true,
                'data' => $this->settings->get($request->user()->id),
            ]);
        } catch (\Throwable $e) {
            report($e);

            return response()->json([
                'success' => false,
                'message' => $e->getMessage() ?: 'Failed to load intelligence settings.',
            ], 422);
        }
    }

    public function update(IntelligenceSettingsRequest $request): JsonResponse
    {
        try {
            return response()->json([
                'success' => true,
                'data' => $this->settings->update($request->user()->id, $request->validated()),
                'message' => 'Intelligence analytics settings saved.',
            ]);
        } catch (\Throwable $e) {
            report($e);

            return response()->json([
                'success' => false,
                'message' => $e->getMessage() ?: 'Failed to save intelligence settings.',
            ], 422);
        }
    }

    public function regenerateKeys(Request $request): JsonResponse
    {
        try {
            $data = $this->settings->regenerateKeys($request->user()->id);

            return response()->json([
                'success' => true,
                'data' => $data,
                'message' => 'New keys generated. Copy the secret key now.',
            ]);
        } catch (\Throwable $e) {
            report($e);

            return response()->json([
                'success' => false,
                'message' => $e->getMessage() ?: 'Failed to regenerate keys.',
            ], 422);
        }
    }
}
