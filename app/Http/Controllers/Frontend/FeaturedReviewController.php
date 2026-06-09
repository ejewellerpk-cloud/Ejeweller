<?php

namespace App\Http\Controllers\Frontend;

use App\Http\Controllers\Controller;
use App\Http\Resources\ProductReviewResource;
use App\Services\ReviewService;
use Illuminate\Http\Request;

class FeaturedReviewController extends Controller
{
    public function __construct(private ReviewService $reviewService)
    {
    }

    public function index(Request $request): \Illuminate\Http\Resources\Json\AnonymousResourceCollection|\Illuminate\Http\JsonResponse
    {
        $limit = min((int) $request->get('limit', 12), 20);

        try {
            return ProductReviewResource::collection(
                $this->reviewService->featuredForHomepage($limit)
            );
        } catch (\Throwable $e) {
            report($e);

            return response()->json(['data' => []]);
        }
    }
}
