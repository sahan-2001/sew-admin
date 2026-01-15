<?php

namespace App\Http\Middleware;

use Closure;
use App\Models\Site;
use Illuminate\Http\Request;

class SetDefaultSite
{
    public function handle(Request $request, Closure $next)
    {
        // First, try to set default site if none is selected
        if (!session()->has('site_id')) {
            $site = Site::where('is_active', true)->first();
            
            if ($site) {
                session(['site_id' => $site->id]);
            } else {
                // If no active site exists at all
                if ($request->route()->getName() !== 'sites.select' && 
                    !$request->is('sites*')) {
                    return redirect()->route('sites.select')
                        ->with('error', 'No active sites available. Please configure a site first.');
                }
            }
        }
        
        // Verify the selected site still exists and is active
        if (session()->has('site_id')) {
            $site = Site::where('id', session('site_id'))
                ->where('is_active', true)
                ->first();
            
            if (!$site) {
                // Clear invalid site_id from session
                session()->forget('site_id');
                
                // Try to find another active site
                $newSite = Site::where('is_active', true)->first();
                
                if ($newSite) {
                    session(['site_id' => $newSite->id]);
                } else {
                    // No active sites available
                    if ($request->route()->getName() !== 'sites.select' && 
                        !$request->is('sites*')) {
                        return redirect()->route('sites.select')
                            ->with('error', 'The selected site is no longer available. Please select another site.');
                    }
                }
            }
        }

        return $next($request);
    }
}