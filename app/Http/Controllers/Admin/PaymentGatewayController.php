<?php

namespace App\Http\Controllers\Admin;


use Exception;
use Illuminate\Http\Request;
use App\Http\Requests\PaginateRequest;
use App\Services\PaymentGatewayService;
use App\Http\Resources\PaymentGatewayResource;
use Illuminate\Routing\Controllers\Middleware;
use Illuminate\Routing\Controllers\HasMiddleware;

class PaymentGatewayController extends AdminController implements HasMiddleware
{
    private PaymentGatewayService $paymentGatewayService;

    public function __construct(PaymentGatewayService $paymentGatewayService)
    {
        parent::__construct();
        $this->paymentGatewayService = $paymentGatewayService;
    }

    public static function middleware(): array
    {
        return [
            new Middleware('permission:settings|transactions|sales-report', only: ['index']),
            new Middleware('permission:settings', only: ['update']),
        ];
    }
    public function index(PaginateRequest $request): \Illuminate\Http\Response|\Illuminate\Http\Resources\Json\AnonymousResourceCollection|\Illuminate\Contracts\Foundation\Application|\Illuminate\Contracts\Routing\ResponseFactory
    {
        try {
            return PaymentGatewayResource::collection($this->paymentGatewayService->list($request));
        } catch (Exception $exception) {
            return response(['status' => false, 'message' => $exception->getMessage()], 422);
        }
    }

    public function update(Request $request): PaymentGatewayResource|\Illuminate\Http\Response|\Illuminate\Contracts\Foundation\Application|\Illuminate\Contracts\Routing\ResponseFactory
    {
        $slug = (string) $request->input('payment_type');
        if ($slug === '') {
            return response(['status' => false, 'message' => 'Payment type is required.'], 422);
        }

        $className = 'App\\Http\\PaymentGateways\\Requests\\' . ucfirst($slug);
        if (!class_exists($className)) {
            return response(['status' => false, 'message' => 'Invalid payment gateway.'], 422);
        }

        $gateway            = new $className;
        $validationRequests = $request->validate($gateway->rules());

        try {
            return new PaymentGatewayResource($this->paymentGatewayService->update($validationRequests, $slug));
        } catch (Exception $exception) {
            return response(['status' => false, 'message' => $exception->getMessage()], 422);
        }
    }
}
