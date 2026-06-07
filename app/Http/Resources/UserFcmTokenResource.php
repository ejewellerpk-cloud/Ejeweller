<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class UserFcmTokenResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'           => $this->id,
            'platform'     => $this->platform,
            'device_name'  => $this->device_name ?: ucfirst($this->platform),
            'device_id'    => $this->device_id,
            'token_preview'=> $this->tokenPreview(),
            'ip_address'   => $this->ip_address,
            'last_used_at' => $this->last_used_at?->toIso8601String(),
            'created_at'   => $this->created_at?->toIso8601String(),
            'is_active'    => (bool) $this->is_active,
        ];
    }

    private function tokenPreview(): string
    {
        $token = (string) $this->token;

        if (strlen($token) <= 16) {
            return $token;
        }

        return substr($token, 0, 8) . '…' . substr($token, -6);
    }
}
