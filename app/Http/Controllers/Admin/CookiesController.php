<?php

namespace App\Http\Controllers\Admin;

use App\Http\Requests\CookiesRequest;
use App\Http\Resources\CookiesResource;
use App\Services\CookiesService;
use Exception;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;

class CookiesController extends AdminController implements HasMiddleware
{
    private CookiesService $cookiesService;

    public function __construct(CookiesService $cookiesService)
    {
        parent::__construct();
        $this->cookiesService = $cookiesService;
    }

    public static function middleware(): array
    {
        return [
            new Middleware('permission:settings', only: ['index']),
            new Middleware('permission:settings', only: ['update']),
        ];
    }

    public function index(): \Illuminate\Http\Response|CookiesResource|\Illuminate\Contracts\Foundation\Application|\Illuminate\Contracts\Routing\ResponseFactory
    {
        try {
            return new CookiesResource($this->cookiesService->list());
        } catch (Exception $exception) {
            return response(['status' => false, 'message' => $exception->getMessage()], 422);
        }
    }

    public function update(CookiesRequest $request): \Illuminate\Http\Response|CookiesResource|\Illuminate\Contracts\Foundation\Application|\Illuminate\Contracts\Routing\ResponseFactory
    {
        try {
            return new CookiesResource($this->cookiesService->update($request));
        } catch (Exception $exception) {
            return response(['status' => false, 'message' => $exception->getMessage()], 422);
        }
    }
}
