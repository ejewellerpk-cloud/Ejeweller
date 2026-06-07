<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserFcmToken extends Model
{
    protected $fillable = [
        'user_id',
        'token',
        'platform',
        'device_name',
        'device_id',
        'user_agent',
        'ip_address',
        'last_used_at',
        'is_active',
    ];

    protected $casts = [
        'id'           => 'integer',
        'user_id'      => 'integer',
        'token'        => 'string',
        'platform'     => 'string',
        'device_name'  => 'string',
        'device_id'    => 'string',
        'user_agent'   => 'string',
        'ip_address'   => 'string',
        'last_used_at' => 'datetime',
        'is_active'    => 'boolean',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
