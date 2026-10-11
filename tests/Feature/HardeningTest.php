<?php

namespace Tests\Feature;

use App\Models\Document;
use App\Models\Tenant;
use App\Models\User;
use App\Services\Documents\DocumentNumberGenerator;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class HardeningTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @return array{0: User, 1: Tenant}
     */
    private function membership(string $role, string $plan = 'pro'): array
    {
        $user = User::factory()->create();
        $tenant = Tenant::query()->create([
            'name' => 'Org '.$role,
            'email' => $user->email,
            'plan' => $plan,
            'currency' => 'XAF',
            'subscribed_at' => $plan === 'pro' ? now() : null,
            'subscription_ends_at' => $plan === 'pro' ? now()->addMonth() : null,
            'is_active' => true,
        ]);
        $tenant->users()->attach($user->id, ['role' => $role]);
        $user->forceFill(['current_tenant_id' => $tenant->id])->save();

        return [$user, $tenant];
    }

    public function test_owner_can_open_new_quote_form(): void
    {
        [$owner] = $this->membership('owner');

        $this->actingAs($owner)
            ->get(route('app.documents.create', ['type' => 'quote']))
            ->assertOk()
            ->assertInertia(fn (Assert $page): Assert => $page
                ->component('App/Documents/Form')
                ->where('type', 'quote')
                ->where('document', null)
            );
    }

    public function test_viewer_cannot_open_document_form(): void
    {
        [$viewer] = $this->membership('viewer');

        $this->actingAs($viewer)
            ->get(route('app.documents.create', ['type' => 'quote']))
            ->assertForbidden();
    }

    public function test_viewer_cannot_create_document(): void
    {
        [$viewer] = $this->membership('viewer');

        $this->actingAs($viewer)
            ->post(route('app.documents.store'), [
                'type' => 'quote',
                'status' => 'draft',
                'title' => 'Interdit',
                'currency' => 'XAF',
                'tax_rate' => 0,
                'lines' => [
                    [
                        'designation' => 'x',
                        'quantity' => 1,
                        'unit_price' => 100,
                    ],
                ],
            ])
            ->assertForbidden();
    }

    public function test_viewer_can_list_documents(): void
    {
        [$viewer] = $this->membership('viewer');

        $this->actingAs($viewer)
            ->get(route('app.documents.index'))
            ->assertOk();
    }

    public function test_editor_can_create_document(): void
    {
        [$editor] = $this->membership('editor', 'free');

        $this->actingAs($editor)
            ->post(route('app.documents.store'), [
                'type' => 'quote',
                'status' => 'draft',
                'title' => 'OK editor',
                'currency' => 'XAF',
                'tax_rate' => 0,
                'lines' => [
                    [
                        'designation' => 'Article',
                        'quantity' => 1,
                        'unit_price' => 1000,
                    ],
                ],
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('documents', ['title' => 'OK editor']);
        $this->assertDatabaseHas('activity_logs', ['action' => 'document.created']);
    }

    public function test_document_numbers_are_sequential(): void
    {
        [, $tenant] = $this->membership('owner');
        $generator = app(DocumentNumberGenerator::class);

        $a = $generator->next($tenant, Document::TYPE_QUOTE);
        $b = $generator->next($tenant, Document::TYPE_QUOTE);

        $this->assertSame('DEV-'.now()->format('Y').'-00001', $a);
        $this->assertSame('DEV-'.now()->format('Y').'-00002', $b);
    }

    public function test_health_endpoint(): void
    {
        $this->getJson(route('health'))
            ->assertOk()
            ->assertJsonPath('status', 'ok')
            ->assertJsonStructure(['checks' => ['database', 'search', 'vite']]);
    }
}
