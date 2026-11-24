<?php

namespace App\Http\Middleware;

use App\Models\Business;
use App\Services\Tenant\TenantContext;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\View;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;

class ResolveTenant
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $business = null;

        // 1. Resolve from route parameter if present ({business_slug} or {business})
        $routeParam = $request->route('business_slug') ?? $request->route('business');

        if ($routeParam instanceof Business) {
            $business = $routeParam;
        } elseif (is_string($routeParam) && $routeParam !== '') {
            if (Str::isUuid($routeParam)) {
                $business = Business::find($routeParam);
            } else {
                $decoded = urldecode($routeParam);
                $business = Business::where('slug', $decoded)->orWhere('slug', $routeParam)->first();
            }

            if (!$business) {
                abort(404, 'Business not found.');
            }
        }

        // 2. Resolve from authenticated session for /app routes
        $user = $request->user();
        if (!$business && $user) {
            $activeBusinessId = session('active_business_id');

            if ($activeBusinessId && is_string($activeBusinessId) && Str::isUuid($activeBusinessId)) {
                $business = Business::find($activeBusinessId);
            }

            // Fallback to first business owned or joined by the user
            if (!$business) {
                $business = $user->ownedBusinesses()->first() ?? $user->businesses()->first();
                if ($business) {
                    session(['active_business_id' => $business->id]);
                }
            }

            // If user has no business and is trying to access dashboard, redirect to create business
            if (!$business && $request->is('app*') && !$request->routeIs('business.create', 'business.store', 'logout')) {
                return redirect()->route('business.create');
            }
        }

        // 3. Authorization check if business is resolved & user is logged in
        if ($business) {
            if ($user && !$user->belongsToBusiness($business) && !$request->is('menu*') && !$request->routeIs('public.*')) {
                abort(403, 'Unauthorized access to this business workspace.');
            }

            TenantContext::set($business);
            View::share('currentBusiness', $business);
        }

        return $next($request);
    }
}
