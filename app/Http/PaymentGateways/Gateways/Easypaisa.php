<?php

namespace App\Http\PaymentGateways\Gateways;

use App\Services\SwichPayinClient;
use App\Services\SwichPayinGateway;

class Easypaisa extends SwichPayinGateway
{
    protected function gatewaySlug(): string
    {
        return 'easypaisa';
    }

    protected function channelId(): int
    {
        return SwichPayinClient::CHANNEL_EASYPAISA;
    }

    protected function defaultCategoryId(): ?int
    {
        return SwichPayinClient::CATEGORY_EWALLET;
    }

    protected function purchase(SwichPayinClient $client, array $payload): array
    {
        return $client->purchaseEwallet($payload);
    }
}
