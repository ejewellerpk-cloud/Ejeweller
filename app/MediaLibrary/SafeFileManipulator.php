<?php

namespace App\MediaLibrary;

use Spatie\MediaLibrary\Conversions\ConversionCollection;
use Spatie\MediaLibrary\Conversions\FileManipulator;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

class SafeFileManipulator extends FileManipulator
{
    public function performConversions(
        ConversionCollection $conversions,
        Media $media,
        bool $onlyMissing = false
    ): self {
        $previousLimit = (string) ini_get('memory_limit');
        @ini_set('memory_limit', '256M');

        try {
            return parent::performConversions($conversions, $media, $onlyMissing);
        } finally {
            @ini_set('memory_limit', $previousLimit);
        }
    }
}
