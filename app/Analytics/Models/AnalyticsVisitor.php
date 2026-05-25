<?php

namespace App\Analytics\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AnalyticsVisitor extends Model
{
    protected $table = 'analytics_visitors';

    protected $fillable = [
        'site_id', 'visitor_uuid', 'user_id', 'first_source', 'first_medium',
        'first_campaign', 'first_seen_at', 'last_seen_at', 'session_count',
        'order_count', 'lifetime_value',
    ];

    protected $casts = [
        'first_seen_at' => 'datetime',
        'last_seen_at' => 'datetime',
        'lifetime_value' => 'decimal:2',
    ];

    public function site(): BelongsTo
    {
        return $this->belongsTo(AnalyticsSite::class, 'site_id');
    }

    public function sessions(): HasMany
    {
        return $this->hasMany(AnalyticsSession::class, 'visitor_id');
    }
}
