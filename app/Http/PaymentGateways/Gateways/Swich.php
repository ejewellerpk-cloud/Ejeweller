<?php

namespace App\Http\PaymentGateways\Gateways;

use App\Enums\Activity;
use App\Enums\GatewayMode;
use App\Enums\PaymentStatus;
use App\Models\Order;
use App\Models\PaymentGateway;
use App\Models\SwichPayinTransaction;
use App\Services\PaymentAbstract;
use App\Services\PaymentService;
use App\Services\SwichPayinClient;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class Swich extends PaymentAbstract
{
    public const SLUG = 'swich';

    public const METHOD_JAZZCASH = 'jazzcash';

    public const METHOD_EASYPAISA = 'easypaisa';

    public const METHOD_BILLER = 'biller';

    public function __construct()
    {
        parent::__construct(new PaymentService());
        $this->paymentGateway = PaymentGateway::with('gatewayOptions')->where(['slug' => self::SLUG])->first();
        if (!blank($this->paymentGateway)) {
            $this->paymentGatewayOption = $this->paymentGateway->gatewayOptions->pluck('value', 'option');
        }
    }

    public function status(): bool
    {
        return PaymentGateway::where(['slug' => self::SLUG, 'status' => Activity::ENABLE])->exists();
    }

    public function ewalletEnabled(): bool
    {
        return (int) $this->opt('swich_ewallet_status') === Activity::ENABLE;
    }

    public function billerEnabled(): bool
    {
        return (int) $this->opt('swich_biller_status') === Activity::ENABLE;
    }

    public function payment($order, $request): mixed
    {
        try {
            $method = $this->resolveMethod((string) ($request->swich_method ?? $request->swich_method_ui ?? ''));
            if ($method === null) {
                $method = $this->ewalletEnabled() ? self::METHOD_JAZZCASH : ($this->billerEnabled() ? self::METHOD_BILLER : null);
            }
            if ($method === null) {
                return $this->backToPayment($order, trans('all.message.swich_method_required'));
            }

            $msisdn = self::msisdnFromRequest($request, $order);
            if ($msisdn === '') {
                Log::warning('Swich PayIn rejected MSISDN', [
                    'order_id' => $order->id,
                    'keys' => array_keys($request->all()),
                ]);

                return $this->backToPayment($order, trans('all.message.swich_msisdn_required'));
            }

            $email = $this->resolveEmail($order, (string) ($request->swich_email ?? $request->email ?? ''));
            if ($email === '') {
                return $this->backToPayment($order, trans('all.message.swich_email_required'));
            }

            $client = $this->client();
            $customerTransactionId = $this->makeCustomerTransactionId($order);
            $isBiller = $method === self::METHOD_BILLER;
            $categoryId = $isBiller ? SwichPayinClient::CATEGORY_BILLER : SwichPayinClient::CATEGORY_EWALLET;
            $channelId = match ($method) {
                self::METHOD_JAZZCASH => SwichPayinClient::CHANNEL_JAZZCASH,
                self::METHOD_EASYPAISA => SwichPayinClient::CHANNEL_EASYPAISA,
                default => SwichPayinClient::CHANNEL_BILLER,
            };
            $amount = number_format((float) $order->total, 2, '.', '');
            $item = $this->sanitizeItem('Order' . preg_replace('/\W+/', '', (string) $order->order_serial_no) . $order->id);
            $ucid = substr(preg_replace('/[^A-Za-z0-9]/', '', $customerTransactionId) ?: '000000', -6);

            $payload = [
                'customerTransactionId' => $customerTransactionId,
                'categoryId' => $categoryId,
                'channelId' => $channelId,
                'ucid' => $ucid,
                'item' => $item,
                'amount' => (float) $amount,
                'msisdn' => $msisdn,
                'cnic' => '',
                'email' => $email,
            ];

            $response = $isBiller
                ? $client->purchaseBiller($payload)
                : $client->purchaseEwallet($payload);

            $record = SwichPayinTransaction::create([
                'order_id' => $order->id,
                'gateway_slug' => self::SLUG,
                'method' => $method,
                'customer_transaction_id' => $customerTransactionId,
                'swich_order_id' => (string) ($response['orderId'] ?? ''),
                'swich_transaction_id' => (string) ($response['transactionId'] ?? ''),
                'consumer_number' => (string) ($response['consumerNumber'] ?? ''),
                'msisdn' => $msisdn,
                'amount' => $amount,
                'status' => strtolower((string) ($response['status'] ?? 'pending')),
                'channel_id' => $channelId,
                'category_id' => $categoryId,
                'payload' => $response,
            ]);

            session(['swich_payin_' . $order->id => $customerTransactionId]);

            if (($response['status'] ?? '') === 'failed') {
                return $this->backToPayment($order, (string) ($response['message'] ?? trans('all.message.something_wrong')));
            }

            if ($isBiller) {
                return redirect()->route('payment.swich.waiting', [
                    'paymentGateway' => self::SLUG,
                    'order' => $order,
                ]);
            }

            if ($client->isSuccessful($response) && !$client->isPending($response)) {
                $this->creditOrder($order, $record);

                return redirect()->route('payment.successful', ['order' => $order]);
            }

            return redirect()->route('payment.swich.waiting', [
                'paymentGateway' => self::SLUG,
                'order' => $order,
            ]);
        } catch (\Throwable $e) {
            Log::error('Swich PayIn payment error', [
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
                'paymentGateway' => self::SLUG,
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

        $record = $this->latestRecord($order);
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
                $this->creditOrder($order, $record);

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
            ->where('gateway_slug', self::SLUG)
            ->latest('id')
            ->first();
    }

    public function handleCallback(Request $request): array
    {
        $payload = $request->all();
        $customerTransactionId = (string) ($payload['CustomerTransactionId'] ?? $payload['customerTransactionId'] ?? '');
        $status = strtolower((string) ($payload['Status'] ?? $payload['status'] ?? ''));
        $callbackAmount = (string) ($payload['Amount'] ?? $payload['amount'] ?? '');

        if (!$this->client()->verifyCallbackChecksum($payload)) {
            Log::warning('Swich PayIn callback checksum failed', ['payload' => $payload]);

            return ['ok' => true, 'credited' => false];
        }

        $record = SwichPayinTransaction::where('customer_transaction_id', $customerTransactionId)->first();
        if (!$record) {
            Log::warning('Swich PayIn callback unknown transaction', ['payload' => $payload]);

            return ['ok' => true, 'credited' => false];
        }

        $record->update([
            'status' => $status !== '' ? $status : $record->status,
            'swich_order_id' => (string) ($payload['OrderId'] ?? $payload['orderId'] ?? $record->swich_order_id),
            'payload' => array_merge($record->payload ?? [], ['callback' => $payload]),
        ]);

        if ($status !== 'success') {
            return ['ok' => true, 'credited' => false];
        }

        if (abs((float) $record->amount - (float) $callbackAmount) > 0.009) {
            Log::warning('Swich PayIn callback amount mismatch', [
                'expected' => $record->amount,
                'callback' => $callbackAmount,
                'customer_transaction_id' => $customerTransactionId,
            ]);

            return ['ok' => true, 'credited' => false];
        }

        $order = $record->order;
        if ($order) {
            $this->creditOrder($order, $record);
        }

        return ['ok' => true, 'credited' => true];
    }

    public function client(): SwichPayinClient
    {
        $clientSecret = (string) $this->opt('swich_client_secret');

        return new SwichPayinClient(
            (string) $this->opt('swich_client_id'),
            $clientSecret,
            $clientSecret,
            (int) ($this->opt('swich_mode') ?: GatewayMode::SANDBOX),
        );
    }

    protected function creditOrder(Order $order, SwichPayinTransaction $record): void
    {
        if ((int) $order->payment_status === PaymentStatus::PAID) {
            return;
        }

        $this->paymentService->payment(
            $order,
            self::SLUG,
            $record->swich_order_id ?: $record->customer_transaction_id
        );
    }

    protected function resolveMethod(string $method): ?string
    {
        $method = strtolower(trim($method));
        if (in_array($method, [self::METHOD_JAZZCASH, self::METHOD_EASYPAISA], true) && $this->ewalletEnabled()) {
            return $method;
        }
        if ($method === self::METHOD_BILLER && $this->billerEnabled()) {
            return $method;
        }

        return null;
    }

    protected function resolveEmail(Order $order, string $requestEmail): string
    {
        $email = trim($requestEmail ?: (string) ($order->shippingAddress?->email ?? $order->user?->email ?? ''));
        if (filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return $email;
        }

        return '';
    }

    protected function sanitizeItem(string $item): string
    {
        $item = preg_replace('/[^A-Za-z0-9]/', '', $item) ?: 'Order';

        return substr($item, 0, 80);
    }

    protected function makeCustomerTransactionId(Order $order): string
    {
        return substr('EJ' . $order->id . Str::upper(Str::random(10)), 0, 50);
    }

    public static function msisdnFromRequest(mixed $request, Order $order): string
    {
        $candidates = [];
        foreach (['swich_mobile', 'msisdn', 'swich_msisdn', 'mobile', 'phone'] as $key) {
            $value = $request->input($key);
            if (is_array($value)) {
                foreach ($value as $item) {
                    $candidates[] = (string) $item;
                }
            } elseif ($value !== null && $value !== '') {
                $candidates[] = (string) $value;
            }
        }

        $address = $order->shippingAddress;
        $candidates[] = (string) ($address?->phone ?? '');
        $candidates[] = (string) ($order->user?->phone ?? '');

        foreach ($candidates as $candidate) {
            $normalized = self::normalizeMsisdn(
                $candidate,
                (string) ($address?->country_code ?? '')
            );
            if ($normalized !== '') {
                return $normalized;
            }
        }

        return '';
    }

    /**
     * Swich PayIn preferred format is 03xxxxxxxxx (11 digits).
     */
    public static function normalizeMsisdn(string $phone, string $countryCode = ''): string
    {
        foreach ([$phone, $countryCode . $phone, $countryCode . '0' . ltrim($phone, '0')] as $candidate) {
            $normalized = self::extractMsisdnDigits($candidate);
            if ($normalized !== '') {
                return $normalized;
            }
        }

        return '';
    }

    protected static function extractMsisdnDigits(string $value): string
    {
        $map = [
            '۰' => '0', '۱' => '1', '۲' => '2', '۳' => '3', '۴' => '4',
            '۵' => '5', '۶' => '6', '۷' => '7', '۸' => '8', '۹' => '9',
            '٠' => '0', '١' => '1', '٢' => '2', '٣' => '3', '٤' => '4',
            '٥' => '5', '٦' => '6', '٧' => '7', '٨' => '8', '٩' => '9',
        ];
        $digits = preg_replace('/\D+/', '', strtr($value, $map)) ?? '';
        if ($digits === '') {
            return '';
        }

        if (preg_match('/03\d{9}$/', $digits, $matches)) {
            return substr($matches[0], -11);
        }
        if (preg_match('/^3\d{9}$/', $digits)) {
            return '0' . $digits;
        }
        if (preg_match('/(?:92)?0?(3\d{9})$/', $digits, $matches)) {
            return '0' . $matches[1];
        }

        return '';
    }

    protected function opt(string $key): mixed
    {
        return $this->paymentGatewayOption[$key] ?? '';
    }

    protected function backToPayment(Order $order, string $message): \Illuminate\Http\RedirectResponse
    {
        return redirect()->route('payment.index', [
            'order' => $order,
            'paymentGateway' => self::SLUG,
        ])->with('error', $message);
    }
}
