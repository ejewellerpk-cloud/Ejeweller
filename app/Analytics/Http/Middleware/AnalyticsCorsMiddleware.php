<?php

namespace App\Analytics\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class AnalyticsCorsMiddleware
{
    public function handle(Request $request, Closure $next)
    {
        $site = $request->attributes->get('analytics_site');
        $origin = $request->header('Origin');
        $allowed = $site?->allowed_origins ?? ['*'];

        if ($request->isMethod('OPTIONS')) {
            return response('', 204)->withHeaders($this->headers($origin, $allowed));
        }

        $response = $next($request);

        foreach ($this->headers($origin, $allowed) as $key => $value) {
            $response->headers->set($key, $value);
        }

        return $response;
    }

    private function headers(?string $origin, array $allowed): array
    {
        $allowOrigin = '*';
        if ($origin && (in_array('*', $allowed, true) || in_array($origin, $allowed, true))) {
            $allowOrigin = $origin;
        }

        return [
            'Access-Control-Allow-Origin' => $allowOrigin,
            'Access-Control-Allow-Methods' => 'POST, OPTIONS',
            'Access-Control-Allow-Headers' => 'Content-Type, X-Analytics-Key, Accept-Encoding',
            'Access-Control-Max-Age' => '86400',
        ];
    }
}
