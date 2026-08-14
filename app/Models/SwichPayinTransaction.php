<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SwichPayinTransaction extends Model
{
    protected $table = 'swich_payin_transactions';

    protected $fillable = [
        'order_id',
        'gateway_slug',
        'method',
        'customer_transaction_id',
        'swich_order_id',
        'swich_transaction_id',
        'consumer_number',
        'msisdn',
        'amount',
        'status',
        'channel_id',
        'category_id',
        'payload',
    ];

    protected $casts = [
        'order_id' => 'integer',
        'amount' => 'decimal:2',
        'channel_id' => 'integer',
        'category_id' => 'integer',
        'payload' => 'array',
    ];

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }
}
