<?php

namespace App\Services;

use Exception;
use App\Enums\Ask;
use App\Enums\OrderStatus;
use App\Enums\ReviewStatus;
use App\Models\Order;
use App\Models\ProductReview;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;
use App\Http\Requests\ChangeImageRequest;
use App\Http\Requests\ProductReviewRequest;
use App\Libraries\QueryExceptionLibrary;

class ProductReviewService
{
    public object $productReview;

    /**
     * @throws Exception
     */
    public function store(ProductReviewRequest $request)
    {
        try {
            DB::transaction(function () use ($request) {
                $userId    = Auth::user()->id;
                $productId = (int) $request->product_id;
                $verified  = $this->hasVerifiedPurchase($userId, $productId) ? Ask::YES : Ask::NO;

                $this->productReview = ProductReview::create($request->validated() + [
                    'user_id'              => $userId,
                    'status'               => ReviewStatus::PENDING,
                    'is_verified_purchase' => $verified,
                ]);

                if ($request->images) {
                    foreach ($request->images as $image) {
                        $this->productReview->addMedia($image)->toMediaCollection('product-review');
                    }
                }
            });
            return $this->productReview;
        } catch (Exception $exception) {
            DB::rollBack();
            Log::info($exception->getMessage());
            throw new Exception(QueryExceptionLibrary::message($exception), 422);
        }
    }

    public function update(ProductReviewRequest $request, ProductReview $productReview)
    {
        try {
            DB::transaction(function () use ($request, $productReview) {
                $productReview->update($request->validated());
            });
            return $productReview;
        } catch (Exception $exception) {
            DB::rollBack();
            Log::info($exception->getMessage());
            throw new Exception(QueryExceptionLibrary::message($exception), 422);
        }
    }

    public function show(ProductReview $productReview)
    {
        try {
            if ($productReview->user_id === Auth::user()->id) {
                return $productReview;
            } else {
                return  [];
            }
        } catch (Exception $exception) {
            Log::info($exception->getMessage());
            throw new Exception(QueryExceptionLibrary::message($exception), 422);
        }
    }

    /**
     * @throws Exception
     */
    public function uploadImage(ChangeImageRequest $request, ProductReview $productReview): ProductReview
    {
        try {
            $productReview->addMedia($request->image)->toMediaCollection('product-review');
            return $productReview;
        } catch (Exception $exception) {
            Log::info($exception->getMessage());
            throw new Exception(QueryExceptionLibrary::message($exception), 422);
        }
    }

    /**
     * @throws Exception
     */
    public function deleteImage(ProductReview $productReview, $index): ProductReview
    {
        try {
            $images = $productReview->getMedia('product-review');
            if (isset($images[$index])) {
                $images[$index]->delete();
            }
            return ProductReview::find($productReview->id);
        } catch (Exception $exception) {
            Log::info($exception->getMessage());
            throw new Exception(QueryExceptionLibrary::message($exception), 422);
        }
    }

    private function hasVerifiedPurchase(int $userId, int $productId): bool
    {
        return Order::query()
            ->where('user_id', $userId)
            ->whereNotIn('status', [OrderStatus::CANCELED, OrderStatus::REJECTED])
            ->whereHas('orderProducts', function ($query) use ($productId) {
                $query->where('product_id', $productId);
            })
            ->exists();
    }
}
