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

    public function index(Request $request): \Illuminate\Http\Resources\Json\AnonymousResourceCollection
    {
        $limit = min((int) $request->get('limit', 6), 12);

        return ProductReviewResource::collection(
            $this->reviewService->featuredForHomepage($limit)
        );
    }
}
