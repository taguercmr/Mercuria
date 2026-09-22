<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\PlatformSetting;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class MaintenanceController extends Controller
{
    public function update(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'enabled' => ['required', 'boolean'],
            'message' => ['nullable', 'string', 'max:1000'],
        ]);

        PlatformSetting::setValue('maintenance_enabled', $data['enabled']);

        if (array_key_exists('message', $data) && filled($data['message'])) {
            PlatformSetting::setValue('maintenance_message', $data['message']);
        }

        return back()->with('success', 'Mode maintenance mis à jour.');
    }
}
