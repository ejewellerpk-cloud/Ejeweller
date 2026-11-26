<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class MarketingCampaignResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'channel' => $this->channel,
            'objective' => $this->objective,
            'status' => $this->status,
            'utm_source' => $this->utm_source,
            'utm_medium' => $this->utm_medium,
            'utm_campaign' => $this->utm_campaign,
            'utm_content' => $this->utm_content,
            'utm_term' => $this->utm_term,
            'utm_query' => $this->utm_query,
            'landing_path' => $this->landing_path,
            'tracking_url' => $this->tracking_url,
            'daily_budget' => $this->daily_budget,
            'total_budget' => $this->total_budget,
            'currency' => $this->currency,
            'starts_at' => optional($this->starts_at)->toDateString(),
            'ends_at' => optional($this->ends_at)->toDateString(),
            'external_campaign_id' => $this->external_campaign_id,
            'notes' => $this->notes,
            'total_spend' => (float) ($this->total_spend ?? $this->spends_sum_spend ?? 0),
            'spends' => $this->whenLoaded('spends', function () {
                return $this->spends->map(fn ($s) => [
                    'id' => $s->id,
                    'spend_date' => optional($s->spend_date)->toDateString(),
                    'spend' => (float) $s->spend,
                    'currency' => $s->currency,
                    'impressions' => $s->impressions,
                    'clicks' => $s->clicks,
                    'notes' => $s->notes,
                ]);
            }),
            'created_at' => optional($this->created_at)->toDateTimeString(),
            'updated_at' => optional($this->updated_at)->toDateTimeString(),
        ];
    }
}
