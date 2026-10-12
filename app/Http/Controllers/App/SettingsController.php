<?php

namespace App\Http\Controllers\App;

use App\Http\Controllers\Controller;
use App\Services\Billing\PlanQuotaService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response;

class SettingsController extends Controller
{
    public function edit(Request $request, PlanQuotaService $quotas): Response
    {
        $tenant = $request->user()->currentTenant;
        $canWrite = $request->user()->is_platform_admin
            || in_array($request->user()->tenantRole(), ['owner', 'editor'], true);

        return Inertia::render('App/Settings/Index', [
            'tenant' => $tenant ? [
                ...$tenant->only([
                    'id', 'name', 'email', 'phone', 'currency', 'logo_path',
                    'letterhead', 'footer_text', 'signature_path', 'plan',
                ]),
                'logo_url' => $tenant->logo_path
                    ? Storage::disk('public')->url($tenant->logo_path)
                    : null,
                'signature_url' => $tenant->signature_path
                    ? Storage::disk('public')->url($tenant->signature_path)
                    : null,
            ] : null,
            'canWrite' => $canWrite,
            'hasAdvancedCustomization' => $tenant
                ? $quotas->hasAdvancedCustomization($tenant, $request->user())
                : false,
        ]);
    }

    public function update(Request $request, PlanQuotaService $quotas): RedirectResponse
    {
        $tenant = $request->user()->currentTenant;
        abort_unless($tenant, 403);

        $rules = [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['nullable', 'email', 'max:255'],
            'phone' => ['nullable', 'string', 'max:40'],
            'currency' => ['required', 'string', 'max:8'],
        ];

        $hasAdvancedCustomization = $quotas->hasAdvancedCustomization($tenant, $request->user());
        if ($hasAdvancedCustomization) {
            $rules += [
                'letterhead' => ['nullable', 'string', 'max:5000'],
                'footer_text' => ['nullable', 'string', 'max:5000'],
                'logo' => ['nullable', 'image', 'max:2048'],
                'signature' => ['nullable', 'image', 'max:2048'],
            ];
        }

        $data = $request->validate($rules);

        if ($request->hasFile('logo')) {
            if ($tenant->logo_path) {
                Storage::disk('public')->delete($tenant->logo_path);
            }
            $data['logo_path'] = $request->file('logo')->store('tenants/'.$tenant->id, 'public');
        }

        if ($request->hasFile('signature')) {
            if ($tenant->signature_path) {
                Storage::disk('public')->delete($tenant->signature_path);
            }
            $data['signature_path'] = $request->file('signature')->store('tenants/'.$tenant->id, 'public');
        }

        unset($data['logo'], $data['signature']);
        $tenant->update($data);

        return back()->with('success', 'Personnalisation enregistrée.');
    }
}
