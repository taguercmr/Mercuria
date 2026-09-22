<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Tenant;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class TenantController extends Controller
{
    public function index(Request $request): Response
    {
        $q = trim((string) $request->get('q', ''));

        $tenants = Tenant::query()
            ->withCount('users')
            ->when($q !== '', fn ($query) => $query->where(function ($inner) use ($q): void {
                $inner->where('name', 'like', "%{$q}%")
                    ->orWhere('email', 'like', "%{$q}%")
                    ->orWhere('slug', 'like', "%{$q}%");
            }))
            ->when($request->filled('plan'), fn ($query) => $query->where('plan', $request->get('plan')))
            ->latest()
            ->paginate(25)
            ->withQueryString();

        return Inertia::render('Admin/Tenants', [
            'tenants' => $tenants,
            'filters' => [
                'q' => $q,
                'plan' => $request->get('plan'),
            ],
            'plans' => array_keys(config('mercuria.plans', [])),
        ]);
    }

    public function update(Request $request, Tenant $tenant): RedirectResponse
    {
        $data = $request->validate([
            'plan' => ['sometimes', 'in:free,pro'],
            'is_active' => ['sometimes', 'boolean'],
            'subscription_ends_at' => ['nullable', 'date'],
        ]);

        if (array_key_exists('plan', $data) && $data['plan'] === 'pro' && ! $tenant->subscribed_at) {
            $data['subscribed_at'] = now();
        }

        if (($data['plan'] ?? null) === 'free') {
            $data['subscription_ends_at'] = null;
        }

        $tenant->update($data);

        return back()->with('success', 'Tenant mis a jour.');
    }
}
