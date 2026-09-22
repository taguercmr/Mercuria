<?php

namespace App\Http\Middleware;

use App\Models\PlatformSetting;
use Closure;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Symfony\Component\HttpFoundation\Response;

class CheckMaintenanceMode
{
    public function handle(Request $request, Closure $next): Response
    {
        if (! PlatformSetting::maintenanceEnabled()) {
            return $next($request);
        }

        $user = $request->user();

        if ($user?->is_platform_admin) {
            return $next($request);
        }

        // Allow auth routes for platform admins who are not yet logged in? Keep login open.
        if ($request->routeIs('login', 'login.store', 'password.*', 'logout')) {
            return $next($request);
        }

        // Webhooks must remain reachable during maintenance.
        if ($request->is('webhooks/*') || $request->is('up')) {
            return $next($request);
        }

        if ($request->expectsJson() || $request->is('api/*')) {
            return response()->json([
                'message' => PlatformSetting::maintenanceMessage(),
            ], 503);
        }

        return Inertia::render('Maintenance', [
            'message' => PlatformSetting::maintenanceMessage(),
        ])->toResponse($request)->setStatusCode(503);
    }
}
