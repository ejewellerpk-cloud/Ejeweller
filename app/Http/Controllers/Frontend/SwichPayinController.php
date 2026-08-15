<?php

namespace App\Http\Controllers\Frontend;

use App\Enums\PaymentStatus;
use App\Http\Controllers\Controller;
use App\Http\PaymentGateways\Gateways\Swich;
use App\Models\Order;
use App\Models\PaymentGateway;
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
        try {
            $this->paymentManagerService->gateway(Swich::SLUG)->handleCallback($request);
        } catch (\Throwable $e) {
            Log::error('Swich PayIn callback error', ['message' => $e->getMessage()]);
        }

        return response()->json(['status' => 'success'], 200);
    }

    public function waiting(Order $order)
    {
        $paymentGateway = PaymentGateway::where('slug', Swich::SLUG)->firstOrFail();
        if ((int) $order->payment_status === PaymentStatus::PAID) {
            return redirect()->route('payment.successful', ['order' => $order]);
        }

        $gateway = $this->paymentManagerService->gateway(Swich::SLUG)->gateway;
        $record = method_exists($gateway, 'latestRecord') ? $gateway->latestRecord($order) : null;
        if (!$record) {
            return redirect()->route('payment.index', [
                'order' => $order,
                'paymentGateway' => Swich::SLUG,
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

    public function status(Order $order): JsonResponse
    {
        $order->refresh();
        if ((int) $order->payment_status === PaymentStatus::PAID) {
            return response()->json([
                'status' => 'paid',
                'phase' => 'paid',
                'redirect' => route('payment.successful', ['order' => $order]),
            ]);
        }

        $gateway = $this->paymentManagerService->gateway(Swich::SLUG)->gateway;
        if (method_exists($gateway, 'settleFromInquire')) {
            $gateway->settleFromInquire($order, false);
            $order->refresh();
            if ((int) $order->payment_status === PaymentStatus::PAID) {
                return response()->json([
                    'status' => 'paid',
                    'phase' => 'paid',
                    'redirect' => route('payment.successful', ['order' => $order]),
                ]);
            }
        }

        $record = method_exists($gateway, 'latestRecord') ? $gateway->latestRecord($order) : null;

        $isBiller = $record && $record->method === Swich::METHOD_BILLER;
        $status = strtolower((string) ($record?->status ?: 'pending'));
        if ($this->isCancelledApiStatus($status)) {
            $status = 'cancelled';
        }

        return response()->json([
            'status' => $status,
            'phase' => $this->paymentPhase($order, $record, $status),
            'method' => $record?->method,
            'consumerNumber' => $isBiller ? $record->consumer_number : null,
            'message' => $this->statusMessage($status),
        ]);
    }

    public function initiate(Order $order): JsonResponse
    {
        $gateway = $this->paymentManagerService->gateway(Swich::SLUG)->gateway;
        if (!method_exists($gateway, 'initiatePurchase')) {
            return response()->json(['ok' => false, 'status' => 'error'], 500);
        }

        return response()->json($gateway->initiatePurchase($order));
    }

    protected function statusMessage(?string $status): ?string
    {
        $status = strtolower((string) $status);
        if ($this->isCancelledApiStatus($status)) {
            return trans('all.message.swich_payment_cancelled');
        }

        return null;
    }

    protected function isCancelledApiStatus(?string $status): bool
    {
        return in_array(strtolower((string) $status), ['cancelled', 'canceled', 'declined', 'rejected'], true);
    }

    protected function paymentPhase(Order $order, $record, string $status): string
    {
        if ((int) $order->payment_status === PaymentStatus::PAID || $status === 'paid' || $status === 'success') {
            return 'paid';
        }
        if ($this->isCancelledApiStatus($status) || in_array($status, ['failed', 'terminated', 'block', 'expired'], true)) {
            return 'cancelled';
        }
        if ($record && ($record->method === Swich::METHOD_BILLER && filled($record->consumer_number))) {
            return 'waiting';
        }
        if ($record && (filled($record->swich_order_id) || filled($record->swich_transaction_id))) {
            return 'waiting';
        }

        return 'sending';
    }
}
