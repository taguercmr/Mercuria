<?php

namespace App\Services\Billing;

use App\Models\PaymentTransaction;
use App\Models\Plan;
use App\Models\Tenant;
use App\Services\Payments\PaymentConfig;
use App\Services\Payments\PaymentManager;
use App\Services\Payments\PaymentRequest;
use Illuminate\Support\Str;
use RuntimeException;
use Throwable;

class SubscriptionCheckoutService
{
    public function __construct(
        private readonly PaymentManager $payments,
        private readonly PaymentConfig $paymentConfig,
    ) {}

    /**
     * @return array{transaction:PaymentTransaction, checkout_url:string}
     */
    public function startCheckout(
        Tenant $tenant,
        string $billingCycle = 'monthly',
        ?string $provider = null,
        ?string $customerEmail = null,
        ?string $customerName = null,
        ?string $customerPhone = null,
    ): array {
        $plan = Plan::query()->where('code', 'pro')->firstOrFail();
        $amount = $billingCycle === 'yearly'
            ? (int) $plan->price_yearly_xaf
            : (int) $plan->price_monthly_xaf;

        $provider = $provider ?: $this->paymentConfig->defaultProvider();
        $externalId = 'sub_'.$tenant->id.'_'.Str::lower(Str::random(12));

        $transaction = PaymentTransaction::query()->create([
            'tenant_id' => $tenant->id,
            'provider' => $provider,
            'external_id' => $externalId,
            'amount' => $amount,
            'currency' => (string) config('payments.currency', 'XAF'),
            'status' => 'pending',
            'meta' => [
                'billing_cycle' => $billingCycle,
                'plan' => 'pro',
            ],
        ]);

        try {
            $initiation = $this->payments->driver($provider)->initiate(new PaymentRequest(
                amount: $amount,
                currency: (string) config('payments.currency', 'XAF'),
                externalId: $externalId,
                description: 'Mercuria Pro '.$billingCycle,
                returnUrl: route('app.subscription.return'),
                notifyUrl: route('webhooks.'.$provider),
                customerEmail: $customerEmail,
                customerName: $customerName,
                customerPhone: $customerPhone,
                userId: (string) $tenant->id,
            ));
            if ($initiation->providerTransId === '' || $initiation->checkoutUrl === '') {
                throw new RuntimeException('Invalid checkout response.');
            }
            $transaction->update([
                'provider_trans_id' => $initiation->providerTransId,
                'checkout_url' => $initiation->checkoutUrl,
            ]);
        } catch (Throwable) {
            PaymentTransaction::query()->whereKey($transaction->id)->where('status', 'pending')->update(['status' => 'failed']);

            throw new RuntimeException('Impossible de démarrer le paiement. Vérifiez la configuration du prestataire ou réessayez plus tard.');
        }

        return [
            'transaction' => $transaction,
            'checkout_url' => $initiation->checkoutUrl,
        ];
    }
}
