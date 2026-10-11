<?php

namespace Tests\Feature;

use App\Mail\TeamInvitationMail;
use App\Models\ApiKey;
use App\Models\Document;
use App\Models\MercurialeArticle;
use App\Models\MercurialeFamille;
use App\Models\MercurialeRubrique;
use App\Models\Tenant;
use App\Models\TenantInvitation;
use App\Models\User;
use App\Services\Billing\PlanQuotaService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class MercuriaCoreTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @return array{0: User, 1: Tenant}
     */
    private function actingTenantOwner(string $plan = 'free'): array
    {
        $user = User::factory()->create();
        $tenant = Tenant::query()->create([
            'name' => 'Cabinet Test',
            'email' => $user->email,
            'plan' => $plan,
            'currency' => 'XAF',
            'onboarding_completed' => true,
            'subscribed_at' => $plan === 'pro' ? now() : null,
            'subscription_ends_at' => $plan === 'pro' ? now()->addMonth() : null,
            'is_active' => true,
        ]);
        $tenant->users()->attach($user->id, ['role' => 'owner']);
        $user->forceFill(['current_tenant_id' => $tenant->id])->save();

        return [$user, $tenant->fresh()];
    }

    public function test_owner_can_invite_team_member_on_pro_plan(): void
    {
        Mail::fake();
        [$owner] = $this->actingTenantOwner('pro');

        $response = $this->actingAs($owner)->post(route('app.team.invite'), [
            'email' => 'collegue@example.com',
            'role' => 'editor',
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('tenant_invitations', [
            'email' => 'collegue@example.com',
            'role' => 'editor',
        ]);
        Mail::assertSent(TeamInvitationMail::class);
    }

    public function test_free_plan_blocks_second_team_member(): void
    {
        Mail::fake();
        [$owner] = $this->actingTenantOwner('free');

        $response = $this->actingAs($owner)->post(route('app.team.invite'), [
            'email' => 'autre@example.com',
            'role' => 'viewer',
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('error');
        $this->assertDatabaseCount('tenant_invitations', 0);
    }

    public function test_guest_can_accept_invitation_and_join_tenant(): void
    {
        [, $tenant] = $this->actingTenantOwner('pro');
        $inviter = $tenant->users()->first();

        $invitation = TenantInvitation::createFor(
            $tenant,
            $inviter,
            'nouveau@example.com',
            'editor',
        );

        $response = $this->post(route('invitations.accept', $invitation->token), [
            'name' => 'Nouveau Membre',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ]);

        $response->assertRedirect(route('app.dashboard'));
        $this->assertAuthenticated();
        $this->assertTrue(
            $tenant->users()->where('email', 'nouveau@example.com')->exists()
        );
    }

    public function test_document_quota_blocks_free_plan_over_limit(): void
    {
        [$user, $tenant] = $this->actingTenantOwner('free');
        $limit = (int) config('mercuria.plans.free.documents_per_month');
        $tenant->forceFill([
            'documents_used_month' => $limit,
            'usage_month' => now()->format('Y-m'),
        ])->save();

        $response = $this->actingAs($user)->post(route('app.documents.store'), [
            'type' => 'quote',
            'status' => 'draft',
            'title' => 'Devis test',
            'currency' => 'XAF',
            'tax_rate' => 0,
            'lines' => [
                [
                    'designation' => 'Article test',
                    'unit' => 'U',
                    'quantity' => 1,
                    'unit_price' => 1000,
                ],
            ],
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('error');
        $this->assertSame(0, Document::query()->count());
    }

    public function test_document_can_be_created_under_quota(): void
    {
        [$user] = $this->actingTenantOwner('free');

        $response = $this->actingAs($user)->post(route('app.documents.store'), [
            'type' => 'quote',
            'status' => 'draft',
            'title' => 'Devis OK',
            'currency' => 'XAF',
            'tax_rate' => 0,
            'lines' => [
                [
                    'designation' => 'Fourniture bureau',
                    'unit' => 'U',
                    'quantity' => 2,
                    'unit_price' => 5000,
                ],
            ],
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('documents', ['title' => 'Devis OK', 'type' => 'quote']);
    }

    public function test_api_requires_key(): void
    {
        $this->getJson('/api/v1/mercuriale/rubriques')
            ->assertStatus(401)
            ->assertJson(['message' => 'API key required.']);
    }

    public function test_api_rejects_free_plan_even_with_key(): void
    {
        [$user, $tenant] = $this->actingTenantOwner('free');
        $issued = ApiKey::issue($tenant, $user, 'test');

        $this->withHeader('X-Api-Key', $issued['plain'])
            ->getJson('/api/v1/mercuriale/rubriques')
            ->assertStatus(402);
    }

    public function test_existing_api_key_returns_402_at_expiry_and_works_again_after_renewal(): void
    {
        $this->freezeSecond();
        [$user, $tenant] = $this->actingTenantOwner('pro');
        $issued = ApiKey::issue($tenant, $user, 'subscription lifecycle');
        $expiresAt = $tenant->subscription_ends_at->copy();

        $this->travelTo($expiresAt->copy()->subSecond());
        $this->withHeader('X-Api-Key', $issued['plain'])
            ->getJson('/api/v1/mercuriale/rubriques')
            ->assertOk();

        $this->travelTo($expiresAt);

        foreach ([
            '/api/v1/mercuriale/rubriques',
            '/api/v1/mercuriale/articles',
            '/api/v1/mercuriale/articles/01-001-100001',
        ] as $endpoint) {
            $this->getJson($endpoint)->assertStatus(402);
        }

        $this->travel(1)->day();
        $this->getJson('/api/v1/mercuriale/rubriques')->assertStatus(402);

        $tenant->update(['subscription_ends_at' => now()->addMonth()]);

        $this->getJson('/api/v1/mercuriale/rubriques')->assertOk();
        $this->assertTrue($issued['model']->fresh()->is_active);
    }

    public function test_expired_subscription_cannot_create_a_new_api_key(): void
    {
        $this->freezeSecond();
        [$user, $tenant] = $this->actingTenantOwner('pro');
        $tenant->update(['subscription_ends_at' => now()->subSecond()]);

        $this->actingAs($user)
            ->post(route('app.developers.keys.store'), ['name' => 'New integration'])
            ->assertRedirect()
            ->assertSessionHas('error');

        $this->assertDatabaseCount('api_keys', 0);
    }

    public function test_pro_api_lists_rubriques(): void
    {
        [$user, $tenant] = $this->actingTenantOwner('pro');
        $issued = ApiKey::issue($tenant, $user, 'prod');

        $rubrique = MercurialeRubrique::query()->create([
            'code' => '01',
            'name' => 'Fournitures',
            'articles_count' => 1,
        ]);
        $famille = MercurialeFamille::query()->create([
            'rubrique_id' => $rubrique->id,
            'code' => '001',
            'name' => 'Général',
            'articles_count' => 1,
        ]);
        MercurialeArticle::query()->create([
            'rubrique_id' => $rubrique->id,
            'famille_id' => $famille->id,
            'reference' => '01-001-100001',
            'article_code' => '100001',
            'designation' => 'Article test',
            'unit' => 'UNITE',
            'quantity' => 1,
            'unit_price' => 1000,
            'budget_year' => (int) config('mercuria.mercuriale_year', 2026),
            'source' => 'test',
        ]);

        $this->withHeader('X-Api-Key', $issued['plain'])
            ->getJson('/api/v1/mercuriale/rubriques')
            ->assertOk()
            ->assertJsonPath('data.0.code', '01');
    }

    public function test_plan_quota_service_team_slot(): void
    {
        [, $tenant] = $this->actingTenantOwner('free');
        $quotas = app(PlanQuotaService::class);

        $this->expectException(\RuntimeException::class);
        $quotas->ensureTeamSlot($tenant);
    }

    public function test_document_can_be_duplicated(): void
    {
        [$user] = $this->actingTenantOwner('free');

        $document = Document::query()->create([
            'tenant_id' => $user->current_tenant_id,
            'created_by' => $user->id,
            'type' => 'quote',
            'status' => 'draft',
            'currency' => 'XAF',
            'title' => 'Original',
            'tax_rate' => 0,
            'issue_date' => now()->toDateString(),
        ]);
        $document->lines()->create([
            'designation' => 'Ligne A',
            'quantity' => 1,
            'unit_price' => 1000,
            'line_total' => 1000,
            'position' => 0,
        ]);

        $response = $this->actingAs($user)->post(route('app.documents.duplicate', $document));

        $response->assertRedirect();
        $this->assertDatabaseHas('documents', ['title' => 'Original (copie)', 'status' => 'draft']);
        $this->assertSame(2, Document::query()->count());
    }

    public function test_user_can_switch_tenant(): void
    {
        [$user, $tenantA] = $this->actingTenantOwner('free');
        $tenantB = Tenant::query()->create([
            'name' => 'Autre Cabinet',
            'email' => $user->email,
            'plan' => 'free',
            'currency' => 'XAF',
            'is_active' => true,
        ]);
        $tenantB->users()->attach($user->id, ['role' => 'editor']);

        $this->actingAs($user)
            ->post(route('app.tenant.switch', $tenantB))
            ->assertRedirect(route('app.dashboard'));

        $this->assertSame($tenantB->id, $user->fresh()->current_tenant_id);
        $this->assertNotSame($tenantA->id, $user->fresh()->current_tenant_id);
    }

    public function test_clients_csv_export(): void
    {
        [$user] = $this->actingTenantOwner('free');

        $this->actingAs($user)
            ->get(route('app.clients.export'))
            ->assertOk()
            ->assertHeader('content-disposition');
    }

    public function test_catalogue_search_prioritizes_reference(): void
    {
        [$user] = $this->actingTenantOwner('pro');

        $rubrique = MercurialeRubrique::query()->create([
            'code' => '02',
            'name' => 'Materiel',
            'articles_count' => 2,
        ]);
        $famille = MercurialeFamille::query()->create([
            'rubrique_id' => $rubrique->id,
            'code' => '001',
            'name' => 'Famille A',
            'articles_count' => 2,
        ]);

        MercurialeArticle::query()->create([
            'rubrique_id' => $rubrique->id,
            'famille_id' => $famille->id,
            'reference' => '02-001-000099',
            'article_code' => '000099',
            'designation' => 'Stylo bleu',
            'unit' => 'U',
            'quantity' => 1,
            'unit_price' => 100,
            'budget_year' => 2026,
        ]);
        MercurialeArticle::query()->create([
            'rubrique_id' => $rubrique->id,
            'famille_id' => $famille->id,
            'reference' => '02-001-000001',
            'article_code' => '000001',
            'designation' => 'Autre',
            'unit' => 'U',
            'quantity' => 1,
            'unit_price' => 200,
            'budget_year' => 2026,
        ]);

        $this->actingAs($user)->get(route('app.catalogue.index', [
            'q' => '02-001-000001',
        ]))->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('App/Catalogue/Index')
                ->has('articles.data', 1)
                ->where('articles.data.0.reference', '02-001-000001'));
    }
}
