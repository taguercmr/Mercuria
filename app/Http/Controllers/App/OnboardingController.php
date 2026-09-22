<?php

namespace App\Http\Controllers\App;

use App\Http\Controllers\Controller;
use App\Models\Client;
use App\Models\Document;
use App\Models\Tenant;
use App\Services\Billing\PlanQuotaService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

class OnboardingController extends Controller
{
    public function create(Request $request): Response
    {
        $user = $request->user();

        if ($user->current_tenant_id) {
            return redirect()->route('app.onboarding.guide');
        }

        return Inertia::render('App/Onboarding', [
            'step' => 'organization',
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['nullable', 'email', 'max:255'],
            'phone' => ['nullable', 'string', 'max:40'],
            'currency' => ['required', 'string', 'max:8'],
        ]);

        $user = $request->user();

        $tenant = Tenant::query()->create([
            'name' => $data['name'],
            'slug' => Str::slug($data['name']).'-'.Str::lower(Str::random(4)),
            'email' => $data['email'] ?? $user->email,
            'phone' => $data['phone'] ?? null,
            'currency' => $data['currency'],
            'plan' => 'free',
            'onboarding_step' => 1,
            'onboarding_completed' => false,
        ]);

        $tenant->users()->attach($user->id, ['role' => 'owner']);
        $user->forceFill(['current_tenant_id' => $tenant->id])->save();

        return redirect()->route('app.onboarding.guide');
    }

    public function guide(Request $request): Response
    {
        $tenant = $request->user()->currentTenant;
        abort_unless($tenant, 403);

        $hasLogo = filled($tenant->logo_path) || filled($tenant->letterhead);
        $hasClient = Client::query()->exists();
        $hasDocument = Document::query()->exists();

        $steps = [
            [
                'key' => 'branding',
                'title' => '1. Personnalisez votre en-tete',
                'done' => $hasLogo,
                'href' => route('app.settings.edit'),
                'hint' => 'Logo, devise, pied de page — votre marque sur chaque devis.',
            ],
            [
                'key' => 'client',
                'title' => '2. Ajoutez votre premier client',
                'done' => $hasClient,
                'href' => route('app.clients.create'),
                'hint' => 'Un carnet client pour reunir historique et envois.',
            ],
            [
                'key' => 'document',
                'title' => '3. Creez votre premier devis',
                'done' => $hasDocument,
                'href' => route('app.documents.create', ['type' => 'quote']),
                'hint' => 'Selectionnez des articles dans la mercuriale 2026.',
            ],
        ];

        $completed = collect($steps)->where('done', true)->count();
        if ($completed === 3 && ! $tenant->onboarding_completed) {
            $tenant->update([
                'onboarding_completed' => true,
                'onboarding_step' => 3,
            ]);
        }

        return Inertia::render('App/OnboardingGuide', [
            'steps' => $steps,
            'completed' => $completed,
            'tenant' => $tenant->only(['name', 'plan']),
            'usage' => app(PlanQuotaService::class)->usageSummary($tenant),
        ]);
    }

    public function skip(Request $request): RedirectResponse
    {
        $tenant = $request->user()->currentTenant;
        if ($tenant) {
            $tenant->update(['onboarding_completed' => true, 'onboarding_step' => 3]);
        }

        return redirect()->route('app.dashboard');
    }
}
