<?php

namespace Tests\Feature;

use App\Models\PaymentTransaction;
use App\Models\Plan;
use App\Models\Tenant;
use App\Models\User;
use App\Services\Payments\PaymentConfig;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Inertia\Testing\AssertableInertia as Assert;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class SubscriptionPricingTest extends TestCase
{
    use RefreshDatabase;

    #[DataProvider('billingCycles')]
    public function test_displayed_price_matches_the_camerpay_charge(string $cycle, int $amount): void
    {
        $this->seed();
        $user = $this->actingOwner();
        $payments = app(PaymentConfig::class);
        $payments->set('camerpay', 'api_token', 'test-token');
        $payments->set('camerpay', 'callback_secret', 'test-secret');
        Http::preventStrayRequests();
        Http::fake([
            'camerpay.biz/api/payment/initiate' => Http::response([
                'success' => true,
                'transaction_uuid' => 'tx-new-price',
                'pay_url' => 'https://camerpay.biz/pay/tx-new-price',
            ]),
        ]);

        $this->get(route('landing'))->assertInertia(fn (Assert $page) => $page
            ->where("plans.pro.price_{$cycle}_xaf", $amount));
        $this->actingAs($user)->get(route('app.subscription.show'))->assertInertia(fn (Assert $page) => $page
            ->where("plans.pro.price_{$cycle}_xaf", $amount));
        $this->withHeader('X-Inertia', 'true')->post(route('app.subscription.checkout'), [
            'billing_cycle' => $cycle,
            'provider' => 'camerpay',
            'amount' => 1,
        ])->assertStatus(409)->assertHeader('X-Inertia-Location', 'https://camerpay.biz/pay/tx-new-price');

        Http::assertSent(fn ($request) => $request['amount'] === $amount);
        $this->assertDatabaseHas('payment_transactions', [
            'tenant_id' => $user->current_tenant_id,
            'provider_trans_id' => 'tx-new-price',
            'amount' => $amount,
            'currency' => 'XAF',
            'status' => 'pending',
        ]);
    }

    public static function billingCycles(): array
    {
        return ['monthly' => ['monthly', 9900], 'yearly' => ['yearly', 99000]];
    }

    public function test_landing_uses_the_same_stored_price_as_checkout(): void
    {
        $this->seed();
        Plan::query()->where('code', 'pro')->update(['price_monthly_xaf' => 12000]);

        $this->get(route('landing'))->assertInertia(fn (Assert $page) => $page
            ->where('plans.pro.price_monthly_xaf', 12000));
    }

    public function test_upgrade_changes_catalogue_prices_without_rewriting_payment_history(): void
    {
        $this->seed();
        $user = $this->actingOwner();
        Plan::query()->where('code', 'pro')->update(['price_monthly_xaf' => 15000, 'price_yearly_xaf' => 150000]);
        $transaction = PaymentTransaction::query()->create([
            'tenant_id' => $user->current_tenant_id,
            'provider' => 'camerpay',
            'external_id' => 'previous-price',
            'amount' => 15000,
            'currency' => 'XAF',
            'status' => 'paid',
        ]);
        $migration = require database_path('migrations/2026_10_11_212328_update_pro_subscription_prices.php');

        $migration->up();

        $this->assertDatabaseHas('plans', ['code' => 'pro', 'price_monthly_xaf' => 9900, 'price_yearly_xaf' => 99000]);
        $this->assertDatabaseHas('plans', ['code' => 'free', 'price_monthly_xaf' => 0]);
        $this->assertDatabaseHas('payment_transactions', ['id' => $transaction->id, 'amount' => 15000, 'status' => 'paid']);
    }

    private function actingOwner(): User
    {
        $user = User::factory()->create();
        $tenant = Tenant::query()->create([
            'name' => 'Entreprise test', 'plan' => 'free', 'currency' => 'XAF',
            'onboarding_completed' => true, 'is_active' => true,
        ]);
        $tenant->users()->attach($user, ['role' => 'owner']);
        $user->forceFill(['current_tenant_id' => $tenant->id])->save();

        return $user;
    }
}
