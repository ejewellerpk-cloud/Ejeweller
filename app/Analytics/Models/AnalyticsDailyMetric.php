<?php

namespace App\Analytics\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AnalyticsDailyMetric extends Model
{
    protected $table = 'analytics_daily_metrics';

    protected $fillable = [
        'site_id', 'metric_date', 'visitors', 'sessions', 'page_views', 'orders',
        'revenue', 'bounces', 'add_to_carts', 'checkouts_started', 'checkouts_abandoned',
        'by_source', 'by_device',
    ];

    protected $casts = [
        'metric_date' => 'date',
        'revenue' => 'decimal:2',
        'by_source' => 'array',
        'by_device' => 'array',
    ];

    public function site(): BelongsTo
    {
        return $this->belongsTo(AnalyticsSite::class, 'site_id');
    }
}
