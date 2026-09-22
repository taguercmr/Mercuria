<?php

namespace App\Http\Middleware;

use Illuminate\Http\Request;
use Inertia\Middleware;

class HandleInertiaRequests extends Middleware
{
    /**
     * The root template that is loaded on the first page visit.
     *
     * @var string
     */
    protected $rootView = 'app';

    /**
     * Determine the current asset version.
     */
    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    /**
     * Define the props that are shared by default.
     *
     * @return array<string, mixed>
     */
    public function share(Request $request): array
    {
        $user = $request->user();

        return [
            ...parent::share($request),
            'auth' => [
                'user' => $user ? [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'is_platform_admin' => (bool) $user->is_platform_admin,
                    'current_tenant_id' => $user->current_tenant_id,
                    'tenant_role' => $user->tenantRole(),
                    'can_write' => $user->is_platform_admin
                        || in_array($user->tenantRole(), ['owner', 'editor'], true),
                ] : null,
            ],
            'notifications' => $user ? [
                'unread_count' => $user->unreadNotifications()->count(),
            ] : ['unread_count' => 0],
            'tenant' => $user?->currentTenant?->only(['id', 'name', 'slug', 'plan', 'currency']),
            'tenants' => $user
                ? $user->tenants()->get(['tenants.id', 'tenants.name', 'tenants.slug', 'tenants.plan'])
                    ->map(fn ($t) => [
                        'id' => $t->id,
                        'name' => $t->name,
                        'slug' => $t->slug,
                        'plan' => $t->plan,
                        'role' => $t->pivot->role,
                    ])
                : [],
            'flash' => [
                'success' => fn () => $request->session()->get('success'),
                'error' => fn () => $request->session()->get('error'),
            ],
            'app' => [
                'name' => config('mercuria.name', config('app.name')),
                'mercurialeYear' => config('mercuria.mercuriale_year'),
            ],
        ];
    }
}
