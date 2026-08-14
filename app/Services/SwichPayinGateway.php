<?php

namespace App\Services;

use App\Enums\Activity;
use App\Enums\GatewayMode;
use App\Enums\PaymentStatus;
use App\Models\Order;
use App\Models\PaymentGateway;
use App\Models\SwichPayinTransaction;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

abstract class SwichPayinGateway extends PaymentAbstract
{
    abstract protected function gatewaySlug(): string;

    abstract protected function channelId(): int;

    abstract protected function defaultCategoryId(): ?int;

    abstract protected function purchase(SwichPayinClient $client, array $payload): array;

    public function __construct()
    {
        parent::__construct(new PaymentService());
        $this->paymentGateway = PaymentGateway::with('gatewayOptions')->where(['slug' => $this->gatewaySlug()])->first();
        if (!blank($this->paymentGateway)) {
            $this->paymentGatewayOption = $this->paymentGateway->gatewayOptions->pluck('value', 'option');
        }
    }

    public function status(): bool
    {
        return (bool) PaymentGateway::where(['slug' => $this->gatewaySlug(), 'status' => Activity::ENABLE])->first();
    }

    public function payment($order, $request): mixed
    {
        $slug = $this->gatewaySlug();

        try {
            $msisdn = $this->normalizeMsisdn((string) ($request->msisdn ?? $order->shippingAddress?->phone ?? ''));
            if ($msisdn === '') {
                return $this->backToPayment($order, trans('all.message.swich_msisdn_required'));
            }

            $client = $this->client();
            $customerTransactionId = $this->makeCustomerTransactionId($order);
            $categoryId = (int) ($this->opt($slug . '_category_id') ?: $this->defaultCategoryId() ?: 0);
            $amount = round((float) $order->total, 2);

            $payload = [
                'customerTransactionId' => $customerTransactionId,
                'categoryId' => $categoryId,
                'channelId' => $this->channelId(),
                'item' => 'Order' . $order->order_serial_no,
                'amount' => $amount,
                'msisdn' => $msisdn,
                'cnic' => '',
                'email' => (string) ($order->shippingAddress?->email ?? $order->user?->email ?? ''),
                'ucid' => Str::upper(Str::random(6)),
            ];

            $remoteIp = trim((string) $this->opt($slug . '_remote_ip'));
            if ($remoteIp !== '') {
                $payload['remoteIPAddress'] = $remoteIp;
            }

            $response = $this->purchase($client, $payload);

            $record = SwichPayinTransaction::create([
                'order_id' => $order->id,
                'gateway_slug' => $slug,
                'customer_transaction_id' => $customerTransactionId,
                'swich_order_id' => (string) ($response['orderId'] ?? ''),
                'swich_transaction_id' => (string) ($response['transactionId'] ?? ''),
                'consumer_number' => (string) ($response['consumerNumber'] ?? ''),
                'msisdn' => $msisdn,
                'amount' => $amount,
                'status' => strtolower((string) ($response['status'] ?? 'pending')),
                'channel_id' => $this->channelId(),
                'category_id' => $categoryId,
                'payload' => $response,
            ]);

            session(['swich_payin_' . $order->id => $customerTransactionId]);

            if ($client->isSuccessful($response) && !$client->isPending($response) && empty($record->consumer_number)) {
                $this->paymentService->payment($order, $slug, $record->swich_order_id ?: $customerTransactionId);

                return redirect()->route('payment.successful', ['order' => $order]);
            }

            if (($response['status'] ?? '') === 'failed') {
                return $this->backToPayment($order, (string) ($response['message'] ?? trans('all.message.something_wrong')));
            }

            return redirect()->route('payment.swich.waiting', [
                'paymentGateway' => $slug,
                'order' => $order,
            ]);
        } catch (\Throwable $e) {
            Log::error('Swich PayIn payment error', [
                'gateway' => $slug,
                'order_id' => $order->id,
                'message' => $e->getMessage(),
            ]);

            return $this->backToPayment($order, trans('all.message.something_wrong'));
        }
    }

    public function success($order, $request): \Illuminate\Http\RedirectResponse
    {
        return $this->settleFromInquire($order)
            ?: redirect()->route('payment.swich.waiting', [
                'paymentGateway' => $this->gatewaySlug(),
                'order' => $order,
            ]);
    }

    public function fail($order, $request): \Illuminate\Http\RedirectResponse
    {
        return $this->backToPayment($order, trans('all.message.something_wrong'));
    }

    public function cancel($order, $request): \Illuminate\Http\RedirectResponse
    {
        return redirect('/checkout/payment')->with('error', trans('all.message.payment_canceled'));
    }

    public function settleFromInquire(Order $order): ?\Illuminate\Http\RedirectResponse
    {
        if ((int) $order->payment_status === PaymentStatus::PAID) {
            return redirect()->route('payment.successful', ['order' => $order]);
        }

        $record = SwichPayinTransaction::where('order_id', $order->id)
            ->where('gateway_slug', $this->gatewaySlug())
            ->latest('id')
            ->first();

        if (!$record) {
            return null;
        }

        try {
            $response = $this->client()->inquire($record->customer_transaction_id);
            $txnStatus = strtolower((string) data_get($response, 'transaction.transactionStatus', $response['status'] ?? ''));
            $record->update([
                'status' => $txnStatus,
                'payload' => $response,
                'swich_order_id' => data_get($response, 'transaction.orderId', $record->swich_order_id),
                'consumer_number' => data_get($response, 'transaction.consumerNumber', $record->consumer_number),
            ]);

            if ($txnStatus === 'success') {
                $this->paymentService->payment(
                    $order,
                    $this->gatewaySlug(),
                    (string) data_get($response, 'transaction.orderId', $record->swich_order_id)
                );

                return redirect()->route('payment.successful', ['order' => $order]);
            }

            if (in_array($txnStatus, ['failed', 'block', 'terminated', 'expired'], true)) {
                return $this->backToPayment($order, trans('all.message.something_wrong'));
            }
        } catch (\Throwable $e) {
            Log::warning('Swich PayIn inquire failed', [
                'order_id' => $order->id,
                'message' => $e->getMessage(),
            ]);
        }

        return null;
    }

    public function latestRecord(Order $order): ?SwichPayinTransaction
    {
        return SwichPayinTransaction::where('order_id', $order->id)
            ->where('gateway_slug', $this->gatewaySlug())
            ->latest('id')
            ->first();
    }

    public function handleCallback(Request $request): array
    {
        $payload = $request->all();
        if (!$this->client()->verifyCallbackChecksum($payload)) {
            Log::warning('Swich PayIn callback checksum failed', ['payload' => $payload]);

            return ['ok' => false, 'message' => 'Invalid checksum'];
        }

        $customerTransactionId = (string) ($payload['CustomerTransactionId'] ?? $payload['customerTransactionId'] ?? '');
        $record = SwichPayinTransaction::where('customer_transaction_id', $customerTransactionId)->first();
        if (!$record) {
            return ['ok' => false, 'message' => 'Transaction not found'];
        }

        $status = strtolower((string) ($payload['Status'] ?? $payload['status'] ?? ''));
        $record->update([
            'status' => $status,
            'swich_order_id' => (string) ($payload['OrderId'] ?? $payload['orderId'] ?? $record->swich_order_id),
            'payload' => array_merge($record->payload ?? [], ['callback' => $payload]),
        ]);

        $order = $record->order;
        if ($status === 'success' && $order && (int) $order->payment_status !== PaymentStatus::PAID) {
            $this->paymentService->payment($order, $record->gateway_slug, $record->swich_order_id ?: $customerTransactionId);
        }

        return ['ok' => true];
    }

    public function client(): SwichPayinClient
    {
        $slug = $this->gatewaySlug();
        $secret = (string) $this->opt($slug . '_client_secret');

        return new SwichPayinClient(
            (string) $this->opt($slug . '_client_id'),
            $secret,
            $secret,
            (int) ($this->opt($slug . '_mode') ?: GatewayMode::SANDBOX),
            $this->opt($slug . '_remote_ip') ?: null,
        );
    }

    protected function opt(string $key): mixed
    {
        return $this->paymentGatewayOption[$key] ?? '';
    }

    protected function makeCustomerTransactionId(Order $order): string
    {
        return substr('EJ' . $order->id . Str::upper(Str::random(10)), 0, 50);
    }

    protected function normalizeMsisdn(string $phone): string
    {
        $digits = preg_replace('/\D+/', '', $phone) ?? '';
        if (str_starts_with($digits, '92') && strlen($digits) === 12) {
            $digits = '0' . substr($digits, 2);
        }
        if (strlen($digits) === 10 && str_starts_with($digits, '3')) {
            $digits = '0' . $digits;
        }

        return preg_match('/^03\d{9}$/', $digits) ? $digits : '';
    }

    protected function backToPayment(Order $order, string $message): \Illuminate\Http\RedirectResponse
    {
        return redirect()->route('payment.index', [
            'order' => $order,
            'paymentGateway' => $this->gatewaySlug(),
        ])->with('error', $message);
    }
}
