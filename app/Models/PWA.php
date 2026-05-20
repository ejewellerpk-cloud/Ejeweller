<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Spatie\Image\Enums\CropPosition;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

class PWA extends Model implements HasMedia
{
    use HasFactory;

    use InteractsWithMedia;

    protected $fillable = ['id'];
    protected $table = "pwa";

    public function getSplashAttribute(): string
    {
        if (!empty($this->getFirstMediaUrl('pwa_splash'))) {
            $pwa = $this->getMedia('pwa_splash')->first();
            return $pwa->getUrl('D_2048x2732');
        }
        return asset('images/icons/splash-2048x2732.png');
    }

    public function getIconAttribute(): string
    {
        if (!empty($this->getFirstMediaUrl('pwa_icon'))) {
            $pwa = $this->getMedia('pwa_icon')->first();
            return $pwa->getUrl('D_512x512');
        }
        return asset('images/icons/icon-512x512.png');
    }

    public function registerMediaConversions(?Media $media = null): void
    {
        $this->addMediaConversion('D_2048x2732')->performOnCollections('pwa_splash')
             ->crop(2048, 2732, CropPosition::Center)->keepOriginalImageFormat()->nonOptimized();
        $this->addMediaConversion('D_1668x2388')->performOnCollections('pwa_splash')
             ->crop(1668, 2388, CropPosition::Center)->keepOriginalImageFormat()->nonOptimized();
             $this->addMediaConversion('D_1668x2224')->performOnCollections('pwa_splash')
             ->crop(1668, 2224, CropPosition::Center)->keepOriginalImageFormat()->nonOptimized();
             $this->addMediaConversion('D_1536x2048')->performOnCollections('pwa_splash')
             ->crop(1536, 2048, CropPosition::Center)->keepOriginalImageFormat()->nonOptimized();
             $this->addMediaConversion('D_1242x2688')->performOnCollections('pwa_splash')
             ->crop(1242, 2688, CropPosition::Center)->keepOriginalImageFormat()->nonOptimized();
             $this->addMediaConversion('D_1242x2208')->performOnCollections('pwa_splash')
             ->crop(1242, 2208, CropPosition::Center)->keepOriginalImageFormat()->nonOptimized();
             $this->addMediaConversion('D_1125x2436')->performOnCollections('pwa_splash')
             ->crop(1125, 2436, CropPosition::Center)->keepOriginalImageFormat()->nonOptimized();
             $this->addMediaConversion('D_828x1792')->performOnCollections('pwa_splash')
             ->crop(828, 1792, CropPosition::Center)->keepOriginalImageFormat()->nonOptimized();
             $this->addMediaConversion('D_750x1334')->performOnCollections('pwa_splash')
             ->crop(750, 1334, CropPosition::Center)->keepOriginalImageFormat()->nonOptimized();
             $this->addMediaConversion('D_640x1136')->performOnCollections('pwa_splash')
             ->crop(640, 1136, CropPosition::Center)->keepOriginalImageFormat()->nonOptimized();

        $this->addMediaConversion('D_512x512')->performOnCollections('pwa_icon')
             ->crop(512, 512, CropPosition::Center)->keepOriginalImageFormat()->nonOptimized();
             $this->addMediaConversion('D_384x384')->performOnCollections('pwa_icon')
             ->crop(384, 384, CropPosition::Center)->keepOriginalImageFormat()->nonOptimized();
             $this->addMediaConversion('D_192x192')->performOnCollections('pwa_icon')
             ->crop(192, 192, CropPosition::Center)->keepOriginalImageFormat()->nonOptimized();
             $this->addMediaConversion('D_152x152')->performOnCollections('pwa_icon')
             ->crop(152, 152, CropPosition::Center)->keepOriginalImageFormat()->nonOptimized();
             $this->addMediaConversion('D_144x144')->performOnCollections('pwa_icon')
             ->crop(144, 144, CropPosition::Center)->keepOriginalImageFormat()->nonOptimized();
             $this->addMediaConversion('D_128x128')->performOnCollections('pwa_icon')
             ->crop(128, 128, CropPosition::Center)->keepOriginalImageFormat()->nonOptimized();
             $this->addMediaConversion('D_96x96')->performOnCollections('pwa_icon')
             ->crop(96, 96, CropPosition::Center)->keepOriginalImageFormat()->nonOptimized();
             $this->addMediaConversion('D_72x72')->performOnCollections('pwa_icon')
             ->crop(72, 72, CropPosition::Center)->keepOriginalImageFormat()->nonOptimized();
     }

}