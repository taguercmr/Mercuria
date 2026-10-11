<?php

namespace App\Http\Controllers\Webhooks;

use App\Http\Controllers\Controller;
use App\Models\PaymentTransaction;
use App\Models\Plan;
use App\Models\Subscription;
use App\Models\Tenant;
use App\Notifications\SubscriptionActivatedNotification;
use App\Services\Payments\PaymentManager;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;
use RuntimeException;

class PaymentWebhookController extends Controller
{
    public function __construct(private readonly PaymentManager $payments) {}

    public function camerpay(Request $request): Response
    {
        return $this->handle($request, 'camerpay');
    }

    public function fapshi(Request $request): Response
    {
        return $this->handle($request, 'fapshi');
    }

    public function cinetpay(Request $request): Response
    {
        return $this->handle($request, 'cinetpay');
    }

    private function handle(Request $request, string $provider): Response
    {
        try {
            $status = $this->payments->driver($provider)->verifyWebhook($request);

            if (! $status->isSuccessful()) {
                return response('ignored', 200);
            }

            $activated = null;

            DB::transaction(function () use ($status, $provider, &$activated): void {
                $tx = PaymentTransaction::query()
                    ->where('provider', $provider)
                    ->when($status->externalId,
                        fn ($query) => $query->where('external_id', $status->externalId),
                        fn ($query) => $query->where('provider_trans_id', $status->providerTransId))
                    ->lockForUpdate()
                    ->first();

                if (! $tx || $status->providerTransId === ''
                    || ($tx->provider_trans_id && $tx->provider_trans_id !== $status->providerTransId)
                    || ($status->externalId && $tx->external_id !== $status->externalId)
                    || $status->amount === null || $status->amount <= 0
                    || (int) $tx->amount !== $status->amount
                    || ($provider === 'camerpay' && $tx->currency !== 'XAF')
                    || ($status->currency !== null && $status->currency !== $tx->currency)) {
                    throw new RuntimeException('Payment does not match the expected transaction.');
                }

                if ($tx->status === 'paid') {
                    return;
                }

                $tenant = Tenant::query()->lockForUpdate()->find($tx->tenant_id);
                $plan = Plan::query()->where('code', 'pro')->first();

                if (! $plan || ! $tenant) {
                    throw new RuntimeException('Subscription configuration unavailable.');
                }

                $cycle = data_get($tx->meta, 'billing_cycle', 'monthly');
                if (! in_array($cycle, ['monthly', 'yearly'], true)) {
                    throw new RuntimeException('Invalid billing cycle.');
                }
                $startsAt = $tenant->isPaid() ? $tenant->subscription_ends_at->copy() : now();
                $endsAt = $cycle === 'yearly'
                    ? $startsAt->copy()->addYearNoOverflow()
                    : $startsAt->copy()->addMonthNoOverflow();

                $subscription = Subscription::query()->create([
                    'tenant_id' => $tenant->id,
                    'plan_id' => $plan->id,
                    'status' => 'active',
                    'billing_cycle' => $cycle,
                    'provider' => $provider,
                    'starts_at' => $startsAt,
                    'ends_at' => $endsAt,
                ]);

                $tx->update([
                    'subscription_id' => $subscription->id,
                    'status' => 'paid',
                    'provider_trans_id' => $status->providerTransId,
                    'paid_at' => now(),
                ]);

                $tenant->update([
                    'plan' => 'pro',
                    'subscribed_at' => $tenant->subscribed_at ?? now(),
                    'subscription_ends_at' => $endsAt,
                ]);

                $activated = ['tenant' => $tenant->fresh(), 'ends_at' => $endsAt];
            });

            if ($activated) {
                $owners = $activated['tenant']->users()
                    ->wherePivot('role', 'owner')
                    ->get();

                if ($owners->isNotEmpty()) {
                    Notification::send(
                        $owners,
                        new SubscriptionActivatedNotification(
                            $activated['tenant'],
                            $activated['ends_at']->format('d/m/Y'),
                        ),
                    );
                }
            }
        } catch (\Throwable $e) {
            Log::warning('Payment webhook failed', [
                'provider' => $provider,
                'error' => $e->getMessage(),
            ]);

            return response('error', 400);
        }

        return response('ok', 200);
    }
}
