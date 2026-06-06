<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

class Outlet extends Model implements HasMedia
{
    use HasFactory, InteractsWithMedia;

    protected $fillable = ['name', 'email', 'phone', 'country_code', 'latitude', 'longitude', 'city', 'state', 'zip_code', 'address', 'status'];
    
    protected $casts = [
        'id'           => 'integer',
        'name'         => 'string',
        'email'        => 'string',
        'phone'        => 'string',
        'country_code' => 'string',
        'latitude'     => 'string',
        'longitude'    => 'string',
        'city'         => 'string',
        'state'        => 'string',
        'zip_code'     => 'string',
        'address'      => 'string',
        'status'       => 'integer',
    ];

    public function getImageAttribute(): string
    {
        if (!empty($this->getFirstMediaUrl('outlet'))) {
            return asset($this->getFirstMediaUrl('outlet'));
        }
        return asset('images/default/outlet.png');
    }

    public function registerMediaConversions(?Media $media = null): void
    {
        $this->addMediaConversion('thumb')->width(400)->height(300)->format('webp')->quality(70)->nonOptimized();
    }
}
