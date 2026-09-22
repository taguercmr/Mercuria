<?php

namespace App\Http\Controllers\Webhooks;

use App\Http\Controllers\Controller;
use App\Models\PaymentTransaction;
use App\Models\Plan;
use App\Models\Subscription;
use App\Notifications\SubscriptionActivatedNotification;
use App\Services\Payments\PaymentManager;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;

class PaymentWebhookController extends Controller
{
    public function __construct(private readonly PaymentManager $payments) {}

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
                    ->where(function ($q) use ($status): void {
                        $q->where('provider_trans_id', $status->providerTransId);

                        if ($status->externalId) {
                            $q->orWhere('external_id', $status->externalId);
                        }
                    })
                    ->lockForUpdate()
                    ->first();

                if (! $tx || $tx->status === 'paid') {
                    return;
                }

                $tx->update([
                    'status' => 'paid',
                    'provider_trans_id' => $status->providerTransId ?: $tx->provider_trans_id,
                    'paid_at' => now(),
                    'meta' => array_merge($tx->meta ?? [], ['webhook' => $status->raw]),
                ]);

                $tenant = $tx->tenant;
                $plan = Plan::query()->where('code', 'pro')->first();

                if ($plan && $tenant) {
                    $cycle = data_get($tx->meta, 'billing_cycle', 'monthly');
                    $endsAt = $cycle === 'yearly'
                        ? Carbon::now()->addYear()
                        : Carbon::now()->addMonth();

                    $subscription = Subscription::query()->create([
                        'tenant_id' => $tenant->id,
                        'plan_id' => $plan->id,
                        'status' => 'active',
                        'billing_cycle' => $cycle,
                        'provider' => $provider,
                        'starts_at' => now(),
                        'ends_at' => $endsAt,
                    ]);

                    $tx->update(['subscription_id' => $subscription->id]);

                    $tenant->update([
                        'plan' => 'pro',
                        'subscribed_at' => now(),
                        'subscription_ends_at' => $endsAt,
                    ]);

                    $activated = ['tenant' => $tenant->fresh(), 'ends_at' => $endsAt];
                }
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
