<?php

namespace App\Services;

use App\Enums\Role;
use App\Http\Requests\PaginateRequest;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Laravel\Sanctum\PersonalAccessToken;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class UserSessionService
{
    private const GUARD = 'sanctum';

    public function listForUser(User $user, ?int $currentTokenId = null): Collection
    {
        return $user->tokens()
            ->orderByRaw('COALESCE(last_used_at, created_at) DESC')
            ->get()
            ->map(function (PersonalAccessToken $token) use ($currentTokenId) {
                $token->setAttribute('is_current', $currentTokenId !== null && (int) $token->id === (int) $currentTokenId);

                return $token;
            });
    }

    public function listAllForGroup(string $group, PaginateRequest $request): LengthAwarePaginator
    {
        $query = PersonalAccessToken::query()
            ->where('tokenable_type', User::class)
            ->with(['tokenable:id,name,email'])
            ->whereHasMorph('tokenable', [User::class], function (Builder $userQuery) use ($group, $request) {
                $this->applyRoleGroupFilter($userQuery, $group);
                $this->applyUserSearchFilter($userQuery, $request);
            });

        if ($request->filled('device_name')) {
            $query->where('device_name', 'like', '%' . $request->get('device_name') . '%');
        }

        if ($request->filled('browser')) {
            $query->where('user_agent', 'like', '%' . $request->get('browser') . '%');
        }

        if ($request->filled('ip_address')) {
            $query->where('ip_address', 'like', '%' . $request->get('ip_address') . '%');
        }

        return $query
            ->orderByRaw('COALESCE(last_used_at, created_at) DESC')
            ->paginate($request->get('per_page', 10));
    }

    private function applyRoleGroupFilter(Builder $query, string $group): void
    {
        match ($group) {
            'administrator' => $query->role(Role::ADMIN),
            'customer'      => $query->role(Role::CUSTOMER),
            'employee'      => $query->whereHas('roles', function (Builder $roleQuery) {
                $roleQuery->whereNotIn('id', [Role::ADMIN, Role::CUSTOMER]);
            }),
            default         => throw new \InvalidArgumentException('Invalid role group'),
        };
    }

    private function applyUserSearchFilter(Builder $query, PaginateRequest $request): void
    {
        foreach (['name', 'email', 'phone'] as $field) {
            if ($request->filled($field)) {
                $query->where($field, 'like', '%' . $request->get($field) . '%');
            }
        }
    }

    public function revokeToken(User $user, int $tokenId): void
    {
        $token = $user->tokens()->where('id', $tokenId)->first();

        if (!$token) {
            throw new NotFoundHttpException(trans('all.message.session_not_found'));
        }

        $token->delete();
    }

    public function revokeAllExcept(User $user, ?int $exceptTokenId = null): int
    {
        $query = $user->tokens();

        if ($exceptTokenId !== null) {
            $query->where('id', '!=', $exceptTokenId);
        }

        return $query->delete();
    }

    public function authorizeAdminCanManageSessions(User $admin, User $target): void
    {
        $target->loadMissing('roles');
        $admin->loadMissing('roles');

        $permission = match (true) {
            $target->hasRole(Role::ADMIN) => 'administrators_show',
            $target->hasRole(Role::CUSTOMER) => 'customers_show',
            $target->hasRole([Role::MANAGER, Role::POS_OPERATOR, Role::STUFF]) => 'employees_show',
            default => null,
        };

        if ($permission === null || !$admin->hasPermissionTo($permission, self::GUARD)) {
            throw new AccessDeniedHttpException(trans('all.message.permission_denied'));
        }
    }

    public function authorizeAdminCanListAllSessions(User $admin, string $group): void
    {
        $admin->loadMissing('roles');

        $permission = match ($group) {
            'administrator' => 'administrators_show',
            'customer'      => 'customers_show',
            'employee'      => 'employees_show',
            default         => null,
        };

        if ($permission === null || !$admin->hasPermissionTo($permission, self::GUARD)) {
            throw new AccessDeniedHttpException(trans('all.message.permission_denied'));
        }
    }
}
