<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\Payments\PaymentConfig;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class PaymentSettingsController extends Controller
{
    public function edit(PaymentConfig $payments): Response
    {
        return Inertia::render('Admin/Payments', $payments->adminForm());
    }

    public function update(Request $request, PaymentConfig $payments): RedirectResponse
    {
        $data = $request->validate([
            'default_provider' => ['required', 'in:camerpay,fapshi,cinetpay'],
            'camerpay' => ['nullable', 'array:api_token,callback_secret,base_url'],
            'camerpay.api_token' => ['nullable', 'string', 'max:500'],
            'camerpay.callback_secret' => ['nullable', 'string', 'max:500'],
            'camerpay.base_url' => ['nullable', 'in:https://camerpay.biz/api'],
            'fapshi' => ['nullable', 'array:apiuser,apikey,webhook_secret,base_url'],
            'fapshi.apiuser' => ['nullable', 'string', 'max:255'],
            'fapshi.apikey' => ['nullable', 'string', 'max:500'],
            'fapshi.webhook_secret' => ['nullable', 'string', 'max:500'],
            'fapshi.base_url' => ['nullable', 'url:https', 'max:255'],
            'cinetpay' => ['nullable', 'array:apikey,site_id,notify_secret,base_url'],
            'cinetpay.apikey' => ['nullable', 'string', 'max:500'],
            'cinetpay.site_id' => ['nullable', 'string', 'max:255'],
            'cinetpay.notify_secret' => ['nullable', 'string', 'max:500'],
            'cinetpay.base_url' => ['nullable', 'url:https', 'max:255'],
        ]);

        $payments->setDefaultProvider($data['default_provider']);

        foreach (['camerpay', 'fapshi', 'cinetpay'] as $provider) {
            foreach (($data[$provider] ?? []) as $key => $value) {
                if (! is_string($key)) {
                    continue;
                }

                $payments->set($provider, $key, is_string($value) ? trim($value) : null);
            }
        }

        return back()->with('success', 'Configuration paiements enregistrée.');
    }
}
