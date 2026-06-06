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
            ->where(function ($query) {
                $query->where('language', 'pos_and_orders')
                    ->orWhere('name', 'Pos & Orders');
            })
            ->where('url', '#')
            ->first();

        if (!$parent) {
            return;
        }

        $this->insertMenuIfMissing((int) $parent->id, [
            'name'     => 'WhatsApp Order',
            'language' => 'whatsapp_order',
            'url'      => 'whatsapp-order',
            'icon'     => 'lab lab-line-whatsapp',
        ]);

        $this->insertMenuIfMissing((int) $parent->id, [
            'name'     => 'WhatsApp Orders',
            'language' => 'whatsapp_orders',
            'url'      => 'whatsapp-orders',
            'icon'     => 'lab lab-line-push-notification',
        ]);

        Permission::firstOrCreate(
            ['name' => 'whatsapp-order'],
            ['title' => 'WhatsApp Order', 'url' => 'whatsapp-order', 'guard_name' => 'sanctum']
        );

        Permission::firstOrCreate(
            ['name' => 'whatsapp-orders'],
            ['title' => 'WhatsApp Orders', 'url' => 'whatsapp-orders', 'guard_name' => 'sanctum']
        );

        Role::find(EnumRole::ADMIN)?->givePermissionTo(['whatsapp-order', 'whatsapp-orders']);
        Role::find(EnumRole::MANAGER)?->givePermissionTo(['whatsapp-order', 'whatsapp-orders']);
        Role::find(EnumRole::POS_OPERATOR)?->givePermissionTo(['whatsapp-order', 'whatsapp-orders']);

        $posOrderPermission = Permission::where('name', 'pos-orders')->first();
        if ($posOrderPermission) {
            $roleIds = DB::table('role_has_permissions')
                ->where('permission_id', $posOrderPermission->id)
                ->pluck('role_id');

            foreach ($roleIds as $roleId) {
                Role::find($roleId)?->givePermissionTo(['whatsapp-order', 'whatsapp-orders']);
            }
        }
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
        //
    }
};
