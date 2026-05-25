<?php

namespace App\Analytics\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AnalyticsWorkspace extends Model
{
    protected $table = 'analytics_workspaces';

    protected $fillable = ['name', 'slug', 'owner_id', 'settings', 'is_active'];

    protected $casts = [
        'settings' => 'array',
        'is_active' => 'boolean',
    ];

    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'owner_id');
    }

    public function sites(): HasMany
    {
        return $this->hasMany(AnalyticsSite::class, 'workspace_id');
    }
}
