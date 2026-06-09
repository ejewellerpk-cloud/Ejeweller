<?php

namespace App\Services;

use Exception;
use App\Http\Requests\AdminProductReviewRequest;
use App\Http\Requests\ChangeImageRequest;
use App\Http\Requests\PaginateRequest;
use App\Libraries\QueryExceptionLibrary;
use App\Models\ProductReview;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;


class ReviewService
{

    public object $review;
    protected array $reviewFilter = [
        'user_id',
        'product_id',
        'star',
        'except'
    ];

    /**
     * @throws Exception
     */
    public function list(PaginateRequest $request)
    {
        try {
            $requests    = $request->all();
            $method      = $request->get('paginate', 0) == 1 ? 'paginate' : 'get';
            $methodValue = $request->get('paginate', 0) == 1 ? $request->get('per_page', 10) : '*';
            $orderColumn = $request->get('order_column') ?? 'id';
            $orderType   = $request->get('order_type') ?? 'desc';

            return ProductReview::with('product')->where(function ($query) use ($requests) {
                foreach ($requests as $key => $request) {
                    if (in_array($key, $this->reviewFilter)) {
                        if ($key == "except") {
                            $explodes = explode('|', $request);
                            if (count($explodes)) {
                                foreach ($explodes as $explode) {
                                    $query->where('id', '!=', $explode);
                                }
                            }
                        } else {
                            if ($key == "user_id") {
                                $query->where($key, $request);
                            } else if ($key == "product_id") {
                                $query->where($key, $request);
                            } else if ($key == "star") {
                                $query->where($key, $request);
                            } else {
                                $query->where($key, 'like', '%' . $request . '%');
                            }
                        }
                    }
                }
            })->orderBy($orderColumn, $orderType)->$method($methodValue);
        } catch (Exception $exception) {
            Log::info($exception->getMessage());
            throw new Exception(QueryExceptionLibrary::message($exception), 422);
        }
    }

    /**
     * @throws Exception
     */
    public function featuredForHomepage(int $limit = 6)
    {
        try {
            return ProductReview::with(['user.addresses', 'product:id,name,slug'])
                ->where('star', '>=', 4)
                ->whereHas('product', fn ($query) => $query->where('status', \App\Enums\Status::ACTIVE))
                ->orderBy('star', 'desc')
                ->orderBy('id', 'desc')
                ->limit($limit)
                ->get();
        } catch (Exception $exception) {
            Log::info($exception->getMessage());
            throw new Exception(QueryExceptionLibrary::message($exception), 422);
        }
    }

    /**
     * @throws Exception
     */
    public function show(ProductReview $productReview): ProductReview
    {
        try {
            return $productReview->load('product');
        } catch (Exception $exception) {
            Log::info($exception->getMessage());
            throw new Exception(QueryExceptionLibrary::message($exception), 422);
        }
    }

    /**
     * @throws Exception
     */
    public function store(AdminProductReviewRequest $request): ProductReview
    {
        try {
            DB::transaction(function () use ($request) {
                $this->review = ProductReview::create($request->only([
                    'user_id',
                    'product_id',
                    'star',
                    'review',
                ]));

                $images = $request->file('images', []);
                foreach ($images as $image) {
                    if ($image) {
                        $this->review->addMedia($image)->toMediaCollection('product-review');
                    }
                }
            });

            return $this->review->load(['product', 'user']);
        } catch (Exception $exception) {
            DB::rollBack();
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

            return $productReview->load(['product', 'user']);
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

            return ProductReview::with(['product', 'user'])->find($productReview->id);
        } catch (Exception $exception) {
            Log::info($exception->getMessage());
            throw new Exception(QueryExceptionLibrary::message($exception), 422);
        }
    }
}
