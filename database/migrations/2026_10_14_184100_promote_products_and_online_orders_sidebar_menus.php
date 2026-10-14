<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Keep Dashboard first.
        DB::table('menus')
            ->where('url', 'dashboard')
            ->where('parent', 0)
            ->update([
                'priority'   => 10,
                'updated_at' => now(),
            ]);

        // Products list → top-level, right under Dashboard.
        DB::table('menus')
            ->where('url', 'products')
            ->update([
                'parent'     => 0,
                'name'       => 'Products',
                'language'   => 'products',
                'icon'       => 'lab lab-line-items',
                'priority'   => 20,
                'updated_at' => now(),
            ]);

        // Online Orders → top-level, right under Products.
        DB::table('menus')
            ->where('url', 'online-orders')
            ->update([
                'parent'     => 0,
                'name'       => 'Online Orders',
                'language'   => 'online_orders',
                'icon'       => 'lab lab-line-online-orders',
                'priority'   => 30,
                'updated_at' => now(),
            ]);

        // Rename the old Products accordion so it doesn't clash with the top-level Products link.
        DB::table('menus')
            ->where('url', '#')
            ->where('parent', 0)
            ->whereIn('language', ['products', 'product_and_stock'])
            ->update([
                'name'       => 'Catalog',
                'language'   => 'catalog',
                'icon'       => 'lab lab-line-item-categories',
                'priority'   => 40,
                'updated_at' => now(),
            ]);

        // Preserve a sensible order for remaining top-level sections.
        $sectionPriorities = [
            'stock'            => 50,
            'pos_and_orders'   => 60,
            'promo'            => 70,
            'communications'   => 80,
            'users'            => 90,
            'accounts'         => 100,
            'reports'          => 110,
            'setup'            => 120,
        ];

        foreach ($sectionPriorities as $language => $priority) {
            DB::table('menus')
                ->where('url', '#')
                ->where('parent', 0)
                ->where('language', $language)
                ->update([
                    'priority'   => $priority,
                    'updated_at' => now(),
                ]);
        }
    }

    public function down(): void
    {
        $productsParent = DB::table('menus')
            ->where('url', '#')
            ->where('parent', 0)
            ->whereIn('language', ['catalog', 'products', 'product_and_stock'])
            ->first();

        $ordersParent = DB::table('menus')
            ->where('url', '#')
            ->where('parent', 0)
            ->where('language', 'pos_and_orders')
            ->first();

        if ($productsParent) {
            DB::table('menus')
                ->where('id', $productsParent->id)
                ->update([
                    'name'       => 'Products',
                    'language'   => 'products',
                    'icon'       => 'lab lab-line-items',
                    'priority'   => 100,
                    'updated_at' => now(),
                ]);

            DB::table('menus')
                ->where('url', 'products')
                ->update([
                    'parent'     => $productsParent->id,
                    'priority'   => 100,
                    'updated_at' => now(),
                ]);
        }

        if ($ordersParent) {
            DB::table('menus')
                ->where('url', 'online-orders')
                ->update([
                    'parent'     => $ordersParent->id,
                    'priority'   => 100,
                    'updated_at' => now(),
                ]);
        }
    }
};
