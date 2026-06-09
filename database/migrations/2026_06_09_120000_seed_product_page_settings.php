<?php

use App\Enums\Activity;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $table = config('settings.repositories.database.table', 'settings');
        $now = now();
        $defaults = [
            'product_page_related_status'     => Activity::ENABLE,
            'product_page_related_autoscroll' => Activity::ENABLE,
            'product_page_related_speed'      => 3800,
            'product_page_related_touch'      => Activity::ENABLE,
            'product_page_related_direction'  => 'rtl',
        ];

        foreach ($defaults as $key => $value) {
            $exists = DB::table($table)
                ->where('group', 'product_page')
                ->where('key', $key)
                ->exists();

            if (!$exists) {
                DB::table($table)->insert([
                    'group'      => 'product_page',
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
            ->where('group', 'product_page')
            ->whereIn('key', [
                'product_page_related_status',
                'product_page_related_autoscroll',
                'product_page_related_speed',
                'product_page_related_touch',
                'product_page_related_direction',
            ])
            ->delete();
    }
};
