<?php

namespace App\Http\Controllers\Frontend;

use App\Http\Controllers\Controller;
use App\Models\Order;
use Illuminate\Http\Request;
use Exception;
use App\Http\Resources\OrderDetailsResource;
use Illuminate\Support\Facades\Log;

class TrackOrderController extends Controller
{
    public function track(Request $request)
    {
        try {
            $request->validate([
                'order_serial_no' => 'required|string|max:64',
            ]);

            $orderSerialNo = trim($request->input('order_serial_no'));

            $order = Order::query()
                ->where('order_serial_no', $orderSerialNo)
                ->with([
                    'shippingAddress',
                    'user',
                    'orderProducts.product',
                    'orderProducts.product.category',
                ])
                ->first();

            if (!$order) {
                throw new Exception('Order not found with the provided Order ID.', 404);
            }

            return new OrderDetailsResource($order);

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
