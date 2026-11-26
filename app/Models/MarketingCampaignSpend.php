<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MarketingCampaignSpend extends Model
{
    protected $table = 'marketing_campaign_spends';

    protected $fillable = [
        'marketing_campaign_id',
        'spend_date',
        'spend',
        'currency',
        'impressions',
        'clicks',
        'notes',
    ];

    protected $casts = [
        'spend_date' => 'date',
        'spend' => 'decimal:2',
        'impressions' => 'integer',
        'clicks' => 'integer',
    ];

    public function campaign(): BelongsTo
    {
        return $this->belongsTo(MarketingCampaign::class, 'marketing_campaign_id');
    }
}
