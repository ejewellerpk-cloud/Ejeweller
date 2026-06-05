<?php

namespace App\Http\Resources;

use App\Services\AuthTokenService;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class UserSessionWithUserResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $authTokenService = app(AuthTokenService::class);
        $user = $this->tokenable;

        return [
            'id'           => $this->id,
            'device_name'  => $this->device_name ?: 'Unknown Device',
            'browser'      => $authTokenService->parseBrowser($this->user_agent),
            'ip_address'   => $this->ip_address,
            'last_used_at' => $this->last_used_at?->toIso8601String(),
            'created_at'   => $this->created_at?->toIso8601String(),
            'is_current'   => false,
            'user'         => $user ? [
                'id'    => $user->id,
                'name'  => $user->name,
                'email' => $user->email,
            ] : null,
        ];
    }
}
