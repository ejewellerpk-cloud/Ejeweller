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

            $cnic = preg_replace('/\D+/', '', (string) ($request->swich_cnic ?? '')) ?? '';
            $payload = [
                'customerTransactionId' => $customerTransactionId,
                'categoryId' => (int) $categoryId,
                'channelId' => (int) $channelId,
                'ucid' => $ucid,
                'item' => $item,
                'amount' => (float) $amount,
                'msisdn' => $msisdn,
                'email' => $email,
            ];
            if (preg_match('/^\d{13}$/', $cnic)) {
                $payload['cnic'] = $cnic;
            }

            session(['swich_payin_' . $order->id => $customerTransactionId]);
            SwichPayinTransaction::create([
                'order_id' => $order->id,
                'gateway_slug' => self::SLUG,
                'method' => $method,
                'customer_transaction_id' => $customerTransactionId,
                'msisdn' => $msisdn,
                'amount' => $amount,
                'status' => 'pending',
                'channel_id' => $channelId,
                'category_id' => $categoryId,
                'payload' => ['request' => $payload],
            ]);

            return redirect()->route('payment.swich.waiting', [
                'order' => $order,
            ]);
        } catch (\Throwable $e) {
            Log::error('Swich PayIn payment error', [
                'order_id' => $order->id,
                'message' => $e->getMessage(),
            ]);

            $record = $this->latestRecord($order);
            if ($record) {
                return redirect()->route('payment.swich.waiting', [
                    'order' => $order,
                ]);
            }

            return $this->backToPayment($order, trans('all.message.something_wrong'));
        }
    }

    public function initiatePurchase(Order $order): array
    {
        $record = $this->latestRecord($order);
        if (!$record) {
            return ['ok' => false, 'status' => 'error', 'message' => trans('all.message.something_wrong')];
        }

        $current = strtolower((string) $record->status);
        if (in_array($current, ['success', 'paid'], true)) {
            return ['ok' => true, 'status' => 'paid'];
        }
        if ($this->isCancelledStatus($current)) {
            return ['ok' => true, 'status' => 'cancelled', 'message' => trans('all.message.swich_payment_cancelled')];
        }
        if ($record->swich_order_id || $record->swich_transaction_id || $record->consumer_number) {
            return [
                'ok' => true,
                'status' => 'pending',
                'consumerNumber' => $record->consumer_number,
            ];
        }
        if ($current === 'initiating') {
            return ['ok' => true, 'status' => 'pending'];
        }

        $payload = $record->payload['request'] ?? [];
        if ($payload === []) {
            return ['ok' => false, 'status' => 'error', 'message' => trans('all.message.something_wrong')];
        }

        $record->update(['status' => 'initiating']);
        $client = $this->client();
        $isBiller = $record->method === self::METHOD_BILLER;

        try {
            $response = $isBiller
                ? $client->purchaseBiller($payload + ['cnic' => $payload['cnic'] ?? ''])
                : $client->purchaseEwallet($payload);

            if (!$isBiller && (string) ($response['code'] ?? '') === '0008') {
                $msisdn = (string) ($payload['msisdn'] ?? $record->msisdn);
                $payload['msisdn'] = str_starts_with($msisdn, '0') ? ('92' . substr($msisdn, 1)) : $msisdn;
                $payload['customerTransactionId'] = $this->makeCustomerTransactionId($order);
                $payload['ucid'] = substr(preg_replace('/[^A-Za-z0-9]/', '', $payload['customerTransactionId']) ?: '000000', -6);
                $record->update(['customer_transaction_id' => $payload['customerTransactionId']]);
                session(['swich_payin_' . $order->id => $payload['customerTransactionId']]);
                $response = $client->purchaseEwallet($payload);
            }
        } catch (\Throwable $e) {
            Log::error('Swich PayIn initiate error', [
                'order_id' => $order->id,
                'message' => $e->getMessage(),
            ]);

            return ['ok' => true, 'status' => 'pending'];
        }

        $hardFail = $this->isHardPurchaseFailure($response);
        $message = (string) ($response['message'] ?? '');
        $status = $hardFail ? 'failed' : strtolower((string) ($response['status'] ?? 'pending'));
        if ($this->isCancelledStatus($status, $message)) {
            $status = 'cancelled';
        }

        $record->update([
            'swich_order_id' => (string) ($response['orderId'] ?? ''),
            'swich_transaction_id' => (string) ($response['transactionId'] ?? ''),
            'consumer_number' => (string) ($response['consumerNumber'] ?? ''),
            'status' => $status === 'success' ? 'pending' : $status,
            'payload' => array_merge($record->payload ?? [], ['response' => $response]),
        ]);

        if ($status === 'cancelled') {
            return ['ok' => true, 'status' => 'cancelled', 'message' => trans('all.message.swich_payment_cancelled')];
        }
        if ($hardFail) {
            Log::warning('Swich PayIn purchase failed', [
                'order_id' => $order->id,
                'method' => $record->method,
                'code' => $response['code'] ?? null,
                'message' => $message,
                'msisdn' => $record->msisdn,
            ]);

            return [
                'ok' => false,
                'status' => 'failed',
                'message' => $this->isInvalidWalletAccount($response)
                    ? trans('all.message.swich_wallet_invalid')
                    : ((string) ($response['code'] ?? '') === '0027'
                        ? trans('all.message.swich_ip_not_whitelisted')
                        : ($message !== '' ? $message : trans('all.message.something_wrong'))),
            ];
        }

        return [
            'ok' => true,
            'status' => 'pending',
            'consumerNumber' => $record->consumer_number,
        ];
    }

    public function success($order, $request): \Illuminate\Http\RedirectResponse
    {
        return $this->settleFromInquire($order)
            ?: redirect()->route('payment.swich.waiting', [
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

    public function settleFromInquire(Order $order, bool $redirectOnFailure = true): ?\Illuminate\Http\RedirectResponse
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
            $inquireMessage = (string) ($response['message'] ?? data_get($response, 'transaction.message', ''));
            if ($this->isCancelledStatus($txnStatus, $inquireMessage)) {
                $txnStatus = 'cancelled';
            }
            $record->update([
                'status' => $txnStatus,
                'payload' => array_merge($record->payload ?? [], ['inquire' => $response]),
                'swich_order_id' => data_get($response, 'transaction.orderId', $record->swich_order_id),
                'consumer_number' => data_get($response, 'transaction.consumerNumber', $record->consumer_number),
            ]);

            if ($txnStatus === 'success') {
                $this->creditOrder($order, $record);

                return redirect()->route('payment.successful', ['order' => $order]);
            }

            if ($redirectOnFailure && in_array($txnStatus, ['failed', 'block', 'terminated', 'expired', 'cancelled', 'canceled'], true)) {
                $msg = $txnStatus === 'cancelled' || $txnStatus === 'canceled'
                    ? trans('all.message.swich_payment_cancelled')
                    : trans('all.message.something_wrong');

                return $this->backToPayment($order, $msg);
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
            if ($this->isCancelledStatus($status, (string) ($payload['Message'] ?? $payload['message'] ?? ''))) {
                $record->update(['status' => 'cancelled']);
            }

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
        $posted = $request->input('swich_mobile');
        if (is_array($posted)) {
            $posted = (string) (end($posted) ?: '');
        }

        return self::normalizeMsisdn((string) ($posted ?? ''));
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

    protected function isCancelledStatus(string $status, string $message = ''): bool
    {
        $status = strtolower(trim($status));
        $message = strtolower($message);

        return in_array($status, ['cancelled', 'canceled', 'declined', 'rejected', 'expired', 'terminated'], true)
            || str_contains($message, 'cancel');
    }

    protected function isHardPurchaseFailure(array $response): bool
    {
        $status = strtolower((string) ($response['status'] ?? ''));
        $code = (string) ($response['code'] ?? '');
        $message = strtolower((string) ($response['message'] ?? ''));

        if (str_contains($message, 'otp')) {
            return false;
        }

        return $status === 'failed' || in_array($code, ['0001', '0008', '0018', '0027', '0036', '9900', '00012'], true);
    }

    protected function isInvalidWalletAccount(array $response): bool
    {
        $code = (string) ($response['code'] ?? '');
        $message = strtolower((string) ($response['message'] ?? ''));

        return $code === '0036' || str_contains($message, 'invalid account');
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
