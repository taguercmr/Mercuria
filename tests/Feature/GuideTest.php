<?php

namespace Tests\Feature;

use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class GuideTest extends TestCase
{
    use RefreshDatabase;

    public function test_authenticated_user_can_view_usage_guide(): void
    {
        $user = User::factory()->create(['email_verified_at' => now()]);
        $tenant = Tenant::query()->create([
            'name' => 'Guide Org',
            'slug' => 'guide-org',
            'plan' => 'free',
            'is_active' => true,
            'currency' => 'XAF',
            'onboarding_completed' => true,
        ]);
        $user->tenants()->attach($tenant->id, ['role' => 'owner']);
        $user->update(['current_tenant_id' => $tenant->id]);

        $this->actingAs($user)
            ->get(route('app.guide'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('App/Guide/Index')
                ->has('mercurialeYear')
                ->has('plans.free')
                ->has('plans.pro'));
    }
}
