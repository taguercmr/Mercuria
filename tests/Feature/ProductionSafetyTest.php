<?php

namespace Tests\Feature;

use App\Models\ApiKey;
use App\Models\PlatformSetting;
use App\Models\Tenant;
use App\Models\User;
use App\Services\Payments\PaymentConfig;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class ProductionSafetyTest extends TestCase
{
    use RefreshDatabase;

    public function test_seeding_never_creates_or_resets_an_administrator(): void
    {
        $this->seed();
        $this->assertDatabaseCount('users', 0);
        $admin = User::factory()->create(['email' => 'admin@mercuria.cm', 'is_platform_admin' => true]);
        $hash = $admin->password;
        PlatformSetting::setValue('maintenance_enabled', true);
        $this->seed();
        $this->assertSame($hash, $admin->fresh()->password);
        $this->assertTrue(PlatformSetting::maintenanceEnabled());
    }

    public function test_admin_creation_requires_a_strong_password_and_never_promotes_an_existing_user(): void
    {
        $this->artisan('mercuria:admin', ['email' => 'new-admin@example.com'])
            ->expectsQuestion('Password (at least 12 characters)', 'Strong-Admin-483!')
            ->expectsQuestion('Confirm password', 'Strong-Admin-483!')
            ->assertExitCode(0);
        $admin = User::query()->where('email', 'new-admin@example.com')->firstOrFail();
        $this->assertTrue($admin->is_platform_admin);
        $this->assertTrue(Hash::check('Strong-Admin-483!', $admin->password));
        $member = User::factory()->create();
        $this->artisan('mercuria:admin', ['email' => $member->email, '--reset-password' => true])->assertExitCode(1);
        $this->assertFalse($member->fresh()->is_platform_admin);
    }

    public function test_admin_command_rejects_a_weak_password(): void
    {
        $this->artisan('mercuria:admin', ['email' => 'weak@example.com'])
            ->expectsQuestion('Password (at least 12 characters)', 'password')
            ->expectsQuestion('Confirm password', 'password')
            ->assertExitCode(1);
        $this->assertDatabaseCount('users', 0);
    }

    public function test_pro_without_expiry_cannot_use_api(): void
    {
        $owner = User::factory()->create();
        $tenant = Tenant::query()->create(['name' => 'No expiry', 'plan' => 'pro', 'is_active' => true]);
        $key = ApiKey::issue($tenant, $owner, 'Existing key');
        $this->withToken($key['plain'])->getJson('/api/v1/mercuriale/rubriques')->assertStatus(402);
    }

    public function test_admin_pro_activation_gets_one_month_and_cannot_clear_expiry(): void
    {
        $this->freezeSecond();
        $admin = User::factory()->create(['is_platform_admin' => true]);
        $tenant = Tenant::query()->create(['name' => 'Manual subscription', 'plan' => 'free']);
        $this->actingAs($admin)->put(route('admin.tenants.update', $tenant), ['plan' => 'pro'])->assertRedirect();
        $expiry = $tenant->fresh()->subscription_ends_at->toDateTimeString();
        $this->assertSame(now()->addMonthNoOverflow()->toDateTimeString(), $expiry);
        $this->put(route('admin.tenants.update', $tenant), ['subscription_ends_at' => null])
            ->assertSessionHasErrors('subscription_ends_at');
        $this->assertSame($expiry, $tenant->fresh()->subscription_ends_at->toDateTimeString());
    }

    public function test_production_check_rejects_local_configuration(): void
    {
        config(['app.debug' => true, 'app.url' => 'http://localhost:8000']);
        $this->artisan('mercuria:production-check', ['--configuration-only' => true])
            ->expectsOutput('[FAIL] APP_ENV=production')
            ->expectsOutput('[FAIL] APP_DEBUG=false')
            ->assertExitCode(1);
    }

    public function test_production_check_requires_credentials_and_rejects_default_admin_password(): void
    {
        $this->app->instance('env', 'production');
        config(['app.debug' => false, 'app.url' => 'https://mercuria.example.com', 'session.secure' => true,
            'session.http_only' => true, 'mail.default' => 'smtp', 'payments.camerpay.api_token' => null,
            'payments.camerpay.callback_secret' => null]);
        $admin = User::factory()->create(['is_platform_admin' => true, 'password' => 'password']);
        $this->artisan('mercuria:production-check')
            ->expectsOutput('[FAIL] No default administrator password')
            ->expectsOutput('[FAIL] Active Pro plan with valid prices')
            ->expectsOutput('[FAIL] CamerPay API token configured')
            ->assertExitCode(1);
        $this->artisan('db:seed', ['--force' => true])->assertExitCode(0);
        $admin->update(['password' => 'Secure-Changed-492!']);
        $config = app(PaymentConfig::class);
        $config->set('camerpay', 'api_token', 'test-token');
        $config->set('camerpay', 'callback_secret', 'test-secret');
        $this->artisan('mercuria:production-check')->assertExitCode(0);
    }

    public function test_health_endpoint_hides_internal_details_in_production(): void
    {
        $this->app->instance('env', 'production');
        $this->getJson(route('health'))->assertOk()->assertExactJson(['status' => 'ok']);
    }

    public function test_unsigned_fapshi_callback_never_activates_access(): void
    {
        config(['payments.fapshi.webhook_secret' => null]);
        $this->post(route('webhooks.fapshi'), ['status' => 'SUCCESSFUL', 'transId' => 'forged', 'amount' => 15000])
            ->assertStatus(400);
        $this->assertDatabaseCount('subscriptions', 0);
    }

    public function test_payment_secrets_are_encrypted_and_not_returned_to_the_admin_page(): void
    {
        $admin = User::factory()->create(['is_platform_admin' => true]);
        $config = app(PaymentConfig::class);
        $config->set('camerpay', 'api_token', 'private-test-token');
        $this->assertNotSame('private-test-token', PlatformSetting::getValue('payments.camerpay.api_token'));
        $this->actingAs($admin)->get(route('admin.payments.edit'))->assertOk()->assertDontSee('private-test-token');
        PlatformSetting::setValue('payments.camerpay.api_token', 'unencrypted-or-corrupt');
        $this->assertFalse($config->has('camerpay', 'api_token'));
    }

    public function test_payment_settings_reject_unexpected_fields_and_non_official_endpoint(): void
    {
        $admin = User::factory()->create(['is_platform_admin' => true]);
        $this->actingAs($admin)->put(route('admin.payments.update'), [
            'default_provider' => 'camerpay', 'camerpay' => ['base_url' => 'http://127.0.0.1/private'],
        ])->assertSessionHasErrors('camerpay.base_url');
        $this->put(route('admin.payments.update'), [
            'default_provider' => 'camerpay', 'camerpay' => ['unexpected' => 'value'],
        ])->assertSessionHasErrors('camerpay');
    }
}
