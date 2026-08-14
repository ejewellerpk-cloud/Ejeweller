<?php

namespace App\Http\Controllers\Frontend;

use App\Enums\PaymentStatus;
use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\PaymentGateway;
use App\Models\SwichPayinTransaction;
use App\Models\ThemeSetting;
use App\Services\PaymentManagerService;
use Dipokhalder\Settings\Facades\Settings;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class SwichPayinController extends Controller
{
    public function __construct(private PaymentManagerService $paymentManagerService)
    {
    }

    public function callback(Request $request): JsonResponse
    {
        $customerTransactionId = (string) $request->input('CustomerTransactionId', $request->input('customerTransactionId', ''));
        $record = SwichPayinTransaction::where('customer_transaction_id', $customerTransactionId)->first();
        if (!$record) {
            Log::warning('Swich PayIn callback unknown transaction', $request->all());

            return response()->json(['status' => 'failed'], 404);
        }

        try {
            $result = $this->paymentManagerService->gateway($record->gateway_slug)->handleCallback($request);
            if (!($result['ok'] ?? false)) {
                return response()->json(['status' => 'failed'], 400);
            }

            return response()->json(['status' => 'success']);
        } catch (\Throwable $e) {
            Log::error('Swich PayIn callback error', ['message' => $e->getMessage()]);

            return response()->json(['status' => 'failed'], 500);
        }
    }

    public function waiting(PaymentGateway $paymentGateway, Order $order)
    {
        if ((int) $order->payment_status === PaymentStatus::PAID) {
            return redirect()->route('payment.successful', ['order' => $order]);
        }

        $gateway = $this->paymentManagerService->gateway($paymentGateway->slug)->gateway;
        $record = method_exists($gateway, 'latestRecord') ? $gateway->latestRecord($order) : null;
        if (!$record) {
            return redirect()->route('payment.index', [
                'order' => $order,
                'paymentGateway' => $paymentGateway->slug,
            ])->with('error', trans('all.message.something_wrong'));
        }

        $company = Settings::group('company')->all();
        $logo = ThemeSetting::where(['key' => 'theme_logo'])->first();
        $faviconLogo = ThemeSetting::where(['key' => 'theme_favicon_logo'])->first();

        return view('paymentGateways.swich.waiting', [
            'company' => $company,
            'logo' => $logo,
            'faviconLogo' => $faviconLogo,
            'order' => $order,
            'paymentGateway' => $paymentGateway,
            'record' => $record,
        ]);
    }

    public function status(PaymentGateway $paymentGateway, Order $order): JsonResponse
    {
        $order->refresh();
        if ((int) $order->payment_status === PaymentStatus::PAID) {
            return response()->json([
                'status' => 'paid',
                'redirect' => route('payment.successful', ['order' => $order]),
            ]);
        }

        $gateway = $this->paymentManagerService->gateway($paymentGateway->slug)->gateway;
        if (method_exists($gateway, 'settleFromInquire')) {
            $redirect = $gateway->settleFromInquire($order);
            if ($redirect) {
                $order->refresh();
                if ((int) $order->payment_status === PaymentStatus::PAID) {
                    return response()->json([
                        'status' => 'paid',
                        'redirect' => route('payment.successful', ['order' => $order]),
                    ]);
                }
            }
        }

        $record = method_exists($gateway, 'latestRecord') ? $gateway->latestRecord($order) : null;

        return response()->json([
            'status' => $record?->status ?: 'pending',
            'consumerNumber' => $record?->consumer_number,
        ]);
    }
}
