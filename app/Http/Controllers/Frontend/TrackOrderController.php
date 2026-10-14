<?php

namespace App\Http\Controllers\Frontend;

use App\Http\Controllers\Controller;
use App\Http\Resources\OrderDetailsResource;
use App\Models\Order;
use App\Services\PostEx\PostExOrderService;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class TrackOrderController extends Controller
{
    public function __construct(
        protected PostExOrderService $postExOrderService
    ) {}

    public function track(Request $request)
    {
        try {
            $request->validate([
                'order_serial_no' => 'required|string|max:64',
            ]);

            $lookup = trim((string) $request->input('order_serial_no'));

            $order = Order::query()
                ->where(function ($q) use ($lookup) {
                    $q->where('order_serial_no', $lookup)
                        ->orWhere('postex_tracking_number', $lookup);
                })
                ->with([
                    'shippingAddress',
                    'user',
                    'orderProducts.product',
                    'orderProducts.product.category',
                ])
                ->first();

            if (!$order) {
                throw new Exception('Order not found with the provided Order ID / tracking number.', 404);
            }

            $resource = (new OrderDetailsResource($order))->resolve();
            $resource['postex_tracking'] = $this->postExOrderService->publicTrackingForOrder($order);

            return response(['data' => $resource]);

        } catch (Exception $exception) {
            Log::info($exception->getMessage());
            $statusCode = $exception->getCode() ?: 422;
            if (!is_numeric($statusCode) || $statusCode < 100 || $statusCode >= 600) {
                $statusCode = 422;
            }
            return response(['status' => false, 'message' => $exception->getMessage()], $statusCode);
        }
    }
}
