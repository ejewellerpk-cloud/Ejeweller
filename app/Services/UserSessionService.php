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
        $roleId = (int) ($target->roles->first()?->id ?? 0);

        $allowed = match ($roleId) {
            Role::ADMIN    => $admin->can('administrators_show'),
            Role::CUSTOMER => $admin->can('customers_show'),
            Role::MANAGER, Role::POS_OPERATOR, Role::STUFF => $admin->can('employees_show'),
            default        => false,
        };

        if (!$allowed) {
            throw new AccessDeniedHttpException(trans('all.message.permission_denied'));
        }
    }
}
