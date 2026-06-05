<?php

namespace App\Http\Controllers\Auth;


use Exception;
use App\Http\Controllers\Controller;
use App\Http\Requests\TokenStoreRequest;
use App\Services\AuthTokenService;
use Laravel\Sanctum\PersonalAccessToken;
use Illuminate\Http\JsonResponse;


class RefreshTokenController extends Controller
{
    public function __construct(private AuthTokenService $authTokenService)
    {
    }

    public function refreshToken(TokenStoreRequest $request)
    {
        try {
            $sanctumToken = $request->token;
            $token = PersonalAccessToken::findToken($sanctumToken);
            $user = $token->tokenable;

            $token->delete();
            $newToken = $this->authTokenService->issueToken($user, $request);

            return new JsonResponse([
                'token'      => $newToken,
            ], 201);
        } catch (Exception $exception) {
            return response(['status' => false, 'message' => trans('all.message.token_is_invalid')], 422);
        }
    }
}