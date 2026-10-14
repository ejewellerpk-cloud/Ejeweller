<?php

use App\Enums\Role as EnumRole;
use App\Models\User;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Artisan;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

return new class extends Migration
{
    public function up(): void
    {
        $permission = Permission::firstOrCreate(
            ['name' => 'intelligence', 'guard_name' => 'sanctum'],
            ['title' => 'Analytics', 'url' => 'intelligence']
        );

        // Move Analytics off roles → per-administrator direct permission
        $adminUsers = User::role(EnumRole::ADMIN)->get();
        foreach ($adminUsers as $user) {
            // Preserve access for existing admins that previously had it via role
            if (!$user->hasDirectPermission('intelligence')) {
                $user->givePermissionTo($permission);
            }
        }

        foreach (Role::all() as $role) {
            if ($role->permissions()->where('name', 'intelligence')->exists()) {
                $role->revokePermissionTo($permission);
            }
        }

        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        try {
            Artisan::call('permission:cache-reset');
        } catch (\Throwable $e) {
            //
        }
    }

    public function down(): void
    {
        $permission = Permission::where('name', 'intelligence')->where('guard_name', 'sanctum')->first();
        if (!$permission) {
            return;
        }

        User::permission('intelligence')->each(function (User $user) use ($permission) {
            $user->revokePermissionTo($permission);
        });

        Role::find(EnumRole::ADMIN)?->givePermissionTo($permission);

        app()[PermissionRegistrar::class]->forgetCachedPermissions();
    }
};
