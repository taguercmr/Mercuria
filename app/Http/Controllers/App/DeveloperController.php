<?php

namespace App\Http\Controllers\App;

use App\Http\Controllers\Controller;
use App\Models\ApiKey;
use App\Services\Billing\PlanQuotaService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use RuntimeException;

class DeveloperController extends Controller
{
    public function index(Request $request, PlanQuotaService $quotas): Response
    {
        $tenant = $request->user()->currentTenant;
        abort_unless($tenant, 403);

        return Inertia::render('App/Developers/Index', [
            'usage' => $quotas->usageSummary($tenant),
            'apiKeys' => ApiKey::query()
                ->where('tenant_id', $tenant->id)
                ->latest()
                ->get(['id', 'name', 'key_prefix', 'last_used_at', 'requests_count', 'is_active', 'created_at']),
            'plainKey' => $request->session()->pull('plain_api_key'),
            'docs' => [
                'base_url' => url('/api/v1'),
                'endpoints' => [
                    'GET /api/v1/mercuriale/articles?q=&rubrique=&page=',
                    'GET /api/v1/mercuriale/articles/{reference}',
                    'GET /api/v1/mercuriale/rubriques',
                ],
            ],
        ]);
    }

    public function storeKey(Request $request, PlanQuotaService $quotas): RedirectResponse
    {
        $tenant = $request->user()->currentTenant;
        abort_unless($tenant, 403);

        try {
            $quotas->ensureApiAccess($tenant);
        } catch (RuntimeException $e) {
            return back()->with('error', $e->getMessage());
        }

        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'],
        ]);

        $issued = ApiKey::issue($tenant, $request->user(), $data['name']);

        return back()
            ->with('success', 'Clé API créée. Copiez-la maintenant, elle ne sera plus affichée.')
            ->with('plain_api_key', $issued['plain']);
    }

    public function revokeKey(Request $request, ApiKey $apiKey): RedirectResponse
    {
        abort_unless($apiKey->tenant_id === $request->user()->current_tenant_id, 403);
        $apiKey->update(['is_active' => false]);

        return back()->with('success', 'Clé API révoquée.');
    }
}
