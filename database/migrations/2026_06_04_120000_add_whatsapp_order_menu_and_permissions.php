<?php

use App\Enums\MenuType;
use App\Enums\Role as EnumRole;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

return new class extends Migration
{
    public function up(): void
    {
        $parent = DB::table('menus')
            ->where('language', 'pos_and_orders')
            ->where('url', '#')
            ->first();

        if ($parent) {
            $this->insertMenuIfMissing($parent->id, [
                'name'     => 'WhatsApp Order',
                'language' => 'whatsapp_order',
                'url'      => 'whatsapp-order',
                'icon'     => 'lab lab-whatsapp',
            ]);

            $this->insertMenuIfMissing($parent->id, [
                'name'     => 'WhatsApp Orders',
                'language' => 'whatsapp_orders',
                'url'      => 'whatsapp-orders',
                'icon'     => 'lab lab-line-push-notification',
            ]);
        }

        Permission::firstOrCreate(
            ['name' => 'whatsapp-order'],
            ['title' => 'WhatsApp Order', 'url' => 'whatsapp-order', 'guard_name' => 'sanctum']
        );

        Permission::firstOrCreate(
            ['name' => 'whatsapp-orders'],
            ['title' => 'WhatsApp Orders', 'url' => 'whatsapp-orders', 'guard_name' => 'sanctum']
        );

        $adminRole = Role::find(EnumRole::ADMIN);
        $adminRole?->givePermissionTo(['whatsapp-order', 'whatsapp-orders']);

        Role::find(EnumRole::MANAGER)?->givePermissionTo(['whatsapp-order', 'whatsapp-orders']);
        Role::find(EnumRole::POS_OPERATOR)?->givePermissionTo(['whatsapp-order', 'whatsapp-orders']);
    }

    private function insertMenuIfMissing(int $parentId, array $data): void
    {
        $exists = DB::table('menus')
            ->where('url', $data['url'])
            ->where('parent', $parentId)
            ->exists();

        if ($exists) {
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
        DB::table('menus')->whereIn('url', ['whatsapp-order', 'whatsapp-orders'])->delete();
        Permission::query()->whereIn('name', ['whatsapp-order', 'whatsapp-orders'])->delete();
    }
};
