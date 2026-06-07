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
                $query->where('language', 'products')
                    ->orWhere('language', 'product_and_stock');
            })
            ->where('parent', 0)
            ->first();

        if (!$productsParent) {
            return;
        }

        $productsParentId = (int) $productsParent->id;

        DB::table('menus')
            ->where('id', $productsParentId)
            ->update([
                'name'       => 'Products',
                'language'   => 'products',
                'icon'       => 'lab lab-line-items',
                'updated_at' => now(),
            ]);

        $items = [
            ['name' => 'Products', 'language' => 'products', 'url' => 'products', 'icon' => 'lab lab-line-items', 'priority' => 100],
            ['name' => 'Product Categories', 'language' => 'product_categories', 'url' => 'product-categories', 'icon' => 'lab lab-line-item-categories', 'priority' => 110],
            ['name' => 'Product Attributes', 'language' => 'product_attributes', 'url' => 'product-attributes', 'icon' => 'lab lab-line-item-attributes', 'priority' => 120],
            ['name' => 'Product Brands', 'language' => 'product_brands', 'url' => 'product-brands', 'icon' => 'lab lab-line-brand', 'priority' => 130],
            ['name' => 'Product Sections', 'language' => 'product_sections', 'url' => 'product-sections', 'icon' => 'lab lab-line-product-section', 'priority' => 140],
            ['name' => 'Reviews', 'language' => 'reviews', 'url' => 'reviews', 'icon' => 'lab lab-line-rating-star', 'priority' => 150],
            ['name' => 'Units', 'language' => 'units', 'url' => 'settings/units', 'icon' => 'lab lab-line-unit', 'priority' => 160],
            ['name' => 'Taxes', 'language' => 'taxes', 'url' => 'settings/taxes', 'icon' => 'lab lab-line-taxes', 'priority' => 170],
        ];

        foreach ($items as $item) {
            $this->upsertChild($productsParentId, $item);
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
                    'priority'   => $data['priority'],
                    'updated_at' => now(),
                ]);

            return;
        }

        DB::table('menus')->insert([
            'name'       => $data['name'],
            'language'   => $data['language'],
            'url'        => $data['url'],
            'icon'       => $data['icon'],
            'priority'   => $data['priority'],
            'status'     => 1,
            'parent'     => $parentId,
            'type'       => MenuType::BACKEND,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function down(): void
    {
        $promoParent = DB::table('menus')
            ->where('url', '#')
            ->where('language', 'promo')
            ->where('parent', 0)
            ->first();

        $stockParent = DB::table('menus')
            ->where('url', '#')
            ->where('language', 'stock')
            ->where('parent', 0)
            ->first();

        if ($promoParent) {
            DB::table('menus')
                ->where('url', 'product-sections')
                ->update(['parent' => $promoParent->id, 'priority' => 100, 'updated_at' => now()]);
        }

        if ($stockParent) {
            DB::table('menus')
                ->where('url', 'reviews')
                ->update(['parent' => $stockParent->id, 'priority' => 100, 'updated_at' => now()]);
        }

        DB::table('menus')->whereIn('url', ['settings/units', 'settings/taxes'])->delete();
    }
};
