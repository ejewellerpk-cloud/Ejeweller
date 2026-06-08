<?php

use App\Enums\Activity;
use App\Services\RelatedProductsCarouselService;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $table = config('settings.repositories.database.table', 'settings');
        $now = now();
        $defaults = [
            'related_products_carousel_status' => Activity::ENABLE,
            'related_products_carousel_speed'  => RelatedProductsCarouselService::DEFAULT_AUTOPLAY_DELAY,
        ];

        foreach ($defaults as $key => $value) {
            $exists = DB::table($table)
                ->where('group', 'related_products_carousel')
                ->where('key', $key)
                ->exists();

            if (!$exists) {
                DB::table($table)->insert([
                    'group'      => 'related_products_carousel',
                    'key'        => $key,
                    'payload'    => json_encode($value),
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
            }
        }
    }

    public function down(): void
    {
        $table = config('settings.repositories.database.table', 'settings');

        DB::table($table)
            ->where('group', 'related_products_carousel')
            ->whereIn('key', [
                'related_products_carousel_status',
                'related_products_carousel_speed',
            ])
            ->delete();
    }
};
