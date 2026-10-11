<?php

namespace Tests\Feature;

use App\Models\User;
use App\Services\Payments\PaymentConfig;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminPaymentSettingsTest extends TestCase
{
    use RefreshDatabase;

    public function test_platform_admin_can_view_and_save_camerpay_credentials(): void
    {
        config(['payments.default' => 'camerpay']);

        $admin = User::factory()->create([
            'is_platform_admin' => true,
            'email_verified_at' => now(),
        ]);

        $this->actingAs($admin)
            ->get(route('admin.payments.edit'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('Admin/Payments')
                ->where('default_provider', 'camerpay')
                ->has('providers.camerpay')
                ->has('webhook_urls.camerpay'));

        $this->actingAs($admin)
            ->put(route('admin.payments.update'), [
                'default_provider' => 'camerpay',
                'camerpay' => [
                    'api_token' => '2|live-token-abc',
                    'callback_secret' => 'cb-secret-xyz',
                    'base_url' => 'https://camerpay.biz/api',
                ],
            ])
            ->assertRedirect();

        $config = app(PaymentConfig::class);

        $this->assertSame('camerpay', $config->defaultProvider());
        $this->assertSame('2|live-token-abc', $config->get('camerpay', 'api_token'));
        $this->assertSame('cb-secret-xyz', $config->get('camerpay', 'callback_secret'));
        $this->assertTrue($config->has('camerpay', 'api_token'));
    }

    public function test_regular_user_cannot_open_payment_settings(): void
    {
        $user = User::factory()->create(['email_verified_at' => now()]);

        $this->actingAs($user)
            ->get(route('admin.payments.edit'))
            ->assertForbidden();
    }
}
