<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MetaCatalogSyncLog extends Model
{
    protected $table = 'meta_catalog_sync_logs';

    protected $fillable = [
        'product_id',
        'action',
        'status',
        'message',
        'response',
    ];

    protected $casts = [
        'response' => 'array',
    ];

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }
}
