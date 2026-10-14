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
        Permission::firstOrCreate(
            ['name' => 'intelligence', 'guard_name' => 'sanctum'],
            ['title' => 'Analytics', 'url' => 'intelligence']
        );

        // No sidebar menu — Analytics uses its own separate dashboard.
        // Permission still gates API / role assignment.

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
