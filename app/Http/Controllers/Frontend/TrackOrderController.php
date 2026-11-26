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
                'phone'           => 'required|string|max:20',
            ]);

            $lookup = trim((string) $request->input('order_serial_no'));
            $phone  = preg_replace('/\D+/', '', (string) $request->input('phone'));

            if ($phone === '' || strlen($phone) < 7) {
                throw new Exception('A valid phone number is required to track this order.', 422);
            }

            $order = Order::query()
                ->where(function ($q) use ($lookup) {
                    $q->where('order_serial_no', $lookup)
                        ->orWhere('postex_tracking_number', $lookup);
                })
                ->with([
                    'shippingAddress',
                    'billingAddress',
                    'user',
                    'orderProducts.product',
                    'orderProducts.product.category',
                ])
                ->first();

            if (!$order) {
                // Same message whether missing or phone mismatch — avoid enumeration.
                throw new Exception('Order not found with the provided details.', 404);
            }

            $candidates = [];
            if ($order->shippingAddress?->phone) {
                $candidates[] = preg_replace('/\D+/', '', (string) $order->shippingAddress->phone);
            }
            if ($order->billingAddress?->phone) {
                $candidates[] = preg_replace('/\D+/', '', (string) $order->billingAddress->phone);
            }
            if ($order->user?->phone) {
                $candidates[] = preg_replace('/\D+/', '', (string) $order->user->phone);
            }

            $matched = false;
            foreach (array_filter($candidates) as $candidate) {
                if ($candidate === $phone || str_ends_with($candidate, $phone) || str_ends_with($phone, $candidate)) {
                    $matched = true;
                    break;
                }
            }

            if (!$matched) {
                throw new Exception('Order not found with the provided details.', 404);
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
