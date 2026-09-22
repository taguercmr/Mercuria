<?php

namespace App\Http\Controllers\App;

use App\Http\Controllers\Controller;
use App\Models\Tenant;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class TenantSwitchController extends Controller
{
    public function __invoke(Request $request, Tenant $tenant): RedirectResponse
    {
        $user = $request->user();

        abort_unless(
            $user->tenants()->where('tenants.id', $tenant->id)->exists(),
            403,
            'Vous n\'appartenez pas a cette organisation.'
        );

        $user->forceFill(['current_tenant_id' => $tenant->id])->save();

        return redirect()
            ->route('app.dashboard')
            ->with('success', 'Espace actif : '.$tenant->name);
    }
}
