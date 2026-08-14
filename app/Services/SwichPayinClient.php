<?php

namespace App\Services;

use App\Enums\GatewayMode;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use RuntimeException;

class SwichPayinClient
{
    public const SANDBOX_API = 'https://sandbox-api.swichnow.com';

    public const LIVE_API = 'https://api.swichnow.com';

    public const SANDBOX_AUTH = 'https://sandbox-auth.swichnow.com/connect/token';

    public const LIVE_AUTH = 'https://auth.swichnow.com/connect/token';

    public const CHANNEL_EASYPAISA = 8;

    public const CHANNEL_JAZZCASH = 10;

    public const CHANNEL_BILLER = 11;

    public const CATEGORY_EWALLET = 2;

    public const CATEGORY_BILLER = 3;

    public function __construct(
        protected string $clientId,
        protected string $clientSecret,
        protected string $checksumSecret,
        protected int $mode = GatewayMode::SANDBOX,
        protected ?string $remoteIp = null,
    ) {
    }

    public function isLive(): bool
    {
        return (int) $this->mode === GatewayMode::LIVE;
    }

    public function apiBase(): string
    {
        return $this->isLive() ? self::LIVE_API : self::SANDBOX_API;
    }

    public function purchaseEwallet(array $payload): array
    {
        return $this->postJson('/gateway/payin/v2.0/purchase/ewallet', $payload);
    }

    public function purchaseBiller(array $payload): array
    {
        return $this->postJson('/gateway/payin/v2.0/purchase/biller', $payload);
    }

    public function inquire(string $customerTransactionId): array
    {
        return $this->getJson('/gateway/payin/v2.0/inquire', [
            'CustomerTransactionId' => $customerTransactionId,
        ]);
    }

    public function isSuccessful(array $response): bool
    {
        $status = strtolower((string) ($response['status'] ?? ''));
        $code = (string) ($response['code'] ?? '');

        return $status === 'success' && in_array($code, ['0000', '00001', '0'], true);
    }

    public function isPending(array $response): bool
    {
        $status = strtolower((string) ($response['status'] ?? ''));
        $message = strtolower((string) ($response['message'] ?? ''));
        $txnStatus = strtolower((string) data_get($response, 'transaction.transactionStatus', ''));

        if (in_array($status, ['pending', 'queue', 'otp send', 'in-progress'], true)) {
            return true;
        }
        if (in_array($txnStatus, ['pending', 'queue', 'otp send'], true)) {
            return true;
        }
        if (str_contains($message, 'otp') && str_contains($message, 'send')) {
            return true;
        }

        return false;
    }

    public function verifyCallbackChecksum(array $payload): bool
    {
        $customerTransactionId = (string) ($payload['CustomerTransactionId'] ?? $payload['customerTransactionId'] ?? '');
        $orderId = (string) ($payload['OrderId'] ?? $payload['orderId'] ?? '');
        $amount = (string) ($payload['Amount'] ?? $payload['amount'] ?? '');
        $status = (string) ($payload['Status'] ?? $payload['status'] ?? '');
        $checksum = (string) ($payload['Checksum'] ?? $payload['checksum'] ?? '');

        if ($customerTransactionId === '' || $checksum === '') {
            return false;
        }

        $plain = 'SWCallback:' . $customerTransactionId . ':' . $orderId . ':' . $amount . ':' . $status;
        $expected = hash_hmac('sha256', $plain, $this->checksumSecret, false);

        return hash_equals(strtolower($expected), strtolower($checksum));
    }

    protected function postJson(string $path, array $payload): array
    {
        $response = $this->http()->post($this->apiBase() . $path, $payload);

        return $this->decode($response->body(), $response->status(), $path);
    }

    protected function getJson(string $path, array $query = []): array
    {
        $response = $this->http()->get($this->apiBase() . $path, $query);

        return $this->decode($response->body(), $response->status(), $path);
    }

    protected function http(): PendingRequest
    {
        return Http::timeout(90)
            ->acceptJson()
            ->asJson()
            ->withToken($this->accessToken());
    }

    protected function accessToken(): string
    {
        $cacheKey = 'swich_payin_token_' . md5($this->clientId . '|' . $this->mode);

        return Cache::remember($cacheKey, 3000, function () {
            $url = $this->isLive() ? self::LIVE_AUTH : self::SANDBOX_AUTH;
            $response = Http::asForm()->timeout(30)->post($url, [
                'grant_type' => 'client_credentials',
                'client_id' => $this->clientId,
                'client_secret' => $this->clientSecret,
            ]);

            if (!$response->successful()) {
                Log::warning('Swich PayIn token failed', [
                    'status' => $response->status(),
                    'body' => $response->body(),
                ]);
                throw new RuntimeException('Unable to authenticate with Swich PayIn.');
            }

            $token = (string) $response->json('access_token');
            if ($token === '') {
                throw new RuntimeException('Swich PayIn returned an empty access token.');
            }

            return $token;
        });
    }

    protected function decode(string $body, int $httpStatus, string $path): array
    {
        $json = json_decode($body, true);
        if (!is_array($json)) {
            Log::warning('Swich PayIn invalid JSON', [
                'path' => $path,
                'http' => $httpStatus,
                'body' => $body,
            ]);
            throw new RuntimeException('Swich PayIn returned an invalid response.');
        }

        return $json;
    }
}
