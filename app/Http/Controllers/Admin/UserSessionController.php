<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Resources\UserSessionResource;
use App\Models\User;
use App\Services\UserSessionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;

class UserSessionController extends Controller
{
    public function __construct(private UserSessionService $userSessionService)
    {
    }

    public function index(Request $request, User $user): JsonResponse
    {
        try {
            $this->userSessionService->authorizeAdminCanManageSessions($request->user(), $user);

            $sessions = $this->userSessionService->listForUser($user);

            return response()->json([
                'data'          => UserSessionResource::collection($sessions),
                'total_devices' => $sessions->count(),
            ]);
        } catch (\Throwable $exception) {
            return $this->errorResponse($exception);
        }
    }

    public function destroy(Request $request, User $user, int $token): JsonResponse
    {
        try {
            $this->userSessionService->authorizeAdminCanManageSessions($request->user(), $user);
            $this->userSessionService->revokeToken($user, $token);

            return response()->json([
                'message' => trans('all.message.session_logout_success'),
            ]);
        } catch (\Throwable $exception) {
            return $this->errorResponse($exception);
        }
    }

    public function destroyAll(Request $request, User $user): JsonResponse
    {
        try {
            $this->userSessionService->authorizeAdminCanManageSessions($request->user(), $user);
            $count = $this->userSessionService->revokeAllExcept($user);

            return response()->json([
                'message'       => trans('all.message.all_sessions_logout_success'),
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
