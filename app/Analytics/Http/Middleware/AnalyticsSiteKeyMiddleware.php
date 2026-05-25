<?php

namespace App\Analytics\Http\Middleware;

use App\Analytics\Contracts\AnalyticsSiteRepositoryInterface;
use Closure;
use Illuminate\Http\Request;

class AnalyticsSiteKeyMiddleware
{
    public function __construct(private readonly AnalyticsSiteRepositoryInterface $sites) {}

    public function handle(Request $request, Closure $next)
    {
        $key = trim((string) (
            $request->header('X-Analytics-Key')
            ?? $request->input('site_key')
            ?? $request->input('key')
            ?? ''
        ));

        if ($key === '') {
            return response()->json(['success' => false, 'message' => 'Missing analytics site key'], 401);
        }

        $site = $this->sites->findByPublicKey($key);
        if (!$site) {
            return response()->json(['success' => false, 'message' => 'Invalid analytics site key'], 401);
        }

        if (!$site->is_active) {
            return response()->json(['success' => false, 'message' => 'Analytics is disabled for this site'], 403);
        }

        $request->attributes->set('analytics_site', $site);

        return $next($request);
    }
}
