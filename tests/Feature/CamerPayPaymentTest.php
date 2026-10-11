<?php

namespace Tests\Feature;

use App\Models\PaymentTransaction;
use App\Models\Plan;
use App\Models\Tenant;
use App\Models\User;
use App\Services\Payments\Adapters\CamerPayAdapter;
use App\Services\Payments\PaymentConfig;
use App\Services\Payments\PaymentRequest;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Http;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class CamerPayPaymentTest extends TestCase
{
    use RefreshDatabase;

    public function test_camerpay_webhook_signature_matches_official_vector(): void
    {
        $config = app(PaymentConfig::class);
        $config->set('camerpay', 'callback_secret', 'test_secret_key_123');

        $uuid = '5add2319-f71b-4f2d-a4f4-97fe0d11c1d4';
        $invoiceId = 'FACT-001';
        $status = 'completed';
        $amount = '10000.00';
        $signature = 'feab3068de64a00e07ecddc6990570a621eb3d725f9efb728b6a9ca2e455bc37';

        $request = Request::create('/webhooks/camerpay', 'POST', [
            'uuid' => $uuid,
            'invoice_id' => $invoiceId,
            'status' => $status,
            'amount' => $amount,
            'signature' => $signature,
        ]);

        $result = app(CamerPayAdapter::class)->verifyWebhook($request);

        $this->assertTrue($result->isSuccessful());
        $this->assertSame($uuid, $result->providerTransId);
        $this->assertSame($invoiceId, $result->externalId);
        $this->assertSame(10000, $result->amount);
    }

    public function test_camerpay_webhook_activates_pro_subscription(): void
    {
        $this->seedPlans();

        $config = app(PaymentConfig::class);
        $config->set('camerpay', 'callback_secret', 'test_secret_key_123');

        [$user, $tenant] = $this->actingTenantOwner();

        $externalId = 'sub_'.$tenant->id.'_abc123';
        $uuid = '5add2319-f71b-4f2d-a4f4-97fe0d11c1d4';

        PaymentTransaction::query()->create([
            'tenant_id' => $tenant->id,
            'provider' => 'camerpay',
            'external_id' => $externalId,
            'provider_trans_id' => $uuid,
            'amount' => 15000,
            'currency' => 'XAF',
            'status' => 'pending',
            'meta' => ['billing_cycle' => 'monthly', 'plan' => 'pro'],
        ]);

        $amount = '15000.00';
        $signature = hash_hmac(
            'sha256',
            "{$uuid}|{$externalId}|completed|{$amount}",
            'test_secret_key_123',
        );

        $this->post(route('webhooks.camerpay'), [
            'uuid' => $uuid,
            'invoice_id' => $externalId,
            'status' => 'completed',
            'amount' => $amount,
            'signature' => $signature,
        ])->assertOk();

        $this->assertDatabaseHas('payment_transactions', [
            'provider_trans_id' => $uuid,
            'status' => 'paid',
        ]);

        $this->assertSame('pro', $tenant->fresh()->plan);
        $this->assertNotNull($tenant->fresh()->subscription_ends_at);
        $this->assertTrue($tenant->fresh()->isPaid());
    }

    public function test_camerpay_initiate_posts_expected_payload(): void
    {
        $config = app(PaymentConfig::class);
        $config->set('camerpay', 'api_token', '2|test-token');
        $config->set('camerpay', 'callback_secret', 'test-secret');
        $config->set('camerpay', 'base_url', 'https://camerpay.biz/api');

        Http::preventStrayRequests();
        Http::fake([
            'camerpay.biz/api/payment/initiate' => Http::response([
                'success' => true,
                'transaction_uuid' => 'tx-uuid-1',
                'status' => 'pending',
                'pay_url' => 'https://camerpay.biz/pay/tx-uuid-1',
            ], 200),
        ]);

        $result = app(CamerPayAdapter::class)->initiate(new PaymentRequest(
            amount: 15000,
            currency: 'XAF',
            externalId: 'sub_1_test',
            description: 'Mercuria Pro monthly',
            returnUrl: 'https://example.test/return',
            notifyUrl: 'https://example.test/webhooks/camerpay',
            customerEmail: 'owner@example.com',
            customerName: 'Owner',
            customerPhone: '+237 690 00 00 00',
        ));

        $this->assertSame('tx-uuid-1', $result->providerTransId);
        $this->assertSame('https://camerpay.biz/pay/tx-uuid-1', $result->checkoutUrl);

        Http::assertSent(function ($request) {
            return $request->url() === 'https://camerpay.biz/api/payment/initiate'
                && $request->hasHeader('Authorization', 'Bearer 2|test-token')
                && $request['merchant_invoice_id'] === 'sub_1_test'
                && $request['amount'] === 15000;
        });
    }

    #[DataProvider('invalidCallbacks')]
    public function test_invalid_callbacks_do_not_activate_a_subscription(array $changes): void
    {
        $transaction = $this->pendingTransaction();
        $payload = $this->callbackPayload($transaction, $changes);
        $this->post(route('webhooks.camerpay'), $payload)->assertStatus(400);
        $this->assertDatabaseHas('payment_transactions', ['id' => $transaction->id, 'status' => 'pending']);
        $this->assertDatabaseCount('subscriptions', 0);
        $this->assertSame('free', $transaction->tenant->fresh()->plan);
    }

    public static function invalidCallbacks(): array
    {
        return [
            'underpayment' => [['amount' => '14999.00']],
            'overpayment' => [['amount' => '15001.00']],
            'fractional amount' => [['amount' => '15000.49']],
            'unknown invoice' => [['invoice_id' => 'another-invoice']],
            'different transaction' => [['uuid' => 'another-transaction']],
            'invalid signature' => [['signature' => str_repeat('0', 64)]],
        ];
    }

    public function test_duplicate_callback_does_not_extend_subscription_twice(): void
    {
        $this->freezeSecond();
        $transaction = $this->pendingTransaction();
        $payload = $this->callbackPayload($transaction);
        $this->post(route('webhooks.camerpay'), $payload)->assertOk();
        $endsAt = $transaction->tenant->fresh()->subscription_ends_at->toDateTimeString();
        $this->travel(2)->days();
        $this->post(route('webhooks.camerpay'), $payload)->assertOk();
        $this->assertDatabaseCount('subscriptions', 1);
        $this->assertSame($endsAt, $transaction->tenant->fresh()->subscription_ends_at->toDateTimeString());
    }

    #[DataProvider('renewalDates')]
    public function test_renewal_preserves_remaining_time_and_handles_month_ends(string $now, ?string $expiry, string $cycle, string $expected): void
    {
        $this->travelTo(Carbon::parse($now));
        $transaction = $this->pendingTransaction();
        $transaction->update(['meta' => ['billing_cycle' => $cycle, 'plan' => 'pro']]);
        if ($expiry) {
            $transaction->tenant->update(['plan' => 'pro', 'subscription_ends_at' => $expiry]);
        }
        $this->post(route('webhooks.camerpay'), $this->callbackPayload($transaction))->assertOk();
        $this->assertSame($expected, $transaction->tenant->fresh()->subscription_ends_at->toDateTimeString());
    }

    public static function renewalDates(): array
    {
        return [
            'early monthly renewal' => ['2026-10-10 12:00:00', '2026-10-30 12:00:00', 'monthly', '2026-11-30 12:00:00'],
            'expired subscription' => ['2026-10-10 12:00:00', '2026-09-30 12:00:00', 'monthly', '2026-11-10 12:00:00'],
            'January end' => ['2027-01-31 12:00:00', null, 'monthly', '2027-02-28 12:00:00'],
            'leap year' => ['2024-02-29 12:00:00', null, 'yearly', '2025-02-28 12:00:00'],
        ];
    }

    #[DataProvider('productionPaymentModes')]
    public function test_production_requires_a_confirmed_live_payment(?bool $sandbox, int $expectedStatus): void
    {
        $transaction = $this->pendingTransaction();
        app(PaymentConfig::class)->set('camerpay', 'api_token', 'test-api-token');
        $this->app->instance('env', 'production');
        Http::preventStrayRequests();
        Http::fake(['camerpay.biz/api/payment/tx-verified/status' => Http::response([
            'transaction' => [
                'uuid' => 'tx-verified', 'status' => 'completed', 'merchant_invoice_id' => $transaction->external_id,
                'amount' => '15000.00', 'currency' => 'XAF', 'is_sandbox' => $sandbox,
            ],
        ])]);

        $this->post(route('webhooks.camerpay'), $this->callbackPayload($transaction))->assertStatus($expectedStatus);
        $this->assertDatabaseCount('subscriptions', $expectedStatus === 200 ? 1 : 0);
        Http::assertSentCount(1);
    }

    public static function productionPaymentModes(): array
    {
        return ['live' => [false, 200], 'sandbox' => [true, 400], 'unknown mode' => [null, 400]];
    }

    public function test_checkout_creates_transaction_before_contacting_provider_and_redirects_inertia(): void
    {
        $this->seedPlans();
        [$user] = $this->actingTenantOwner();
        $config = app(PaymentConfig::class);
        $config->set('camerpay', 'api_token', 'test-token');
        $config->set('camerpay', 'callback_secret', 'test-secret');
        Http::preventStrayRequests();
        Http::fake(['camerpay.biz/api/payment/initiate' => function ($request) {
            $this->assertDatabaseHas('payment_transactions', ['external_id' => $request['merchant_invoice_id'], 'status' => 'pending']);

            return Http::response(['success' => true, 'transaction_uuid' => 'checkout-id', 'pay_url' => 'https://camerpay.biz/pay/checkout-id']);
        }]);
        $this->actingAs($user)->withHeader('X-Inertia', 'true')
            ->post(route('app.subscription.checkout'), ['billing_cycle' => 'monthly', 'provider' => 'camerpay'])
            ->assertStatus(409)->assertHeader('X-Inertia-Location', 'https://camerpay.biz/pay/checkout-id');
        Http::assertSentCount(1);
    }

    public function test_failed_or_refunded_callback_does_not_grant_access(): void
    {
        $transaction = $this->pendingTransaction();
        foreach (['failed', 'refunded'] as $status) {
            $this->post(route('webhooks.camerpay'), $this->callbackPayload($transaction, ['status' => $status]))->assertOk();
        }
        $this->assertDatabaseCount('subscriptions', 0);
    }

    #[DataProvider('invalidCheckoutResponses')]
    public function test_invalid_checkout_response_fails_without_redirecting_to_provider(array $response): void
    {
        $this->seedPlans();
        [$user] = $this->actingTenantOwner();
        $config = app(PaymentConfig::class);
        $config->set('camerpay', 'api_token', 'test-token');
        $config->set('camerpay', 'callback_secret', 'test-secret');
        Http::preventStrayRequests();
        Http::fake(['camerpay.biz/api/payment/initiate' => Http::response($response)]);
        $this->actingAs($user)->post(route('app.subscription.checkout'), ['billing_cycle' => 'monthly', 'provider' => 'camerpay'])
            ->assertRedirect()->assertSessionHas('error');
        $this->assertDatabaseHas('payment_transactions', ['tenant_id' => $user->current_tenant_id, 'status' => 'failed']);
        $this->assertDatabaseCount('subscriptions', 0);
        Http::assertSentCount(1);
    }

    public static function invalidCheckoutResponses(): array
    {
        return [
            'untrusted destination' => [['success' => true, 'transaction_uuid' => 'id', 'pay_url' => 'https://untrusted.example/pay']],
            'missing transaction id' => [['success' => true, 'pay_url' => 'https://camerpay.biz/pay/id']],
            'provider failure' => [['success' => false, 'message' => 'sensitive-provider-detail']],
        ];
    }

    public function test_checkout_without_callback_secret_does_not_contact_provider(): void
    {
        $this->seedPlans();
        [$user] = $this->actingTenantOwner();
        config(['payments.camerpay.callback_secret' => null]);
        app(PaymentConfig::class)->set('camerpay', 'api_token', 'test-token');
        Http::preventStrayRequests();
        $this->actingAs($user)->post(route('app.subscription.checkout'), ['billing_cycle' => 'monthly', 'provider' => 'camerpay'])
            ->assertRedirect()->assertSessionHas('error');
        Http::assertNothingSent();
    }

    public function test_unavailable_live_confirmation_does_not_activate_access(): void
    {
        $transaction = $this->pendingTransaction();
        app(PaymentConfig::class)->set('camerpay', 'api_token', 'test-token');
        $this->app->instance('env', 'production');
        Http::preventStrayRequests();
        Http::fake(['camerpay.biz/api/payment/tx-verified/status' => Http::response([], 503)]);
        $this->post(route('webhooks.camerpay'), $this->callbackPayload($transaction))->assertStatus(400);
        $this->assertDatabaseHas('payment_transactions', ['id' => $transaction->id, 'status' => 'pending']);
        $this->assertDatabaseCount('subscriptions', 0);
        Http::assertSentCount(1);
    }

    public function test_production_accepts_the_documented_flat_status_response(): void
    {
        $transaction = $this->pendingTransaction();
        app(PaymentConfig::class)->set('camerpay', 'api_token', 'test-token');
        $this->app->instance('env', 'production');
        Http::preventStrayRequests();
        Http::fake(['camerpay.biz/api/payment/tx-verified/status' => Http::response([
            'success' => true, 'transaction_uuid' => 'tx-verified', 'status' => 'completed',
            'amount' => '15000.00', 'currency' => 'XAF', 'pay_url' => 'https://camerpay.biz/pay/tx-verified',
        ])]);
        $this->post(route('webhooks.camerpay'), $this->callbackPayload($transaction))->assertOk();
        $this->assertDatabaseCount('subscriptions', 1);
        Http::assertSentCount(1);
    }

    public function test_missing_plan_does_not_mark_the_transaction_paid(): void
    {
        $transaction = $this->pendingTransaction();
        Plan::query()->delete();
        $this->post(route('webhooks.camerpay'), $this->callbackPayload($transaction))->assertStatus(400);
        $this->assertDatabaseHas('payment_transactions', ['id' => $transaction->id, 'status' => 'pending']);
    }

    private function pendingTransaction(): PaymentTransaction
    {
        $this->seedPlans();
        [, $tenant] = $this->actingTenantOwner();
        app(PaymentConfig::class)->set('camerpay', 'callback_secret', 'test-secret');

        return PaymentTransaction::query()->create([
            'tenant_id' => $tenant->id, 'provider' => 'camerpay', 'external_id' => 'invoice-test',
            'provider_trans_id' => 'tx-verified', 'amount' => 15000, 'currency' => 'XAF', 'status' => 'pending',
            'meta' => ['billing_cycle' => 'monthly', 'plan' => 'pro'],
        ]);
    }

    private function callbackPayload(PaymentTransaction $transaction, array $changes = []): array
    {
        $payload = array_replace([
            'uuid' => $transaction->provider_trans_id, 'invoice_id' => $transaction->external_id,
            'status' => 'completed', 'amount' => '15000.00',
        ], $changes);
        $payload['signature'] ??= hash_hmac('sha256', implode('|', [
            $payload['uuid'], $payload['invoice_id'], $payload['status'], $payload['amount'],
        ]), 'test-secret');

        return $payload;
    }

    private function seedPlans(): void
    {
        Plan::query()->create([
            'code' => 'pro',
            'name' => 'Pro',
            'price_monthly_xaf' => 15000,
            'price_yearly_xaf' => 150000,
            'documents_per_month' => null,
            'clients_limit' => null,
            'team_members' => 5,
            'api_access' => true,
            'is_active' => true,
            'features' => ['documents', 'api'],
        ]);
    }

    /**
     * @return array{0: User, 1: Tenant}
     */
    private function actingTenantOwner(): array
    {
        $user = User::factory()->create(['email_verified_at' => now()]);
        $tenant = Tenant::query()->create([
            'name' => 'Cabinet Pay',
            'email' => $user->email,
            'plan' => 'free',
            'currency' => 'XAF',
            'onboarding_completed' => true,
            'is_active' => true,
        ]);
        $tenant->users()->attach($user->id, ['role' => 'owner']);
        $user->forceFill(['current_tenant_id' => $tenant->id])->save();

        return [$user, $tenant->fresh()];
    }
}
