<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $map = [
            'settings/product-categories' => 'product-categories',
            'settings/product-attributes' => 'product-attributes',
            'settings/product-brands'     => 'product-brands',
        ];

        foreach ($map as $from => $to) {
            DB::table('menus')
                ->where('url', $from)
                ->update([
                    'url'        => $to,
                    'updated_at' => now(),
                ]);
        }
    }

    public function down(): void
    {
        $map = [
            'product-categories' => 'settings/product-categories',
            'product-attributes' => 'settings/product-attributes',
            'product-brands'     => 'settings/product-brands',
        ];

        foreach ($map as $from => $to) {
            DB::table('menus')
                ->where('url', $from)
                ->update([
                    'url'        => $to,
                    'updated_at' => now(),
                ]);
        }
    }
};
