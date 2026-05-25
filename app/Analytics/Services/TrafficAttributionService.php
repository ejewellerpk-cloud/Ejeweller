<?php

namespace App\Analytics\Services;

use App\Analytics\Enums\TrafficSource;

class TrafficAttributionService
{
    public function resolve(array $context): array
    {
        $utmSource = strtolower((string) ($context['utm_source'] ?? ''));
        $utmMedium = strtolower((string) ($context['utm_medium'] ?? ''));
        $utmCampaign = $context['utm_campaign'] ?? null;
        $referrer = (string) ($context['referrer'] ?? '');

        $source = TrafficSource::Direct->value;
        $medium = $utmMedium ?: 'none';

        if ($utmSource !== '') {
            $source = $this->mapUtmSource($utmSource);
            $medium = $utmMedium ?: 'campaign';
        } elseif ($referrer !== '') {
            $parsed = parse_url($referrer);
            $host = strtolower($parsed['host'] ?? '');
            if (str_contains($host, 'google') || str_contains($host, 'bing')) {
                $source = TrafficSource::Organic->value;
                $medium = 'organic';
            } elseif (str_contains($host, 'facebook') || str_contains($host, 'instagram')) {
                $source = TrafficSource::Facebook->value;
                $medium = 'social';
            } elseif (str_contains($host, 'tiktok')) {
                $source = TrafficSource::Tiktok->value;
                $medium = 'social';
            } else {
                $source = TrafficSource::Referral->value;
                $medium = 'referral';
            }
        }

        if (in_array($utmMedium, ['cpc', 'ppc', 'paid'], true)) {
            $source = TrafficSource::Paid->value;
            $medium = 'paid';
        }

        if (str_contains($utmSource, 'facebook') && in_array($utmMedium, ['cpc', 'paid'], true)) {
            $source = TrafficSource::Facebook->value;
        }

        if (str_contains($utmSource, 'google') && in_array($utmMedium, ['cpc', 'paid'], true)) {
            $source = TrafficSource::GoogleAds->value;
        }

        return [
            'source' => $source,
            'medium' => $medium,
            'campaign' => $utmCampaign,
            'content' => $context['utm_content'] ?? null,
            'term' => $context['utm_term'] ?? null,
            'referrer' => $referrer ?: null,
        ];
    }

    private function mapUtmSource(string $utmSource): string
    {
        return match (true) {
            str_contains($utmSource, 'facebook'), str_contains($utmSource, 'fb') => TrafficSource::Facebook->value,
            str_contains($utmSource, 'tiktok') => TrafficSource::Tiktok->value,
            str_contains($utmSource, 'google') => TrafficSource::GoogleAds->value,
            str_contains($utmSource, 'instagram') => TrafficSource::Social->value,
            default => $utmSource,
        };
    }
}
