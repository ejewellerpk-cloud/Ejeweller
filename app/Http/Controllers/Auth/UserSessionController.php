<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Resources\UserSessionResource;
use App\Services\UserSessionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class UserSessionController extends Controller
{
    public function __construct(private UserSessionService $userSessionService)
    {
    }

    public function index(Request $request): JsonResponse
    {
        $user = $request->user();
        $currentTokenId = $user->currentAccessToken()?->id;
        $sessions = $this->userSessionService->listForUser($user, $currentTokenId);

        return response()->json([
            'data'          => UserSessionResource::collection($sessions),
            'total_devices' => $sessions->count(),
        ]);
    }

    public function destroy(Request $request, int $token): JsonResponse
    {
        try {
            $user = $request->user();
            $currentTokenId = $user->currentAccessToken()?->id;

            if ($currentTokenId !== null && (int) $token === (int) $currentTokenId) {
                return response()->json([
                    'status'  => false,
                    'message' => trans('all.message.cannot_revoke_current_session'),
                ], 422);
            }

            $this->userSessionService->revokeToken($user, $token);

            return response()->json([
                'message' => trans('all.message.session_logout_success'),
            ]);
        } catch (\Exception $exception) {
            $code = method_exists($exception, 'getStatusCode') ? $exception->getStatusCode() : 422;

            return response()->json(['status' => false, 'message' => $exception->getMessage()], $code);
        }
    }

    public function destroyOthers(Request $request): JsonResponse
    {
        $user = $request->user();
        $currentTokenId = $user->currentAccessToken()?->id;
        $count = $this->userSessionService->revokeAllExcept($user, $currentTokenId);

        return response()->json([
            'message'       => trans('all.message.other_sessions_logout_success'),
            'revoked_count' => $count,
        ]);
    }

    public function destroyAll(Request $request): JsonResponse
    {
        $user = $request->user();
        $count = $this->userSessionService->revokeAllExcept($user);

        return response()->json([
            'message'       => trans('all.message.all_sessions_logout_success'),
            'revoked_count' => $count,
        ]);
    }
}
