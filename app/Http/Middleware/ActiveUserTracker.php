<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;

class ActiveUserTracker
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle(Request $request, Closure $next)
    {
        $response = $next($request);

        if (Auth::check()) {
            $user = Auth::user();
            $activeUsers = Cache::get('active-users', []);
            
            $activeUsers[$user->id] = [
                'id' => $user->id,
                'role_id' => $user->roles[0]->id ?? null,
                'last_seen' => now()->timestamp
            ];

            // Filter out users who haven't made a request in pichle 5 minutes
            $threshold = now()->subMinutes(5)->timestamp;
            $activeUsers = array_filter($activeUsers, function($activeUser) use ($threshold) {
                return $activeUser['last_seen'] >= $threshold;
            });

            Cache::put('active-users', $activeUsers, now()->addMinutes(10));
        }

        return $response;
    }
}
