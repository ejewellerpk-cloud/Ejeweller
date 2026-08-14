<?php

namespace App\Http\PaymentGateways\Gateways;

use App\Services\SwichPayinClient;
use App\Services\SwichPayinGateway;

class Biller extends SwichPayinGateway
{
    protected function gatewaySlug(): string
    {
        return 'biller';
    }

    protected function channelId(): int
    {
        return SwichPayinClient::CHANNEL_BILLER;
    }

    protected function defaultCategoryId(): ?int
    {
        return SwichPayinClient::CATEGORY_BILLER;
    }

    protected function purchase(SwichPayinClient $client, array $payload): array
    {
        return $client->purchaseBiller($payload);
    }
}
