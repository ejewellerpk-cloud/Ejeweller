<?php

namespace App\Services;

use App\Enums\Role;
use App\Models\User;
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
            ->orderByDesc('last_used_at')
            ->orderByDesc('created_at')
            ->get()
            ->map(function (PersonalAccessToken $token) use ($currentTokenId) {
                $token->setAttribute('is_current', $currentTokenId !== null && (int) $token->id === (int) $currentTokenId);

                return $token;
            });
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
}
