<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureTenantRole
{
    /**
     * @param  string  ...$roles  Allowed roles (e.g. owner, editor)
     */
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        $user = $request->user();
        if (! $user) {
            abort(401);
        }

        if ($user->is_platform_admin) {
            return $next($request);
        }

        $role = $user->tenantRole();
        if (! $role || ($roles !== [] && ! in_array($role, $roles, true))) {
            abort(403, 'Permissions insuffisantes pour cette action.');
        }

        return $next($request);
    }
}
