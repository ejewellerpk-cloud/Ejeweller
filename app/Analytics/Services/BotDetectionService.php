<?php

namespace App\Analytics\Services;

class BotDetectionService
{
    public function isBot(?string $userAgent): bool
    {
        if (blank($userAgent)) {
            return false;
        }

        $ua = strtolower($userAgent);
        foreach (config('analytics.bot_user_agents', []) as $needle) {
            if (str_contains($ua, strtolower($needle))) {
                return true;
            }
        }

        return false;
    }
}
