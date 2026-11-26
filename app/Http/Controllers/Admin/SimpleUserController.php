<?php

namespace App\Http\Controllers\Admin;

use Exception;
use App\Services\SimpleUserService;
use App\Http\Resources\SimpleUserResource;
use App\Http\Requests\PaginateRequest;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;

class SimpleUserController extends AdminController implements HasMiddleware
{
    private SimpleUserService $simpleUserService;

    public function __construct(SimpleUserService $simpleUserService)
    {
        parent::__construct();
        $this->simpleUserService = $simpleUserService;
    }

    public static function middleware(): array
    {
        return [
            new Middleware('permission:customers|administrators|employees|online-orders|pos'),
        ];
    }

    public function index(PaginateRequest $request)
    {
        try {
            return SimpleUserResource::collection($this->simpleUserService->list($request));
        } catch (Exception $exception) {
            return response(['status' => false, 'message' => $exception->getMessage()], 422);
        }
    }
}
