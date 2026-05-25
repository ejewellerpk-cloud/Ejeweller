<?php

namespace App\Analytics\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AnalyticsEvent extends Model
{
    protected $table = 'analytics_events';

    protected $fillable = [
        'site_id', 'session_id', 'visitor_id', 'event_uuid', 'event_name',
        'event_category', 'page_url', 'page_title', 'product_id', 'product_sku',
        'revenue', 'currency', 'order_id', 'properties', 'occurred_at',
        'event_date', 'ingested_at',
    ];

    protected $casts = [
        'properties' => 'array',
        'occurred_at' => 'datetime',
        'event_date' => 'date',
        'ingested_at' => 'datetime',
        'revenue' => 'decimal:2',
    ];

    public function site(): BelongsTo
    {
        return $this->belongsTo(AnalyticsSite::class, 'site_id');
    }
}
