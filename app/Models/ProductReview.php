<?php

namespace App\Models;

use App\Libraries\AppLibrary;
use Spatie\Image\Enums\Fit;
use Spatie\MediaLibrary\HasMedia;
use Illuminate\Database\Eloquent\Model;
use Spatie\MediaLibrary\InteractsWithMedia;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

class ProductReview extends Model implements HasMedia
{
    use InteractsWithMedia;

    /**
     * When true, media is stored without generating thumb/preview conversions
     * (used by the bulk reviews generator for speed). Original URLs still work.
     */
    public static bool $skipConversions = false;

    protected $table    = "product_reviews";
    protected $fillable = ['user_id', 'reviewer_name', 'product_id', 'star', 'review', 'status', 'is_verified_purchase'];
    protected $casts    = [
        'id'                   => 'integer',
        'user_id'              => 'integer',
        'reviewer_name'        => 'string',
        'product_id'           => 'integer',
        'star'                 => 'integer',
        'review'               => 'string',
        'status'               => 'integer',
        'is_verified_purchase' => 'integer',
    ];

    public function getDisplayNameAttribute(): string
    {
        $named = trim((string) ($this->reviewer_name ?? ''));
        if ($named !== '') {
            return $named;
        }

        return (string) ($this->user?->name ?: 'Guest');
    }

    /**
     * Full-size (or preview) URLs for lightbox / detail views.
     * List UIs should keep CSS sizing (e.g. w-20 h-20); do not serve only the tiny thumb here.
     */
    public function getImagesAttribute(): array
    {
        $response = [];
        $images = $this->getMedia('product-review');
        foreach ($images as $image) {
            $url = $this->resolveReviewMediaUrl($image);
            if ($url !== '') {
                $response[] = $url;
            }
        }
        return $response;
    }

    /**
     * Compact thumbs for admin cards / dense grids when needed.
     */
    public function getImageThumbsAttribute(): array
    {
        $response = [];
        $images = $this->getMedia('product-review');
        foreach ($images as $image) {
            $raw = ($image->hasGeneratedConversion('thumb') ? $image->getUrl('thumb') : null) ?: $image->getUrl();
            $url = AppLibrary::mediaUrl($raw, '');
            if ($url !== '') {
                $response[] = $url;
            }
        }
        return $response;
    }

    private function resolveReviewMediaUrl(Media $image): string
    {
        if ($image->hasGeneratedConversion('preview')) {
            return AppLibrary::mediaUrl($image->getUrl('preview'), '');
        }

        $original = AppLibrary::mediaUrl($image->getUrl(), '');
        if ($original !== '') {
            return $original;
        }

        if ($image->hasGeneratedConversion('thumb')) {
            return AppLibrary::mediaUrl($image->getUrl('thumb'), '');
        }

        return '';
    }

    public function registerMediaConversions(?Media $media = null): void
    {
        if (self::$skipConversions) {
            return;
        }

        $this->addMediaConversion('thumb')->fit(Fit::Fill, 112, 72)->format('webp')->quality(70)->nonOptimized();
        // Large enough for product-page lightbox without serving oversized originals.
        $this->addMediaConversion('preview')->fit(Fit::Max, 1600, 1600)->format('webp')->quality(82)->nonOptimized();
    }


    public function product(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function user(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(User::class)->withTrashed();
    }
}
