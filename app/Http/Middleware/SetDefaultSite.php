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

        if (!$user) {
            return $next($request);
        }

        // Superuser/admin bypass
        if ($user->hasRole('admin') || $user->hasRole('superuser')) {
            // Optionally set site_id to first site for consistency
            if (!session()->has('site_id')) {
                $firstSite = \App\Models\Site::first();
                if ($firstSite) {
                    session(['site_id' => $firstSite->id]);
                }
            }
            return $next($request);
        }

        // Get active sites user can access
        $accessibleSites = $user->sites()->where('is_active', true)->get();

        if ($accessibleSites->isEmpty()) {
            session()->flash('no_site_access', 'No site access assigned. Contact admin.');
            return $next($request);
        }

        // ✅ Only ONE site → auto select session
        if ($accessibleSites->count() === 1) {
            session(['site_id' => $accessibleSites->first()->id]);
            return $next($request);
        }

        // ✅ Validate session site_id
        if (session()->has('site_id')) {
            if ($accessibleSites->contains('id', session('site_id'))) {
                return $next($request);
            }
            session()->forget('site_id'); // invalid → clear
        }

        // Multiple sites → redirect to selector
        if (!$request->routeIs('sites.select')) {
            return redirect()->route('sites.select');
        }

        return $next($request);
    }
}
