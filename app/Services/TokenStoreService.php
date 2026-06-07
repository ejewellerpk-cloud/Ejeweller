<?php

namespace App\Services;

use App\Enums\FcmPlatform;
use Exception;
use Illuminate\Support\Facades\Log;
use App\Http\Requests\TokenStoreRequest;
use App\Libraries\QueryExceptionLibrary;

class TokenStoreService
{
    public function __construct(private UserFcmTokenService $userFcmTokenService)
    {
    }

    /**
     * @throws Exception
     */
    public function webToken(TokenStoreRequest $request): bool
    {
        try {
            $request->merge([
                'platform' => $request->input('platform', FcmPlatform::WEB),
            ]);
            $this->userFcmTokenService->register(auth()->user(), $request);

            return true;
        } catch (Exception $exception) {
            Log::info($exception->getMessage());
            throw new Exception(QueryExceptionLibrary::message($exception), 422);
        }
    }

    /**
     * @throws Exception
     */
    public function deviceToken(TokenStoreRequest $request): bool
    {
        try {
            $request->merge([
                'platform' => $request->input('platform', FcmPlatform::ANDROID),
            ]);
            $this->userFcmTokenService->register(auth()->user(), $request);

            return true;
        } catch (Exception $exception) {
            Log::info($exception->getMessage());
            throw new Exception(QueryExceptionLibrary::message($exception), 422);
        }
    }
}
