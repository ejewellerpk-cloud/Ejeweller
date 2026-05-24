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
                'order_serial_no' => 'required|string',
                'phone_or_email' => 'required|string',
            ]);

            $orderSerialNo = $request->input('order_serial_no');
            $phoneOrEmail = $request->input('phone_or_email');

            // Find order by order_serial_no
            $order = Order::where('order_serial_no', $orderSerialNo)
                          ->with(['shippingAddress'])
                          ->first();

            if (!$order) {
                throw new Exception('Order not found with the provided Order ID.', 404);
            }

            // Verify phone or email from order address or user
            $address = $order->shippingAddress;
            if ($address) {
                if ($address->phone !== $phoneOrEmail && $address->email !== $phoneOrEmail && $order->user?->phone !== $phoneOrEmail && $order->user?->email !== $phoneOrEmail) {
                    throw new Exception('Order ID found, but Phone/Email does not match our records.', 403);
                }
            } else {
                if ($order->user?->phone !== $phoneOrEmail && $order->user?->email !== $phoneOrEmail) {
                    throw new Exception('Order ID found, but Phone/Email does not match our records.', 403);
                }
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
