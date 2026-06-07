<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

class ProductVideo extends Model implements HasMedia
{
    use HasFactory, InteractsWithMedia;
    protected $table = "product_videos";
    protected $fillable = ['product_id', 'video_provider', 'link'];
    protected $casts = [
        'id'             => 'integer',
        'product_id'     => 'integer',
        'video_provider' => 'integer',
        'link'           => 'string',
    ];

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('product_video')->singleFile();
        $this->addMediaCollection('product_video_thumbnail')->singleFile();
    }

    public function getThumbnailUrlAttribute(): string
    {
        $url = $this->getFirstMediaUrl('product_video_thumbnail');

        return $url ? asset($url) : '';
    }
}
