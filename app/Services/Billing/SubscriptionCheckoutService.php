<?php

namespace App\Services\Billing;

use App\Models\PaymentTransaction;
use App\Models\Plan;
use App\Models\Tenant;
use App\Services\Payments\PaymentManager;
use App\Services\Payments\PaymentRequest;
use Illuminate\Support\Str;

class SubscriptionCheckoutService
{
    public function __construct(private readonly PaymentManager $payments) {}

    /**
     * @return array{transaction:PaymentTransaction, checkout_url:string}
     */
    public function startCheckout(
        Tenant $tenant,
        string $billingCycle = 'monthly',
        ?string $provider = null,
        ?string $customerEmail = null,
        ?string $customerName = null,
    ): array {
        $plan = Plan::query()->where('code', 'pro')->firstOrFail();
        $amount = $billingCycle === 'yearly'
            ? (int) $plan->price_yearly_xaf
            : (int) $plan->price_monthly_xaf;

        $provider = $provider ?: (string) config('payments.default', 'fapshi');
        $externalId = 'sub_'.$tenant->id.'_'.Str::lower(Str::random(12));

        $initiation = $this->payments->driver($provider)->initiate(new PaymentRequest(
            amount: $amount,
            currency: (string) config('payments.currency', 'XAF'),
            externalId: $externalId,
            description: 'Mercuria Pro '.$billingCycle,
            returnUrl: route('app.subscription.return'),
            notifyUrl: route('webhooks.'.$provider),
            customerEmail: $customerEmail,
            customerName: $customerName,
            userId: (string) $tenant->id,
        ));

        $transaction = PaymentTransaction::query()->create([
            'tenant_id' => $tenant->id,
            'provider' => $provider,
            'external_id' => $externalId,
            'provider_trans_id' => $initiation->providerTransId,
            'amount' => $amount,
            'currency' => (string) config('payments.currency', 'XAF'),
            'status' => 'pending',
            'checkout_url' => $initiation->checkoutUrl,
            'meta' => [
                'billing_cycle' => $billingCycle,
                'plan' => 'pro',
                'raw' => $initiation->raw,
            ],
        ]);

        return [
            'transaction' => $transaction,
            'checkout_url' => $initiation->checkoutUrl,
        ];
    }
}
