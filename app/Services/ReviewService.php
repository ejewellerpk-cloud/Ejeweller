<?php

namespace App\Services;

use Exception;
use App\Enums\Ask;
use App\Enums\ReviewStatus;
use App\Http\Requests\AdminProductReviewRequest;
use App\Http\Requests\ChangeImageRequest;
use App\Http\Requests\OrderStatusRequest;
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
        'status',
        'images_count',
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

            return $this->filteredQuery($requests)
                ->with(['product', 'user'])
                ->orderBy($orderColumn, $orderType)
                ->$method($methodValue);
        } catch (Exception $exception) {
            Log::info($exception->getMessage());
            throw new Exception(QueryExceptionLibrary::message($exception), 422);
        }
    }

    protected function filteredQuery(array $requests)
    {
        $query = ProductReview::query();

        foreach ($requests as $key => $value) {
            if ($value === null || $value === '' || !in_array($key, $this->reviewFilter, true)) {
                continue;
            }

            if ($key === 'except') {
                $explodes = explode('|', (string) $value);
                foreach ($explodes as $explode) {
                    if ($explode !== '') {
                        $query->where('id', '!=', $explode);
                    }
                }
                continue;
            }

            if ($key === 'images_count') {
                $this->applyImagesCountFilter($query, $value);
                continue;
            }

            if (in_array($key, ['user_id', 'product_id', 'star', 'status'], true)) {
                $query->where($key, $value);
            }
        }

        return $query;
    }

    protected function applyImagesCountFilter($query, $value): void
    {
        $mediaScope = function ($mediaQuery) {
            $mediaQuery->where('collection_name', 'product-review');
        };

        if ($value === 'none' || (string) $value === '0') {
            $query->whereDoesntHave('media', $mediaScope);
            return;
        }

        if ($value === '3plus' || $value === '3+') {
            $query->whereHas('media', $mediaScope, '>=', 3);
            return;
        }

        $count = (int) $value;
        if ($count > 0) {
            $query->whereHas('media', $mediaScope, '=', $count);
        }
    }

    /**
     * @throws Exception
     */
    public function bulkDestroy(array $ids): int
    {
        try {
            $deleted = 0;
            DB::transaction(function () use ($ids, &$deleted) {
                $reviews = ProductReview::whereIn('id', $ids)->get();
                foreach ($reviews as $review) {
                    $review->clearMediaCollection('product-review');
                    $review->delete();
                    $deleted++;
                }
            });

            return $deleted;
        } catch (Exception $exception) {
            DB::rollBack();
            Log::info($exception->getMessage());
            throw new Exception(QueryExceptionLibrary::message($exception), 422);
        }
    }

    /**
     * Delete every review matching the same filters as the list endpoint.
     *
     * @throws Exception
     */
    public function destroyFiltered(array $requests): int
    {
        try {
            $deleted = 0;
            DB::transaction(function () use ($requests, &$deleted) {
                $this->filteredQuery($requests)
                    ->orderBy('id')
                    ->chunkById(100, function ($reviews) use (&$deleted) {
                        foreach ($reviews as $review) {
                            $review->clearMediaCollection('product-review');
                            $review->delete();
                            $deleted++;
                        }
                    });
            });

            return $deleted;
        } catch (Exception $exception) {
            DB::rollBack();
            Log::info($exception->getMessage());
            throw new Exception(QueryExceptionLibrary::message($exception), 422);
        }
    }

    public function stats(): array
    {
        try {
            return [
                'all'      => ProductReview::count(),
                'pending'  => ProductReview::where('status', ReviewStatus::PENDING)->count(),
                'approved' => ProductReview::where('status', ReviewStatus::APPROVED)->count(),
                'rejected' => ProductReview::where('status', ReviewStatus::REJECTED)->count(),
            ];
        } catch (Exception $exception) {
            Log::info($exception->getMessage());
            throw new Exception(QueryExceptionLibrary::message($exception), 422);
        }
    }

    /**
     * @throws Exception
     */
    public function featuredForHomepage(?int $limit = null)
    {
        try {
            $query = ProductReview::with(['user.addresses', 'product:id,name,slug'])
                ->where('status', ReviewStatus::APPROVED)
                ->whereIn('star', [4, 5])
                ->whereHas('product', fn ($query) => $query->where('status', \App\Enums\Status::ACTIVE))
                ->orderBy('star', 'desc')
                ->orderBy('id', 'desc');

            if ($limit) {
                $query->limit($limit);
            }

            return $query->get();
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
                ]) + [
                    'status'               => ReviewStatus::APPROVED,
                    'is_verified_purchase' => Ask::NO,
                ]);

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

    /**
     * @throws Exception
     */
    public function update(AdminProductReviewRequest $request, ProductReview $productReview): ProductReview
    {
        try {
            DB::transaction(function () use ($request, $productReview) {
                $productReview->update($request->only([
                    'user_id',
                    'product_id',
                    'star',
                    'review',
                ]));

                $images = $request->file('images', []);
                foreach ($images as $image) {
                    if ($image) {
                        $productReview->addMedia($image)->toMediaCollection('product-review');
                    }
                }

                $this->review = $productReview;
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
    public function destroy(ProductReview $productReview): void
    {
        try {
            DB::transaction(function () use ($productReview) {
                $productReview->clearMediaCollection('product-review');
                $productReview->delete();
            });
        } catch (Exception $exception) {
            DB::rollBack();
            Log::info($exception->getMessage());
            throw new Exception(QueryExceptionLibrary::message($exception), 422);
        }
    }

    /**
     * @throws Exception
     */
    public function changeStatus(ProductReview $productReview, OrderStatusRequest $request): ProductReview
    {
        try {
            $status = (int) $request->status;
            if (!in_array($status, [ReviewStatus::PENDING, ReviewStatus::APPROVED, ReviewStatus::REJECTED], true)) {
                throw new Exception('Invalid review status.', 422);
            }

            $productReview->status = $status;
            $productReview->save();

            return $productReview->load(['product', 'user']);
        } catch (Exception $exception) {
            Log::info($exception->getMessage());
            throw new Exception(QueryExceptionLibrary::message($exception), 422);
        }
    }
}
