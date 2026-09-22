<?php

namespace App\Http\Controllers\App;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response;

class SettingsController extends Controller
{
    public function edit(Request $request): Response
    {
        $tenant = $request->user()->currentTenant;

        return Inertia::render('App/Settings/Index', [
            'tenant' => $tenant?->only([
                'id', 'name', 'email', 'phone', 'currency', 'logo_path',
                'letterhead', 'footer_text', 'signature_path', 'plan',
            ]),
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $tenant = $request->user()->currentTenant;
        abort_unless($tenant, 403);

        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['nullable', 'email', 'max:255'],
            'phone' => ['nullable', 'string', 'max:40'],
            'currency' => ['required', 'string', 'max:8'],
            'letterhead' => ['nullable', 'string', 'max:5000'],
            'footer_text' => ['nullable', 'string', 'max:5000'],
            'logo' => ['nullable', 'image', 'max:2048'],
            'signature' => ['nullable', 'image', 'max:2048'],
        ]);

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

        return back()->with('success', 'Paramètres enregistrés.');
    }
}
