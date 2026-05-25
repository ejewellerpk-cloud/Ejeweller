<?php

namespace App\Analytics\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AnalyticsSite extends Model
{
    protected $table = 'analytics_sites';

    protected $fillable = [
        'workspace_id', 'name', 'domain', 'public_key', 'secret_key_hash',
        'allowed_origins', 'timezone', 'currency', 'settings', 'is_active',
    ];

    protected $casts = [
        'allowed_origins' => 'array',
        'settings' => 'array',
        'is_active' => 'boolean',
    ];

    protected $hidden = ['secret_key_hash'];

    public function workspace(): BelongsTo
    {
        return $this->belongsTo(AnalyticsWorkspace::class, 'workspace_id');
    }

    public function members(): HasMany
    {
        return $this->hasMany(AnalyticsSiteMember::class, 'site_id');
    }

    public function events(): HasMany
    {
        return $this->hasMany(AnalyticsEvent::class, 'site_id');
    }

    public function isOriginAllowed(?string $origin): bool
    {
        if (blank($origin)) {
            return true;
        }

        $allowed = $this->allowed_origins ?? [];
        if (empty($allowed)) {
            return true;
        }

        if (in_array('*', $allowed, true)) {
            return true;
        }

        if (in_array($origin, $allowed, true)) {
            return true;
        }

        $host = parse_url($origin, PHP_URL_HOST);
        if ($host && ($host === $this->domain || $host === 'www.' . $this->domain)) {
            return true;
        }

        return false;
    }
}
