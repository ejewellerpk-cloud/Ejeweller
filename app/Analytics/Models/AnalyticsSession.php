<?php

namespace App\Analytics\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

class AnalyticsSession extends Model
{
    protected $table = 'analytics_sessions';

    protected $fillable = [
        'site_id', 'visitor_id', 'session_uuid', 'user_id', 'landing_page', 'exit_page',
        'referrer', 'source', 'medium', 'campaign', 'content', 'term',
        'page_views', 'duration_seconds', 'max_scroll_depth', 'is_bounce', 'is_active',
        'started_at', 'ended_at',
    ];

    protected $casts = [
        'is_bounce' => 'boolean',
        'is_active' => 'boolean',
        'started_at' => 'datetime',
        'ended_at' => 'datetime',
    ];

    public function site(): BelongsTo
    {
        return $this->belongsTo(AnalyticsSite::class, 'site_id');
    }

    public function visitor(): BelongsTo
    {
        return $this->belongsTo(AnalyticsVisitor::class, 'visitor_id');
    }

    public function device(): HasOne
    {
        return $this->hasOne(AnalyticsDevice::class, 'session_id');
    }
}
