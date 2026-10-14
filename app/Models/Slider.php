<?php

namespace App\Models;

use Spatie\Image\Enums\Fit;
use Spatie\MediaLibrary\HasMedia;
use Illuminate\Database\Eloquent\Model;
use Spatie\MediaLibrary\InteractsWithMedia;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

class Slider extends Model implements HasMedia
{
    use InteractsWithMedia;

    protected $table = "sliders";
    protected $fillable = ['title', 'link', 'description', 'status'];
    protected $casts = [
        'id'          => 'integer',
        'title'       => 'string',
        'description' => 'string',
        'status'      => 'integer',
        'link'        => 'string',
    ];

    public function getImageAttribute(): string
    {
        if (!empty($this->getFirstMediaUrl('slider'))) {
            $slider = $this->getMedia('slider')->last();

            return $this->resolveSliderUrl($slider, ['cover', 'mobile']);
        }
        return asset('images/default/slider.png');
    }

    /** Mobile LCP-friendly crop (~900w WebP). */
    public function getMobileImageAttribute(): string
    {
        if (!empty($this->getFirstMediaUrl('slider'))) {
            $slider = $this->getMedia('slider')->last();

            return $this->resolveSliderUrl($slider, ['mobile', 'cover']);
        }
        return asset('images/default/slider.png');
    }

    protected function resolveSliderUrl(Media $slider, array $conversions): string
    {
        foreach ($conversions as $conversion) {
            if ($slider->hasGeneratedConversion($conversion)) {
                return $slider->getUrl($conversion);
            }
        }

        return $slider->getUrl() ?: asset('images/default/slider.png');
    }

    public function registerMediaConversions(?Media $media = null): void
    {
        $this->addMediaConversion('cover')->fit(Fit::Fill, 1689, 600)->format('webp')->quality(68)->nonOptimized();
        $this->addMediaConversion('mobile')->fit(Fit::Fill, 900, 320)->format('webp')->quality(68)->nonOptimized();
    }
}
