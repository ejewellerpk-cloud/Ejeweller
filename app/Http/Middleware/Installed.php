<?php

namespace App\Http\Middleware;

use Closure;

class Installed
{
    /**
     * Handle an incoming request.
     *
     * @param \Illuminate\Http\Request $request
     * @param \Closure $next
     * @return mixed
     */
    public function handle($request, Closure $next)
    {
        if (!file_exists(storage_path('installed'))) {
            // Never redirect API/JSON — redirect loops cause HTTP 508 on some hosts (LiteSpeed/Cloudflare)
            if ($request->is('api/*') || $request->expectsJson()) {
                return response()->json([
                    'status' => false,
                    'message' => 'Application is not installed.',
                ], 503);
            }

            return redirect('/install');
        }

        return $next($request);
    }
}
