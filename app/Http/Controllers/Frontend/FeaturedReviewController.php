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
        $limit = $request->filled('limit') ? (int) $request->get('limit') : null;

        try {
            return ProductReviewResource::collection(
                $this->reviewService->featuredForHomepage($limit > 0 ? $limit : null)
            );
        } catch (\Throwable $e) {
            report($e);

            return response()->json(['data' => []]);
        }
    }
}
