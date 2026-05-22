<?php

namespace App\Http\Controllers\Admin;

use Exception;
use App\Services\TopBarService;
use App\Http\Requests\TopBarRequest;
use App\Http\Resources\TopBarResource;
use Illuminate\Routing\Controllers\Middleware;
use Illuminate\Routing\Controllers\HasMiddleware;

class TopBarController extends AdminController implements HasMiddleware
{
    public TopBarService $topBarService;

    public function __construct(TopBarService $topBarService)
    {
        parent::__construct();
        $this->topBarService = $topBarService;
    }

    public static function middleware(): array
    {
        return [
            new Middleware('permission:settings', only: ['index']),
            new Middleware('permission:settings', only: ['update']),
        ];
    }

    public function index(): \Illuminate\Foundation\Application|\Illuminate\Http\Response|TopBarResource|\Illuminate\Contracts\Foundation\Application|\Illuminate\Contracts\Routing\ResponseFactory
    {
        try {
            return new TopBarResource($this->topBarService->list());
        } catch (Exception $exception) {
            return response(['status' => false, 'message' => $exception->getMessage()], 422);
        }
    }

    public function update(TopBarRequest $request): \Illuminate\Foundation\Application|\Illuminate\Http\Response|TopBarResource|\Illuminate\Contracts\Foundation\Application|\Illuminate\Contracts\Routing\ResponseFactory
    {
        try {
            return new TopBarResource($this->topBarService->update($request));
        } catch (Exception $exception) {
            return response(['status' => false, 'message' => $exception->getMessage()], 422);
        }
    }
}
