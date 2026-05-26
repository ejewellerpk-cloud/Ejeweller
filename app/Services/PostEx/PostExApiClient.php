<?php

namespace App\Services\PostEx;

use App\Exceptions\PostExApiException;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;

/**
 * PostEx Merchant COD API v4.1.9 client.
 *
 * @see PostEx-COD_API_Integration_Guide_V4.1.9
 */
class PostExApiClient
{
    protected string $baseUrl;

    protected string $token;

    public function __construct(?string $token = null, ?string $baseUrl = null)
    {
        $settings = app(PostExSettingsService::class)->list();

        $this->token   = $token ?? (string) ($settings['postex_api_token'] ?? '');
        $this->baseUrl = rtrim($baseUrl ?? (string) ($settings['postex_base_url'] ?? config('postex.base_url')), '/');
    }

    public function isConfigured(): bool
    {
        return $this->token !== '';
    }

    public function getOperationalCities(?string $operationalCityType = null): array
    {
        $params = [];
        if ($operationalCityType !== null && $operationalCityType !== '') {
            $params['operationalCityType'] = $operationalCityType;
        }

        return $this->decode($this->get('/order/v2/get-operational-city', $params));
    }

    public function getMerchantAddresses(?string $cityName = null): array
    {
        $params = [];
        if ($cityName !== null && $cityName !== '') {
            $params['cityName'] = $cityName;
        }

        return $this->decode($this->get('/order/v1/get-merchant-address', $params));
    }

    public function createMerchantAddress(array $payload): array
    {
        return $this->decode($this->post('/order/v2/create-merchant-address', $payload));
    }

    public function getOrderTypes(): array
    {
        return $this->decode($this->get('/order/v1/get-order-types'));
    }

    public function createOrder(array $payload): array
    {
        return $this->decode($this->post('/order/v3/create-order', $payload));
    }

    public function getUnbookedOrders(string $startDate, string $endDate, ?string $cityName = null): array
    {
        $params = [
            'startDate' => $startDate,
            'endDate'   => $endDate,
        ];
        if ($cityName !== null && $cityName !== '') {
            $params['cityName'] = $cityName;
        }

        return $this->decode($this->get('/order/v2/get-unbooked-orders', $params));
    }

    public function generateLoadSheet(array $trackingNumbers, ?string $pickupAddress = null): Response
    {
        $payload = ['trackingNumbers' => array_values($trackingNumbers)];
        if ($pickupAddress !== null && $pickupAddress !== '') {
            $payload['pickupAddress'] = $pickupAddress;
        }

        return $this->postRaw('/order/v2/generate-load-sheet', $payload);
    }

    public function trackOrder(string $trackingNumber): array
    {
        return $this->decode($this->get('/order/v1/track-order/' . rawurlencode($trackingNumber)));
    }

    public function trackBulkOrders(array $trackingNumbers): array
    {
        return $this->decode($this->post('/order/v1/track-bulk-order', [
            'trackingNumber' => array_values($trackingNumbers),
        ]));
    }

    public function getAirwayBill(array $trackingNumbers): Response
    {
        $query = ['trackingNumbers' => implode(',', array_values($trackingNumbers))];

        // PDF v4.1.9 lists "get invoice" (with space); production often uses get-invoice.
        $paths = ['/order/v1/get-invoice', '/order/v1/get invoice'];

        $lastResponse = null;
        foreach ($paths as $path) {
            $response = $this->getRaw($path, [], $query);
            $lastResponse = $response;

            if ($this->isPdfResponse($response)) {
                return $response;
            }
        }

        if ($lastResponse !== null) {
            $this->decode($lastResponse);
        }

        throw new PostExApiException('Unable to generate PostEx airway bill.');
    }

    public function saveShipperAdvice(string $trackingNumber, int $statusId, string $remarks): array
    {
        $payload = [
            'trackingNumber' => $trackingNumber,
            'statusId'       => $statusId,
            'remarks'        => $remarks,
        ];

        try {
            return $this->decode($this->put('/order/v2/save-shipper-advice', $payload));
        } catch (PostExApiException) {
            return $this->decode($this->putOnAlternateBase('/order/v2/save-shipper-advice', $payload));
        }
    }

    public function getShipperAdvice(string $trackingNumber): array
    {
        try {
            return $this->decode($this->get('/order/v1/get-shipper-advice/' . rawurlencode($trackingNumber)));
        } catch (PostExApiException) {
            return $this->decode($this->getOnAlternateBase('/order/v1/get-shipper-advice/' . rawurlencode($trackingNumber)));
        }
    }

    public function cancelOrder(string $trackingNumber): array
    {
        return $this->decode($this->put('/order/v1/cancel-order', [
            'trackingNumber' => $trackingNumber,
        ]));
    }

    public function paymentStatus(string $trackingNumber): array
    {
        return $this->decode($this->get('/order/v1/payment-status/' . rawurlencode($trackingNumber)));
    }

    public function getOrderStatuses(): array
    {
        return $this->decode($this->get('/order/v1/get-order-status'));
    }

    public function listOrders(int $orderStatusId, string $fromDate, string $toDate): array
    {
        return $this->decode($this->get('/order/v1/get-all-order', [
            'orderStatusID' => $orderStatusId,
            'fromDate'      => $fromDate,
            'toDate'        => $toDate,
        ]));
    }

    public function testConnection(): array
    {
        return $this->getOrderTypes();
    }

    protected function get(string $uri, array $params = []): Response
    {
        return $this->request()->get($this->baseUrl . $uri, $params);
    }

    protected function post(string $uri, array $data = []): Response
    {
        return $this->request()->post($this->baseUrl . $uri, $data);
    }

    protected function put(string $uri, array $data = []): Response
    {
        return $this->request()->put($this->baseUrl . $uri, $data);
    }

    protected function getRaw(string $uri, array $params = [], array $query = []): Response
    {
        $url = $this->baseUrl . $uri;
        if ($query !== []) {
            $url .= '?' . http_build_query($query);
        }

        return $this->request()->get($url, $params);
    }

    protected function postRaw(string $uri, array $data = []): Response
    {
        return $this->post($uri, $data);
    }

    protected function putOnAlternateBase(string $uri, array $data = []): Response
    {
        $alternate = str_replace('/services/integration/api', '/service/integration/api', $this->baseUrl);

        return $this->request()->put($alternate . $uri, $data);
    }

    protected function getOnAlternateBase(string $uri, array $params = []): Response
    {
        $alternate = str_replace('/services/integration/api', '/service/integration/api', $this->baseUrl);

        return $this->request()->get($alternate . $uri, $params);
    }

    protected function request()
    {
        return Http::withHeaders([
            'token'        => $this->token,
            'Accept'       => 'application/json',
            'Content-Type' => 'application/json',
        ])->timeout(config('postex.timeout', 30));
    }

    protected function decode(Response $response): array
    {
        if ($this->isPdfResponse($response)) {
            return ['binary' => $response->body()];
        }

        if ($response->successful() && trim($response->body()) === '') {
            return ['statusCode' => '200', 'statusMessage' => 'SUCCESSFULLY OPERATED', 'dist' => []];
        }

        if ($response->failed()) {
            $message = $response->json('statusMessage') ?? $response->body() ?: 'PostEx API request failed.';
            throw new PostExApiException($message, (string) $response->status());
        }

        $data = $response->json();
        if (!is_array($data)) {
            throw new PostExApiException('Invalid response from PostEx API.');
        }

        $statusCode = (string) ($data['statusCode'] ?? '');
        if ($statusCode !== '' && $statusCode !== '200') {
            throw new PostExApiException(
                (string) ($data['statusMessage'] ?? 'PostEx API returned an error.'),
                $statusCode,
                (string) ($data['statusMessage'] ?? null)
            );
        }

        return $data;
    }

    protected function isPdfResponse(Response $response): bool
    {
        $contentType = (string) $response->header('Content-Type');

        return str_contains($contentType, 'pdf')
            || str_starts_with($response->body(), '%PDF');
    }
}
