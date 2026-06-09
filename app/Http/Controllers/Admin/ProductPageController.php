<?php

namespace App\Http\Controllers\Admin;

use App\Http\Requests\ProductPageRequest;
use App\Http\Resources\ProductPageResource;
use App\Services\ProductPageService;
use Exception;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;

class ProductPageController extends AdminController implements HasMiddleware
{
    public ProductPageService $productPageService;

    public function __construct(ProductPageService $productPageService)
    {
        parent::__construct();
        $this->productPageService = $productPageService;
    }

    public static function middleware(): array
    {
        return [
            new Middleware('permission:settings', only: ['index', 'update']),
        ];
    }

    public function index(): \Illuminate\Foundation\Application|\Illuminate\Http\Response|ProductPageResource|\Illuminate\Contracts\Foundation\Application|\Illuminate\Contracts\Routing\ResponseFactory
    {
        try {
            return new ProductPageResource($this->productPageService->list());
        } catch (Exception $exception) {
            return response(['status' => false, 'message' => $exception->getMessage()], 422);
        }
    }

    public function update(ProductPageRequest $request): \Illuminate\Foundation\Application|\Illuminate\Http\Response|ProductPageResource|\Illuminate\Contracts\Foundation\Application|\Illuminate\Contracts\Routing\ResponseFactory
    {
        try {
            return new ProductPageResource($this->productPageService->update($request));
        } catch (Exception $exception) {
            return response(['status' => false, 'message' => $exception->getMessage()], 422);
        }
    }
}
