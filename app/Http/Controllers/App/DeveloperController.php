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

        $baseUrl = url('/api/v1');

        return Inertia::render('App/Developers/Index', [
            'usage' => $quotas->usageSummary($tenant, $request->user()),
            'apiKeys' => ApiKey::query()
                ->where('tenant_id', $tenant->id)
                ->latest()
                ->get(['id', 'name', 'key_prefix', 'last_used_at', 'requests_count', 'is_active', 'created_at']),
            'plainKey' => $request->session()->pull('plain_api_key'),
            'docs' => [
                'base_url' => $baseUrl,
                'rate_limit' => '60 requêtes / minute / clé',
                'plan' => 'Pro (abonnement actif)',
                'auth_headers' => [
                    'Authorization: Bearer mrc_…',
                    'X-Api-Key: mrc_…',
                ],
                'auth_query' => 'api_key=mrc_… (déconseillé hors tests)',
                'endpoints' => [
                    [
                        'method' => 'GET',
                        'path' => '/mercuriale/rubriques',
                        'summary' => 'Liste toutes les rubriques de la mercuriale active (ou ?year=).',
                        'params' => [
                            ['name' => 'year', 'type' => 'integer', 'required' => false, 'description' => 'Année budget (défaut : année active plateforme).'],
                        ],
                        'example_request' => "curl -s -H \"X-Api-Key: mrc_…\" \\\n  {$baseUrl}/mercuriale/rubriques?year=2026",
                        'example_response' => [
                            'data' => [
                                [
                                    'code' => '35',
                                    'name' => 'INFORMATIQUE ET BUREAUTIQUE',
                                    'articles_count' => 2173,
                                ],
                            ],
                            'meta' => ['budget_year' => 2026],
                        ],
                    ],
                    [
                        'method' => 'GET',
                        'path' => '/mercuriale/articles',
                        'summary' => 'Recherche et pagination des articles mercuriale.',
                        'params' => [
                            ['name' => 'q', 'type' => 'string', 'required' => false, 'description' => 'Texte libre (référence ou désignation).'],
                            ['name' => 'rubrique', 'type' => 'string', 'required' => false, 'description' => 'Code rubrique (ex. 35).'],
                            ['name' => 'year', 'type' => 'integer', 'required' => false, 'description' => 'Année budget.'],
                            ['name' => 'page', 'type' => 'integer', 'required' => false, 'description' => 'Page (défaut 1).'],
                            ['name' => 'per_page', 'type' => 'integer', 'required' => false, 'description' => 'Résultats par page (défaut 50, max 100).'],
                        ],
                        'example_request' => "curl -s -H \"X-Api-Key: mrc_…\" \\\n  \"{$baseUrl}/mercuriale/articles?q=ordinateur&year=2026&per_page=2\"",
                        'example_response' => [
                            'data' => [
                                [
                                    'reference' => '35-001-220085',
                                    'designation' => 'ORDINATEUR PORTABLE …',
                                    'unit' => 'UNITE',
                                    'quantity' => '1.000',
                                    'unit_price' => 3420000,
                                    'currency' => 'XAF',
                                    'budget_year' => 2026,
                                    'rubrique' => ['code' => '35', 'name' => 'INFORMATIQUE ET BUREAUTIQUE'],
                                    'famille' => ['code' => '001', 'name' => '…'],
                                ],
                            ],
                            'meta' => [
                                'current_page' => 1,
                                'last_page' => 124,
                                'per_page' => 2,
                                'total' => 247,
                                'search_driver' => 'sql',
                                'budget_year' => 2026,
                            ],
                        ],
                    ],
                    [
                        'method' => 'GET',
                        'path' => '/mercuriale/articles/{reference}',
                        'summary' => 'Détail d’un article par sa référence exacte.',
                        'params' => [
                            ['name' => 'reference', 'type' => 'string', 'required' => true, 'description' => 'Référence mercuriale (chemin URL).'],
                            ['name' => 'year', 'type' => 'integer', 'required' => false, 'description' => 'Année budget.'],
                        ],
                        'example_request' => "curl -s -H \"X-Api-Key: mrc_…\" \\\n  \"{$baseUrl}/mercuriale/articles/35-001-220085?year=2026\"",
                        'example_response' => [
                            'data' => [
                                'reference' => '35-001-220085',
                                'designation' => 'ORDINATEUR PORTABLE …',
                                'unit' => 'UNITE',
                                'quantity' => '1.000',
                                'unit_price' => 3420000,
                                'currency' => 'XAF',
                                'budget_year' => 2026,
                                'source' => 'Mercuriale MINCOMMERCE 2026',
                                'rubrique' => ['code' => '35', 'name' => 'INFORMATIQUE ET BUREAUTIQUE'],
                                'famille' => ['code' => '001', 'name' => '…'],
                            ],
                            'meta' => ['budget_year' => 2026],
                        ],
                    ],
                ],
                'errors' => [
                    ['code' => 401, 'meaning' => 'Clé absente ou invalide.'],
                    ['code' => 402, 'meaning' => 'Plan Free ou abonnement Pro inactif — API réservée au Pro.'],
                    ['code' => 403, 'meaning' => 'Organisation inactive.'],
                    ['code' => 404, 'meaning' => 'Article introuvable (référence inconnue).'],
                    ['code' => 429, 'meaning' => 'Limite de débit dépassée (60 req/min).'],
                ],
            ],
        ]);
    }

    public function storeKey(Request $request, PlanQuotaService $quotas): RedirectResponse
    {
        $tenant = $request->user()->currentTenant;
        abort_unless($tenant, 403);

        try {
            $quotas->ensureApiAccess($tenant, $request->user());
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
