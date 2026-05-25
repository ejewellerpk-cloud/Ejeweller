<?php

namespace App\Analytics\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AnalyticsSiteMember extends Model
{
    protected $table = 'analytics_site_members';

    protected $fillable = ['site_id', 'user_id', 'role'];

    public function site(): BelongsTo
    {
        return $this->belongsTo(AnalyticsSite::class, 'site_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
