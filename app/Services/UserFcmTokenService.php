<?php

namespace App\Services;

use App\Enums\FcmPlatform;
use App\Enums\Role;
use App\Http\Requests\PaginateRequest;
use App\Http\Requests\TokenStoreRequest;
use App\Models\User;
use App\Models\UserFcmToken;
use Exception;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use App\Libraries\QueryExceptionLibrary;

class UserFcmTokenService
{
    public function __construct(private UserSessionService $userSessionService)
    {
    }

    /**
     * @throws Exception
     */
    public function register(User $user, TokenStoreRequest $request): UserFcmToken
    {
        try {
            $platform = $request->input('platform', FcmPlatform::WEB);

            if ($request->device_id) {
                UserFcmToken::where('user_id', $user->id)
                    ->where('platform', $platform)
                    ->where('device_id', $request->device_id)
                    ->where('token', '!=', $request->token)
                    ->update(['is_active' => false]);
            }

            $record = UserFcmToken::updateOrCreate(
                ['token' => $request->token],
                [
                    'user_id'      => $user->id,
                    'platform'     => $platform,
                    'device_name'  => $request->device_name ?: $this->defaultDeviceName($platform),
                    'device_id'    => $request->device_id,
                    'user_agent'   => $request->userAgent(),
                    'ip_address'   => $request->ip(),
                    'last_used_at' => now(),
                    'is_active'    => true,
                ]
            );

            $this->syncLegacyUserToken($user, $platform, $request->token);

            return $record;
        } catch (Exception $exception) {
            Log::info($exception->getMessage());
            throw new Exception(QueryExceptionLibrary::message($exception), 422);
        }
    }

    public function listForUser(User $user): Collection
    {
        return UserFcmToken::query()
            ->where('user_id', $user->id)
            ->where('is_active', true)
            ->orderByDesc('last_used_at')
            ->orderByDesc('id')
            ->get();
    }

    public function listAllForGroup(string $group, PaginateRequest $request): LengthAwarePaginator
    {
        $query = UserFcmToken::query()
            ->where('is_active', true)
            ->with(['user:id,name,email,phone'])
            ->whereHas('user', function (Builder $userQuery) use ($group, $request) {
                $this->applyRoleGroupFilter($userQuery, $group);
                $this->applyUserSearchFilter($userQuery, $request);
            });

        foreach (['platform', 'device_name'] as $field) {
            if ($request->filled($field)) {
                $query->where($field, 'like', '%' . $request->get($field) . '%');
            }
        }

        if ($request->filled('ip_address')) {
            $query->where('ip_address', 'like', '%' . $request->get('ip_address') . '%');
        }

        return $query
            ->orderByDesc('last_used_at')
            ->orderByDesc('id')
            ->paginate($request->get('per_page', 10));
    }

    public function revokeToken(User $user, int $tokenId): void
    {
        $token = UserFcmToken::where('user_id', $user->id)->where('id', $tokenId)->first();

        if (!$token) {
            throw new \Symfony\Component\HttpKernel\Exception\NotFoundHttpException(trans('all.message.fcm_token_not_found'));
        }

        $token->update(['is_active' => false]);
        $this->clearLegacyTokenIfMatches($user, $token);
    }

    public function revokeAllForUser(User $user, ?int $exceptTokenId = null): int
    {
        $query = UserFcmToken::where('user_id', $user->id)->where('is_active', true);

        if ($exceptTokenId !== null) {
            $query->where('id', '!=', $exceptTokenId);
        }

        $tokens = $query->get();
        $count  = $tokens->count();

        if ($count > 0) {
            UserFcmToken::whereIn('id', $tokens->pluck('id'))->update(['is_active' => false]);
            $user->update(['web_token' => null, 'device_token' => null]);
        }

        return $count;
    }

    public function revokeByTokenString(User $user, string $tokenString): void
    {
        $token = UserFcmToken::where('user_id', $user->id)
            ->where('token', $tokenString)
            ->where('is_active', true)
            ->first();

        if ($token) {
            $token->update(['is_active' => false]);
            $this->clearLegacyTokenIfMatches($user, $token);
        }
    }

    public function deactivateByToken(string $tokenString): void
    {
        $token = UserFcmToken::where('token', $tokenString)->where('is_active', true)->with('user')->first();

        if (!$token) {
            return;
        }

        $token->update(['is_active' => false]);
        $this->clearLegacyTokenIfMatches($token->user, $token);
    }

    public function getActiveTokenStrings(?array $userIds = null, ?int $roleId = null): array
    {
        $query = UserFcmToken::query()->where('is_active', true);

        if ($userIds !== null) {
            $query->whereIn('user_id', $userIds);
        }

        if ($roleId !== null && $roleId > 0) {
            $query->whereHas('user.roles', fn (Builder $roleQuery) => $roleQuery->where('id', $roleId));
        }

        return $query->pluck('token')->unique()->filter()->values()->all();
    }

    public function getActiveTokenStringsForRoles(array $roleIds): array
    {
        if (blank($roleIds)) {
            return [];
        }

        return UserFcmToken::query()
            ->where('is_active', true)
            ->whereHas('user.roles', fn (Builder $roleQuery) => $roleQuery->whereIn('id', $roleIds))
            ->pluck('token')
            ->unique()
            ->filter()
            ->values()
            ->all();
    }

    public function authorizeAdminCanManage(User $admin, User $target): void
    {
        $this->userSessionService->authorizeAdminCanManageSessions($admin, $target);
    }

    public function authorizeAdminCanListAll(User $admin, string $group): void
    {
        $this->userSessionService->authorizeAdminCanListAllSessions($admin, $group);
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

    private function syncLegacyUserToken(User $user, string $platform, string $token): void
    {
        if ($platform === FcmPlatform::WEB) {
            $user->update(['web_token' => $token]);

            return;
        }

        $user->update(['device_token' => $token]);
    }

    private function clearLegacyTokenIfMatches(User $user, UserFcmToken $token): void
    {
        $updates = [];

        if ($user->web_token === $token->token) {
            $updates['web_token'] = null;
        }

        if ($user->device_token === $token->token) {
            $updates['device_token'] = null;
        }

        if (!blank($updates)) {
            $user->update($updates);
        }
    }

    private function defaultDeviceName(string $platform): string
    {
        return match ($platform) {
            FcmPlatform::ANDROID => 'Android Device',
            FcmPlatform::IOS     => 'iOS Device',
            default              => 'Web Browser',
        };
    }
}
