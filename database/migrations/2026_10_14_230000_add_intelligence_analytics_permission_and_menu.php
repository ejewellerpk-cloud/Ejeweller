<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Permission;

return new class extends Migration
{
    public function up(): void
    {
        Permission::firstOrCreate(
            ['name' => 'intelligence', 'guard_name' => 'sanctum'],
            ['title' => 'Analytics', 'url' => 'intelligence']
        );

        // No sidebar menu — Analytics uses its own separate dashboard.
        // Access is per-administrator (direct permission), not role-wide.
        // See 2026_10_14_233000_move_intelligence_permission_to_administrators.

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
