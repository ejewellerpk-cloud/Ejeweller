<?php

use App\Enums\MenuType;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $productsParent = DB::table('menus')
            ->where('url', '#')
            ->where(function ($query) {
                $query->where('language', 'product_and_stock')
                    ->orWhere('language', 'products');
            })
            ->first();

        if (!$productsParent) {
            return;
        }

        DB::table('menus')
            ->where('id', $productsParent->id)
            ->update([
                'name'       => 'Products',
                'language'   => 'products',
                'icon'       => 'lab lab-line-items',
                'updated_at' => now(),
            ]);

        $productsParentId = (int) $productsParent->id;

        $this->upsertChild($productsParentId, [
            'name'     => 'Products',
            'language' => 'products',
            'url'      => 'products',
            'icon'     => 'lab lab-line-items',
        ]);

        $this->upsertChild($productsParentId, [
            'name'     => 'Product Categories',
            'language' => 'product_categories',
            'url'      => 'settings/product-categories',
            'icon'     => 'lab lab-line-item-categories',
        ]);

        $this->upsertChild($productsParentId, [
            'name'     => 'Product Attributes',
            'language' => 'product_attributes',
            'url'      => 'settings/product-attributes',
            'icon'     => 'lab lab-line-item-attributes',
        ]);

        $this->upsertChild($productsParentId, [
            'name'     => 'Product Brands',
            'language' => 'product_brands',
            'url'      => 'settings/product-brands',
            'icon'     => 'lab lab-line-brand',
        ]);

        $stockParent = DB::table('menus')
            ->where('url', '#')
            ->where('language', 'stock')
            ->where('parent', 0)
            ->first();

        if (!$stockParent) {
            $stockParentId = DB::table('menus')->insertGetId([
                'name'       => 'Stock',
                'language'   => 'stock',
                'url'        => '#',
                'icon'       => 'lab lab-line-stock',
                'priority'   => 100,
                'status'     => 1,
                'parent'     => 0,
                'type'       => MenuType::BACKEND,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        } else {
            DB::table('menus')
                ->where('id', $stockParent->id)
                ->update([
                    'name'       => 'Stock',
                    'icon'       => 'lab lab-line-stock',
                    'updated_at' => now(),
                ]);
            $stockParentId = (int) $stockParent->id;
        }

        foreach ([
            ['name' => 'Stock', 'language' => 'stock', 'url' => 'stock', 'icon' => 'lab lab-line-stock'],
            ['name' => 'Purchase', 'language' => 'purchase', 'url' => 'purchase', 'icon' => 'lab lab-line-add-purchase'],
            ['name' => 'Damages', 'language' => 'damages', 'url' => 'damages', 'icon' => 'lab lab-line-addons'],
            ['name' => 'Reviews', 'language' => 'reviews', 'url' => 'reviews', 'icon' => 'lab lab-line-rating-star'],
        ] as $item) {
            $existing = DB::table('menus')->where('url', $item['url'])->first();

            if ($existing) {
                DB::table('menus')
                    ->where('id', $existing->id)
                    ->update([
                        'parent'     => $stockParentId,
                        'name'       => $item['name'],
                        'language'   => $item['language'],
                        'icon'       => $item['icon'],
                        'updated_at' => now(),
                    ]);
            } else {
                DB::table('menus')->insert([
                    'name'       => $item['name'],
                    'language'   => $item['language'],
                    'url'        => $item['url'],
                    'icon'       => $item['icon'],
                    'priority'   => 100,
                    'status'     => 1,
                    'parent'     => $stockParentId,
                    'type'       => MenuType::BACKEND,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }
    }

    private function upsertChild(int $parentId, array $data): void
    {
        $existing = DB::table('menus')
            ->where('url', $data['url'])
            ->first();

        if ($existing) {
            DB::table('menus')
                ->where('id', $existing->id)
                ->update([
                    'parent'     => $parentId,
                    'name'       => $data['name'],
                    'language'   => $data['language'],
                    'icon'       => $data['icon'],
                    'updated_at' => now(),
                ]);

            return;
        }

        DB::table('menus')->insert([
            'name'       => $data['name'],
            'language'   => $data['language'],
            'url'        => $data['url'],
            'icon'       => $data['icon'],
            'priority'   => 100,
            'status'     => 1,
            'parent'     => $parentId,
            'type'       => MenuType::BACKEND,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function down(): void
    {
        //
    }
};
