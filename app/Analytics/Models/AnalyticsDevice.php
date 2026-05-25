<?php

namespace App\Analytics\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AnalyticsDevice extends Model
{
    protected $table = 'analytics_devices';

    protected $fillable = [
        'session_id', 'ip_hash', 'country', 'city', 'timezone', 'browser',
        'browser_version', 'os', 'device_type', 'screen', 'language',
        'network_type', 'user_agent',
    ];

    public function session(): BelongsTo
    {
        return $this->belongsTo(AnalyticsSession::class, 'session_id');
    }
}
