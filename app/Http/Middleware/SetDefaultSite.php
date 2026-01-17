<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use App\Models\Site;

class SetDefaultSite
{
    public function handle(Request $request, Closure $next)
    {
        $user = auth()->user();

        // Skip if not logged in
        if (!$user) {
            return $next($request);
        }

        // ✅ Superuser/admin bypass
        if ($user->hasRole('admin') || $user->hasRole('superuser')) {
            return $next($request);
        }

        // Get active sites user can access
        $accessibleSites = $user->sites()
            ->where('is_active', true)
            ->get();

        // ❌ No sites assigned → abort
        if ($accessibleSites->isEmpty()) {
            abort(403, 'No site access assigned.');
        }

        // ✅ Session already set → validate it
        if (session()->has('site_id')) {
            if ($accessibleSites->contains('id', session('site_id'))) {
                return $next($request);
            }

            // Invalid site → clear
            session()->forget('site_id');
        }

        // ✅ Only ONE site → auto select
        if ($accessibleSites->count() === 1) {
            session(['site_id' => $accessibleSites->first()->id]);
            return $next($request);
        }

        // 🔁 Multiple sites → redirect to selector
        if (!$request->routeIs('sites.select')) {
            return redirect()->route('sites.select');
        }

        return $next($request);
    }
}
