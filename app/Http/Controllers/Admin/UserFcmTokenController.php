<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\PaginateRequest;
use App\Http\Resources\UserFcmTokenResource;
use App\Http\Resources\UserFcmTokenWithUserResource;
use App\Models\User;
use App\Services\UserFcmTokenService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;

class UserFcmTokenController extends Controller
{
    public function __construct(private UserFcmTokenService $userFcmTokenService)
    {
    }

    public function indexAllAdministrators(PaginateRequest $request): JsonResponse|\Illuminate\Http\Resources\Json\AnonymousResourceCollection
    {
        return $this->indexAll($request, 'administrator');
    }

    public function indexAllCustomers(PaginateRequest $request): JsonResponse|\Illuminate\Http\Resources\Json\AnonymousResourceCollection
    {
        return $this->indexAll($request, 'customer');
    }

    public function indexAllEmployees(PaginateRequest $request): JsonResponse|\Illuminate\Http\Resources\Json\AnonymousResourceCollection
    {
        return $this->indexAll($request, 'employee');
    }

    public function indexAll(PaginateRequest $request, string $group): JsonResponse|\Illuminate\Http\Resources\Json\AnonymousResourceCollection
    {
        try {
            $this->userFcmTokenService->authorizeAdminCanListAll($request->user(), $group);
            $tokens = $this->userFcmTokenService->listAllForGroup($group, $request);

            return UserFcmTokenWithUserResource::collection($tokens)->additional([
                'total_devices' => $tokens->total(),
            ]);
        } catch (\Throwable $exception) {
            return $this->errorResponse($exception);
        }
    }

    public function index(Request $request, User $user): JsonResponse
    {
        try {
            $user->loadMissing('roles');
            $this->userFcmTokenService->authorizeAdminCanManage($request->user(), $user);
            $tokens = $this->userFcmTokenService->listForUser($user);

            return response()->json([
                'data'          => UserFcmTokenResource::collection($tokens),
                'total_devices' => $tokens->count(),
            ]);
        } catch (\Throwable $exception) {
            return $this->errorResponse($exception);
        }
    }

    public function destroy(Request $request, User $user, int $token): JsonResponse
    {
        try {
            $this->userFcmTokenService->authorizeAdminCanManage($request->user(), $user);
            $this->userFcmTokenService->revokeToken($user, $token);

            return response()->json([
                'message' => trans('all.message.fcm_token_revoke_success'),
            ]);
        } catch (\Throwable $exception) {
            return $this->errorResponse($exception);
        }
    }

    public function destroyAll(Request $request, User $user): JsonResponse
    {
        try {
            $this->userFcmTokenService->authorizeAdminCanManage($request->user(), $user);
            $count = $this->userFcmTokenService->revokeAllForUser($user);

            return response()->json([
                'message'       => trans('all.message.all_fcm_tokens_revoke_success'),
                'revoked_count' => $count,
            ]);
        } catch (\Throwable $exception) {
            return $this->errorResponse($exception);
        }
    }

    private function errorResponse(\Throwable $exception): JsonResponse
    {
        $code = $exception instanceof HttpExceptionInterface ? $exception->getStatusCode() : 422;

        return response()->json(['status' => false, 'message' => $exception->getMessage()], $code);
    }
}
