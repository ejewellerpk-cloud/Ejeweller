<?php

use App\Enums\Role as EnumRole;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

return new class extends Migration
{
    public function up(): void
    {
        $permission = Permission::firstOrCreate(
            ['name' => 'postex-shipments', 'guard_name' => 'sanctum'],
            [
                'title' => 'PostEx Shipments',
                'url'   => 'postex-shipments',
            ]
        );

        Role::find(EnumRole::ADMIN)?->givePermissionTo($permission);
        Role::find(EnumRole::MANAGER)?->givePermissionTo($permission);

        // Ensure sidebar menu exists (idempotent with earlier menu migration).
        if (!DB::table('menus')->where('url', 'postex-shipments')->exists()) {
            $onlineOrders = DB::table('menus')->where('url', 'online-orders')->first();
            DB::table('menus')->insert([
                'name'       => 'PostEx Shipments',
                'language'   => 'postex_shipments',
                'url'        => 'postex-shipments',
                'icon'       => 'lab lab-line-truck-check',
                'priority'   => $onlineOrders ? ((int) $onlineOrders->priority + 1) : 31,
                'status'     => 1,
                'parent'     => 0,
                'type'       => \App\Enums\MenuType::BACKEND,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        try {
            Artisan::call('permission:cache-reset');
        } catch (\Throwable $e) {
            // best-effort
        }
    }

    public function down(): void
    {
        $permission = Permission::where('name', 'postex-shipments')
            ->where('guard_name', 'sanctum')
            ->first();

        if ($permission) {
            DB::table('role_has_permissions')->where('permission_id', $permission->id)->delete();
            $permission->delete();
        }

        try {
            Artisan::call('permission:cache-reset');
        } catch (\Throwable $e) {
            //
        }
    }
};
