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
}
