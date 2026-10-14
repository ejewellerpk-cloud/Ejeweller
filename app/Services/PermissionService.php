<?php

namespace App\Services;


use App\Http\Requests\PermissionRequest;
use App\Libraries\AppLibrary;
use App\Libraries\QueryExceptionLibrary;
use App\Models\User;
use Exception;
use Illuminate\Support\Facades\Log;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class PermissionService
{
    public const USER_MANAGED_PERMISSIONS = ['intelligence'];

    /**
     * @throws Exception
     */
    public function permission(Role $role, ?User $user = null): object
    {
        try {
            $permissions = Permission::get();

            if ($user) {
                $granted = $user->getAllPermissions()->pluck('name', 'id');
            } else {
                $granted = Permission::join(
                    "role_has_permissions",
                    "role_has_permissions.permission_id",
                    "=",
                    "permissions.id"
                )->where("role_has_permissions.role_id", $role->id)->get()->pluck('name', 'id');
            }

            return AppLibrary::permissionWithAccess($permissions, $granted);
        } catch (Exception $exception) {
            Log::info($exception->getMessage());
            throw new Exception(QueryExceptionLibrary::message($exception), 422);
        }
    }

    /**
     * @throws Exception
     */
    public function update(PermissionRequest $request, Role $role): Role
    {
        try {
            $permissionIds = collect($request->get('permissions', []))
                ->filter()
                ->values();

            $permissions = Permission::whereIn('id', $permissionIds)
                ->whereNotIn('name', self::USER_MANAGED_PERMISSIONS)
                ->get();

            $role->syncPermissions($permissions);

            // Keep Analytics off roles — managed per administrator only
            foreach (self::USER_MANAGED_PERMISSIONS as $name) {
                if ($role->hasPermissionTo($name)) {
                    $role->revokePermissionTo($name);
                }
            }

            return $role;
        } catch (Exception $exception) {
            Log::info($exception->getMessage());
            throw new Exception(QueryExceptionLibrary::message($exception), 422);
        }
    }
}
