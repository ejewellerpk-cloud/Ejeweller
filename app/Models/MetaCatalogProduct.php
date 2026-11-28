<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class MetaCatalogProduct extends Model
{
    protected $table = 'meta_catalog_products';

    public const STATUS_PENDING = 'pending';
    public const STATUS_SYNCED = 'synced';
    public const STATUS_FAILED = 'failed';
    public const STATUS_STALE = 'stale';
    public const STATUS_NEEDS_ATTENTION = 'needs_attention';

    protected $fillable = [
        'product_id',
        'retailer_id',
        'meta_catalog_id',
        'meta_product_id',
        'sync_status',
        'content_hash',
        'last_payload',
        'validation_errors',
        'validation_warnings',
        'last_error',
        'batch_handle',
        'last_synced_at',
        'last_validated_at',
    ];

    protected $casts = [
        'last_payload' => 'array',
        'validation_errors' => 'array',
        'validation_warnings' => 'array',
        'last_synced_at' => 'datetime',
        'last_validated_at' => 'datetime',
    ];

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function logs(): HasMany
    {
        return $this->hasMany(MetaCatalogSyncLog::class, 'product_id', 'product_id');
    }
}
