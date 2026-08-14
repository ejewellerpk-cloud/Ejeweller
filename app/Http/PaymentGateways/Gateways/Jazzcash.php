<?php

namespace App\Http\PaymentGateways\Gateways;

use App\Services\SwichPayinClient;
use App\Services\SwichPayinGateway;

class Jazzcash extends SwichPayinGateway
{
    protected function gatewaySlug(): string
    {
        return 'jazzcash';
    }

    protected function channelId(): int
    {
        return SwichPayinClient::CHANNEL_JAZZCASH;
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
