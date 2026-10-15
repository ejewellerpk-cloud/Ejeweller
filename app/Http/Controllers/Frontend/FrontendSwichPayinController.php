<?php

namespace App\Http\Controllers\Frontend;

use App\Enums\PaymentStatus;
use App\Http\Controllers\Controller;
use App\Http\PaymentGateways\Gateways\Swich;
use App\Models\Order;
use App\Services\PaymentManagerService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class FrontendSwichPayinController extends Controller
{
    public function __construct(private PaymentManagerService $paymentManagerService)
    {
    }

    public function options(): JsonResponse
    {
        /** @var Swich $gateway */
        $gateway = $this->paymentManagerService->gateway(Swich::SLUG)->gateway;

        return response()->json([
            'data' => [
                'ewallet' => method_exists($gateway, 'ewalletEnabled') ? $gateway->ewalletEnabled() : true,
                'biller' => method_exists($gateway, 'billerEnabled') ? $gateway->billerEnabled() : true,
            ],
        ]);
    }

    public function start(Order $order, Request $request): JsonResponse
    {
        if (!$this->ownsOrder($order)) {
            return response()->json(['status' => false, 'message' => 'Unauthorized'], 403);
        }

        if ((int) $order->payment_status === PaymentStatus::PAID) {
            return response()->json([
                'ok' => true,
                'status' => 'paid',
                'phase' => 'paid',
                'order_id' => $order->id,
            ]);
        }

        /** @var Swich $gateway */
        $gateway = $this->paymentManagerService->gateway(Swich::SLUG)->gateway;
        if (!method_exists($gateway, 'startCheckout')) {
            return response()->json(['ok' => false, 'message' => 'Swich gateway unavailable'], 500);
        }

        $result = $gateway->startCheckout($order, $request);
        $statusCode = ($result['ok'] ?? false) ? 200 : 422;

        return response()->json($result, $statusCode);
    }

    public function initiate(Order $order): JsonResponse
    {
        if (!$this->ownsOrder($order)) {
            return response()->json(['status' => false, 'message' => 'Unauthorized'], 403);
        }

        /** @var Swich $gateway */
        $gateway = $this->paymentManagerService->gateway(Swich::SLUG)->gateway;
        if (!method_exists($gateway, 'initiatePurchase')) {
            return response()->json(['ok' => false, 'status' => 'error'], 500);
        }

        return response()->json($gateway->initiatePurchase($order));
    }

    public function status(Order $order): JsonResponse
    {
        if (!$this->ownsOrder($order)) {
            return response()->json(['status' => false, 'message' => 'Unauthorized'], 403);
        }

        $controller = app(SwichPayinController::class);

        return $controller->status($order);
    }

    protected function ownsOrder(Order $order): bool
    {
        $user = auth('sanctum')->user();
        if (!$user) {
            return false;
        }

        return (int) $order->user_id === (int) $user->id;
    }
}
