<?php

namespace App\Http\Controllers\App;

use App\Http\Controllers\Controller;
use App\Models\PaymentTransaction;
use App\Models\Plan;
use App\Services\Billing\PlanQuotaService;
use App\Services\Billing\SubscriptionCheckoutService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use RuntimeException;

class SubscriptionController extends Controller
{
    public function show(Request $request, PlanQuotaService $quotas): Response
    {
        $tenant = $request->user()->currentTenant;
        abort_unless($tenant, 403);

        return Inertia::render('App/Subscription/Index', [
            'usage' => $quotas->usageSummary($tenant),
            'plans' => [
                'free' => config('mercuria.plans.free'),
                'pro' => Plan::query()->where('code', 'pro')->first()
                    ?? config('mercuria.plans.pro'),
            ],
            'provider' => config('payments.default'),
            'transactions' => PaymentTransaction::query()
                ->where('tenant_id', $tenant->id)
                ->latest()
                ->limit(10)
                ->get(['id', 'provider', 'amount', 'currency', 'status', 'created_at', 'paid_at']),
        ]);
    }

    public function checkout(
        Request $request,
        SubscriptionCheckoutService $checkout,
    ): RedirectResponse {
        $tenant = $request->user()->currentTenant;
        abort_unless($tenant, 403);

        $data = $request->validate([
            'billing_cycle' => ['required', 'in:monthly,yearly'],
            'provider' => ['nullable', 'in:fapshi,cinetpay'],
        ]);

        try {
            $result = $checkout->startCheckout(
                tenant: $tenant,
                billingCycle: $data['billing_cycle'],
                provider: $data['provider'] ?? null,
                customerEmail: $request->user()->email,
                customerName: $request->user()->name,
            );
        } catch (RuntimeException $e) {
            return back()->with('error', $e->getMessage());
        }

        if (blank($result['checkout_url'])) {
            return back()->with('error', 'Impossible d\'initier le paiement. Vérifiez les clés Fapshi/CinetPay.');
        }

        return redirect()->away($result['checkout_url']);
    }

    public function return(Request $request): RedirectResponse
    {
        return redirect()
            ->route('app.subscription.show')
            ->with('success', 'Paiement en cours de confirmation. Votre plan sera activé dès validation du webhook.');
    }
}
