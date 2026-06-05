<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class AuthTokenService
{
    public function issueToken(User $user, ?Request $request = null, ?string $deviceLabel = null): string
    {
        $request ??= request();

        $newToken = $user->createToken('auth_token');
        $accessToken = $newToken->accessToken;

        $userAgent = $request?->userAgent();
        $accessToken->forceFill([
            'device_name' => $deviceLabel ?: $this->parseDeviceName($userAgent),
            'ip_address'  => $request?->ip(),
            'user_agent'  => $userAgent ? Str::limit($userAgent, 1000) : null,
        ])->save();

        return $newToken->plainTextToken;
    }

    public function parseDeviceName(?string $userAgent): string
    {
        if (!$userAgent) {
            return 'Unknown Device';
        }

        if (preg_match('/iPhone/i', $userAgent)) {
            return 'iPhone';
        }
        if (preg_match('/iPad/i', $userAgent)) {
            return 'iPad';
        }
        if (preg_match('/Android/i', $userAgent)) {
            return preg_match('/Mobile/i', $userAgent) ? 'Android Phone' : 'Android Tablet';
        }
        if (preg_match('/Mobile/i', $userAgent)) {
            return 'Mobile Browser';
        }
        if (preg_match('/Windows/i', $userAgent)) {
            return 'Windows PC';
        }
        if (preg_match('/Macintosh|Mac OS/i', $userAgent)) {
            return 'Mac';
        }
        if (preg_match('/Linux/i', $userAgent)) {
            return 'Linux PC';
        }

        return 'Desktop Browser';
    }

    public function parseBrowser(?string $userAgent): string
    {
        if (!$userAgent) {
            return 'Unknown';
        }

        $browsers = [
            'Edg'     => 'Edge',
            'Chrome'  => 'Chrome',
            'Firefox' => 'Firefox',
            'Safari'  => 'Safari',
            'Opera'   => 'Opera',
        ];

        foreach ($browsers as $needle => $label) {
            if (stripos($userAgent, $needle) !== false) {
                return $label;
            }
        }

        return 'Browser';
    }
}
