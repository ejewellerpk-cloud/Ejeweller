<?php

use App\Enums\MenuType;
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
        Permission::firstOrCreate(
            ['name' => 'intelligence', 'guard_name' => 'sanctum'],
            ['title' => 'Analytics', 'url' => 'intelligence']
        );

        $reportsParent = DB::table('menus')
            ->where(function ($query) {
                $query->where('language', 'reports')
                    ->orWhere('name', 'Reports');
            })
            ->where('url', '#')
            ->first();

        if ($reportsParent) {
            $exists = DB::table('menus')
                ->where('url', 'intelligence')
                ->where('parent', $reportsParent->id)
                ->exists();

            if (!$exists) {
                DB::table('menus')->insert([
                    'name'       => 'Analytics',
                    'language'   => 'intelligence',
                    'url'        => 'intelligence',
                    'icon'       => 'lab lab-line-analytic',
                    'priority'   => 100,
                    'status'     => 1,
                    'parent'     => $reportsParent->id,
                    'type'       => MenuType::BACKEND,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }

        // Admin only by default — assign to other roles via Settings → Role & Permissions
        Role::find(EnumRole::ADMIN)?->givePermissionTo('intelligence');

        try {
            Artisan::call('permission:cache-reset');
        } catch (\Throwable $e) {
            // cache reset is best-effort
        }
    }

    public function down(): void
    {
        DB::table('menus')->where('url', 'intelligence')->delete();

        $permission = Permission::where('name', 'intelligence')->where('guard_name', 'sanctum')->first();
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
