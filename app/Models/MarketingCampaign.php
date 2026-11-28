<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class MarketingCampaign extends Model
{
    protected $table = 'marketing_campaigns';

    protected $fillable = [
        'name',
        'channel',
        'objective',
        'status',
        'utm_source',
        'utm_medium',
        'utm_campaign',
        'utm_content',
        'utm_term',
        'landing_path',
        'daily_budget',
        'total_budget',
        'currency',
        'starts_at',
        'ends_at',
        'external_campaign_id',
        'notes',
    ];

    protected $casts = [
        'daily_budget' => 'decimal:2',
        'total_budget' => 'decimal:2',
        'starts_at' => 'date',
        'ends_at' => 'date',
    ];

    protected $appends = ['tracking_url', 'utm_query'];

    public function spends(): HasMany
    {
        return $this->hasMany(MarketingCampaignSpend::class, 'marketing_campaign_id');
    }

    public function products(): BelongsToMany
    {
        return $this->belongsToMany(Product::class, 'marketing_campaign_product')
            ->withPivot('sort_order')
            ->withTimestamps()
            ->orderByPivot('sort_order');
    }

    public function getUtmQueryAttribute(): string
    {
        $params = array_filter([
            'utm_source' => $this->utm_source,
            'utm_medium' => $this->utm_medium,
            'utm_campaign' => $this->utm_campaign,
            'utm_content' => $this->utm_content,
            'utm_term' => $this->utm_term,
        ], fn ($v) => $v !== null && $v !== '');

        return http_build_query($params);
    }

    public function getTrackingUrlAttribute(): string
    {
        $base = rtrim(config('app.url'), '/');
        $path = '/' . ltrim((string) ($this->landing_path ?: '/'), '/');
        if ($path === '//') {
            $path = '/';
        }

        return $base . $path . '?' . $this->utm_query;
    }

    public static function slugifyCampaignName(string $name): string
    {
        $slug = Str::slug($name);
        if ($slug === '') {
            $slug = 'campaign-' . Str::lower(Str::random(6));
        }

        $base = $slug;
        $i = 1;
        while (static::query()->where('utm_campaign', $slug)->exists()) {
            $slug = $base . '-' . $i;
            $i++;
        }

        return $slug;
    }
}
