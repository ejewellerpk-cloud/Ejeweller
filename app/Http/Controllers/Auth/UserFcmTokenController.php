<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\TokenStoreRequest;
use App\Http\Resources\UserFcmTokenResource;
use App\Services\UserFcmTokenService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class UserFcmTokenController extends Controller
{
    public function __construct(private UserFcmTokenService $userFcmTokenService)
    {
    }

    public function index(Request $request): JsonResponse
    {
        $user   = $request->user();
        $tokens = $this->userFcmTokenService->listForUser($user);

        return response()->json([
            'data'          => UserFcmTokenResource::collection($tokens),
            'total_devices' => $tokens->count(),
        ]);
    }

    public function destroy(Request $request, int $token): JsonResponse
    {
        try {
            $this->userFcmTokenService->revokeToken($request->user(), $token);

            return response()->json([
                'message' => trans('all.message.fcm_token_revoke_success'),
            ]);
        } catch (\Exception $exception) {
            $code = method_exists($exception, 'getStatusCode') ? $exception->getStatusCode() : 422;

            return response()->json(['status' => false, 'message' => $exception->getMessage()], $code);
        }
    }

    public function destroyOthers(Request $request): JsonResponse
    {
        $currentTokenId = $request->integer('current_token_id') ?: null;
        $count          = $this->userFcmTokenService->revokeAllForUser($request->user(), $currentTokenId);

        return response()->json([
            'message'       => trans('all.message.other_fcm_tokens_revoke_success'),
            'revoked_count' => $count,
        ]);
    }

    public function destroyAll(Request $request): JsonResponse
    {
        $count = $this->userFcmTokenService->revokeAllForUser($request->user());

        return response()->json([
            'message'       => trans('all.message.all_fcm_tokens_revoke_success'),
            'revoked_count' => $count,
        ]);
    }

    public function revokeCurrent(TokenStoreRequest $request): JsonResponse
    {
        try {
            $this->userFcmTokenService->revokeByTokenString($request->user(), $request->token);

            return response()->json([
                'message' => trans('all.message.fcm_token_revoke_success'),
            ]);
        } catch (\Exception $exception) {
            return response()->json(['status' => false, 'message' => $exception->getMessage()], 422);
        }
    }
}
