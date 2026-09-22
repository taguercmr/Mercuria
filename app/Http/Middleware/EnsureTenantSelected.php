<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureTenantSelected
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (! $user) {
            return $next($request);
        }

        if ($user->is_platform_admin && $request->is('admin*')) {
            return $next($request);
        }

        if (! $user->current_tenant_id) {
            $tenant = $user->tenants()->first();

            if ($tenant) {
                $user->forceFill(['current_tenant_id' => $tenant->id])->save();
            }
        }

        if (! $user->fresh()->current_tenant_id && ! $request->routeIs('onboarding.*')) {
            return redirect()->route('onboarding.create');
        }

        return $next($request);
    }
}
