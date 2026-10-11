<?php

namespace Tests\Feature;

use App\Models\Client;
use App\Models\Document;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AppDashboardTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @return array{0: User, 1: Tenant}
     */
    private function actingTenantOwner(): array
    {
        $user = User::factory()->create(['email_verified_at' => now()]);
        $tenant = Tenant::query()->create([
            'name' => 'Cabinet Charts',
            'email' => $user->email,
            'plan' => 'pro',
            'currency' => 'XAF',
            'onboarding_completed' => true,
            'subscribed_at' => now(),
            'subscription_ends_at' => now()->addMonth(),
            'is_active' => true,
        ]);
        $tenant->users()->attach($user->id, ['role' => 'owner']);
        $user->forceFill(['current_tenant_id' => $tenant->id])->save();

        return [$user, $tenant->fresh()];
    }

    public function test_tenant_dashboard_exposes_chart_series_and_kpis(): void
    {
        [$user, $tenant] = $this->actingTenantOwner();

        $client = Client::query()->create([
            'tenant_id' => $tenant->id,
            'name' => 'Elamigos',
        ]);

        Document::query()->create([
            'tenant_id' => $tenant->id,
            'client_id' => $client->id,
            'created_by' => $user->id,
            'type' => Document::TYPE_QUOTE,
            'status' => 'issued',
            'currency' => 'XAF',
            'title' => 'Devis test',
            'tax_rate' => 0,
            'issue_date' => now()->toDateString(),
            'subtotal' => 100000,
            'tax_amount' => 0,
            'total' => 100000,
            'issued_at' => now(),
        ]);

        Document::query()->create([
            'tenant_id' => $tenant->id,
            'client_id' => $client->id,
            'created_by' => $user->id,
            'type' => Document::TYPE_INVOICE,
            'status' => 'draft',
            'currency' => 'XAF',
            'title' => 'Brouillon',
            'tax_rate' => 0,
            'issue_date' => now()->toDateString(),
            'subtotal' => 50000,
            'tax_amount' => 0,
            'total' => 50000,
        ]);

        $this->actingAs($user)
            ->get(route('app.dashboard'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('App/Dashboard')
                ->has('stats.documents_month')
                ->has('stats.revenue_month')
                ->where('stats.documents_month', 2)
                ->where('stats.revenue_month', 100000)
                ->where('stats.drafts', 1)
                ->has('series.documents', 14)
                ->has('series.revenue', 14)
                ->has('breakdowns.types')
                ->has('breakdowns.statuses')
                ->has('recentDocuments')
                ->has('topClients', 1)
                ->where('topClients.0.name', 'Elamigos')
                ->where('topClients.0.revenue', 150000));
    }

    public function test_guest_is_redirected_from_app_dashboard(): void
    {
        $this->get(route('app.dashboard'))
            ->assertRedirect(route('login'));
    }
}
