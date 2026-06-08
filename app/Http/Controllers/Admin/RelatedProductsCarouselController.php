<?php

namespace App\Http\Controllers\Admin;

use App\Http\Requests\RelatedProductsCarouselRequest;
use App\Http\Resources\RelatedProductsCarouselResource;
use App\Services\RelatedProductsCarouselService;
use Exception;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;

class RelatedProductsCarouselController extends AdminController implements HasMiddleware
{
    public RelatedProductsCarouselService $relatedProductsCarouselService;

    public function __construct(RelatedProductsCarouselService $relatedProductsCarouselService)
    {
        parent::__construct();
        $this->relatedProductsCarouselService = $relatedProductsCarouselService;
    }

    public static function middleware(): array
    {
        return [
            new Middleware('permission:settings', only: ['index']),
            new Middleware('permission:settings', only: ['update']),
        ];
    }

    public function index(): \Illuminate\Foundation\Application|\Illuminate\Http\Response|RelatedProductsCarouselResource|\Illuminate\Contracts\Foundation\Application|\Illuminate\Contracts\Routing\ResponseFactory
    {
        try {
            return new RelatedProductsCarouselResource($this->relatedProductsCarouselService->list());
        } catch (Exception $exception) {
            return response(['status' => false, 'message' => $exception->getMessage()], 422);
        }
    }

    public function update(RelatedProductsCarouselRequest $request): \Illuminate\Foundation\Application|\Illuminate\Http\Response|RelatedProductsCarouselResource|\Illuminate\Contracts\Foundation\Application|\Illuminate\Contracts\Routing\ResponseFactory
    {
        try {
            return new RelatedProductsCarouselResource($this->relatedProductsCarouselService->update($request));
        } catch (Exception $exception) {
            return response(['status' => false, 'message' => $exception->getMessage()], 422);
        }
    }
}
