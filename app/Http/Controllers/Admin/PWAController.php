<?php

namespace App\Http\Controllers\Admin;

use Exception;
use App\Services\PWAService;
use App\Http\Requests\PWARequest;
use App\Http\Resources\PWAResource;
use App\Http\Controllers\Controller;
use Illuminate\Routing\Controllers\Middleware;
use Illuminate\Routing\Controllers\HasMiddleware;

class PWAController extends AdminController implements HasMiddleware
{
    public PWAService $pwaService;

    public function __construct(PWAService $pwaService)
    {
        parent::__construct();
        $this->pwaService = $pwaService;
    }

    public static function middleware(): array
    {
        return [
            new Middleware('permission:settings', only: ['index']),
            new Middleware('permission:settings', only: ['update']),
        ];
    }

    public function index(): \Illuminate\Foundation\Application|\Illuminate\Http\Response|PWAResource|\Illuminate\Contracts\Foundation\Application|\Illuminate\Contracts\Routing\ResponseFactory
    {
        try {
            return new PWAResource($this->pwaService->list());
        } catch (Exception $exception) {
            return response(['status' => false, 'message' => $exception->getMessage()], 422);
        }
    }

    public function update(PWARequest $request): \Illuminate\Foundation\Application|\Illuminate\Http\Response|PWAResource|\Illuminate\Contracts\Foundation\Application|\Illuminate\Contracts\Routing\ResponseFactory
    {
        try {
            return new PWAResource($this->pwaService->update($request));
        } catch (Exception $exception) {
            return response(['status' => false, 'message' => $exception->getMessage()], 422);
        }
    }
}
