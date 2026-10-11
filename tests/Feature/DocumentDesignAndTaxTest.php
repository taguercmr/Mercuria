<?php

namespace Tests\Feature;

use App\Mail\DocumentSentMail;
use App\Models\Client;
use App\Models\Document;
use App\Models\DocumentTemplate;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Inertia\Testing\AssertableInertia as Assert;
use PHPUnit\Framework\Attributes\TestWith;
use Tests\TestCase;

class DocumentDesignAndTaxTest extends TestCase
{
    use RefreshDatabase;

    #[TestWith(['none', 119250, 119250, 0])]
    #[TestWith(['withholding', 119250, 113750, 5500])]
    #[TestWith(['addition', 124750, 124750, 5500])]
    public function test_document_calculates_vat_ir_and_payable_total(string $mode, int $ttc, int $net, int $ir): void
    {
        [$user] = $this->membership();
        $document = $this->createDocument($user, ['ir_mode' => $mode, 'subtotal' => 1, 'tax_amount' => 1, 'ir_amount' => 1, 'total' => 1, 'net_total' => 1]);

        $this->assertSame(100000, $document->subtotal);
        $this->assertSame(19250, $document->tax_amount);
        $this->assertSame($ir, $document->ir_amount);
        $this->assertSame($ttc, $document->total);
        $this->assertSame($net, $document->net_total);
        $this->assertSame('executive', $document->design);
    }

    public function test_amounts_are_rounded_to_the_currency_unit(): void
    {
        [$user] = $this->membership();
        $document = $this->createDocument($user, ['lines' => [['designation' => 'Matériel', 'quantity' => 1, 'unit_price' => 858992]]]);

        $this->assertSame(165356, $document->tax_amount);
        $this->assertSame(47245, $document->ir_amount);
        $this->assertSame(1024348, $document->total);
        $this->assertSame(977103, $document->net_total);
    }

    public function test_update_recalculates_totals_and_can_remove_taxes(): void
    {
        [$user] = $this->membership();
        $document = $this->createDocument($user);

        $this->put(route('app.documents.update', $document), $this->payload(['tax_rate' => 0, 'ir_mode' => 'none', 'ir_rate' => 5.5, 'design' => 'minimal']))
            ->assertSessionHasNoErrors()->assertRedirect(route('app.documents.show', $document));

        $document->refresh();
        $this->assertSame(0, $document->tax_amount);
        $this->assertSame(0, $document->ir_amount);
        $this->assertSame('0.00', $document->ir_rate);
        $this->assertSame(100000, $document->total);
        $this->assertSame(100000, $document->net_total);
        $this->assertSame('minimal', $document->design);
    }

    public function test_existing_payloads_without_ir_or_design_keep_their_amounts(): void
    {
        [$user] = $this->membership();
        $payload = $this->payload();
        unset($payload['ir_rate'], $payload['ir_mode'], $payload['design']);
        $this->actingAs($user)->post(route('app.documents.store'), $payload)->assertSessionHasNoErrors();
        $document = Document::query()->firstOrFail();

        $this->assertSame('signature', $document->design);
        $this->assertSame(119250, $document->total);
        $this->assertSame(119250, $document->net_total);
    }

    public function test_design_can_be_changed_without_changing_the_amounts(): void
    {
        [$user] = $this->membership();
        $document = $this->createDocument($user);
        $this->from(route('app.documents.show', $document))->patch(route('app.documents.design', $document), ['design' => 'atelier', 'total' => 1])
            ->assertSessionHasNoErrors()->assertRedirect(route('app.documents.show', $document));
        $document->refresh();

        $this->assertSame('atelier', $document->design);
        $this->assertSame(119250, $document->total);
        $this->assertSame(113750, $document->net_total);
    }

    #[TestWith(['tax_rate', -1])]
    #[TestWith(['tax_rate', 101])]
    #[TestWith(['ir_rate', -5.5])]
    #[TestWith(['ir_rate', 100.01])]
    #[TestWith(['ir_rate', 5.555])]
    #[TestWith(['ir_mode', 'unexpected'])]
    #[TestWith(['design', '../../untrusted'])]
    public function test_invalid_rates_and_designs_are_rejected(string $field, mixed $value): void
    {
        [$user] = $this->membership();
        $this->actingAs($user)->post(route('app.documents.store'), $this->payload([$field => $value]))->assertSessionHasErrors($field);
        $this->assertDatabaseCount('documents', 0);
    }

    public function test_client_from_another_tenant_cannot_be_used(): void
    {
        [$user] = $this->membership();
        [, $otherTenant] = $this->membership();
        $client = Client::query()->create(['tenant_id' => $otherTenant->id, 'name' => 'Autre client']);

        $this->actingAs($user)->post(route('app.documents.store'), $this->payload(['client_id' => $client->id]))->assertSessionHasErrors('client_id');
        $this->assertDatabaseCount('documents', 0);
    }

    public function test_viewer_cannot_change_design(): void
    {
        [$owner, $tenant] = $this->membership();
        $document = $this->createDocument($owner);
        $viewer = User::factory()->create(['current_tenant_id' => $tenant->id]);
        $tenant->users()->attach($viewer, ['role' => 'viewer']);

        $this->actingAs($viewer)->patch(route('app.documents.design', $document), ['design' => 'classic'])->assertForbidden();
        $this->assertSame('executive', $document->fresh()->design);
    }

    public function test_other_tenant_cannot_change_design_or_download_pdf(): void
    {
        [$owner] = $this->membership();
        $document = $this->createDocument($owner);
        [$otherUser] = $this->membership();

        $this->actingAs($otherUser)->patch(route('app.documents.design', $document), ['design' => 'classic'])->assertNotFound();
        $this->get(route('app.documents.pdf', $document))->assertNotFound();
    }

    public function test_duplicate_and_conversion_preserve_design_and_taxes(): void
    {
        [$user] = $this->membership();
        $document = $this->createDocument($user);
        $this->post(route('app.documents.duplicate', $document))->assertSessionHasNoErrors()->assertRedirect();
        $copy = Document::query()->latest('id')->firstOrFail();
        $this->assertSame('executive', $copy->design);
        $this->assertSame('withholding', $copy->ir_mode);
        $this->assertSame('5.50', $copy->ir_rate);
        $this->assertSame(113750, $copy->net_total);

        $this->post(route('app.documents.convert', $document), ['to_type' => 'invoice'])->assertSessionHasNoErrors()->assertRedirect();
        $invoice = Document::query()->latest('id')->firstOrFail();
        $this->assertSame('invoice', $invoice->type);
        $this->assertSame('executive', $invoice->design);
        $this->assertSame('5.50', $invoice->ir_rate);
        $this->assertSame(113750, $invoice->net_total);
    }

    public function test_saved_template_restores_design_and_rates_in_the_form(): void
    {
        [$user] = $this->membership();
        $document = $this->createDocument($user);
        $this->post(route('app.documents.template', $document), ['name' => 'Fournitures Prestige'])->assertSessionHasNoErrors();
        $template = DocumentTemplate::query()->firstOrFail();
        $this->assertSame('executive', $template->design);
        $this->assertSame('5.50', $template->ir_rate);

        $this->get(route('app.documents.create', ['template' => $template->id]))->assertOk()->assertInertia(fn (Assert $page): Assert => $page
            ->component('App/Documents/Form')->where('document.design', 'executive')->where('document.tax_rate', '19.25')->where('document.ir_rate', '5.50')->where('document.ir_mode', 'withholding')->has('designs', 5));
    }

    public function test_gallery_design_can_be_used_as_the_starting_design(): void
    {
        [$user] = $this->membership();
        $this->actingAs($user)->get(route('app.documents.create', ['design' => 'atelier']))->assertOk()->assertInertia(fn (Assert $page): Assert => $page->component('App/Documents/Form')->where('defaultDesign', 'atelier')->has('designs', 5));
        $this->get(route('app.templates.index'))->assertOk()->assertInertia(fn (Assert $page): Assert => $page->component('App/Templates/Index')->has('designs', 5));
    }

    #[TestWith(['signature'])]
    #[TestWith(['minimal'])]
    #[TestWith(['executive'])]
    #[TestWith(['classic'])]
    #[TestWith(['atelier'])]
    public function test_each_design_renders_as_pdf_with_detailed_taxes(string $design): void
    {
        [$user, $tenant] = $this->membership();
        $document = $this->createDocument($user, ['design' => $design, 'title' => '<script>alert(1)</script>']);
        $document->load(['client', 'lines']);
        $html = view('pdf.document', ['document' => $document, 'tenant' => $tenant, 'typeLabel' => 'Devis'])->render();
        $this->assertStringContainsString('TVA (19,25 %)', $html);
        $this->assertStringContainsString('Retenue IR (5,5 % du HT)', $html);
        $this->assertStringContainsString('113 750 XAF', $html);
        $this->assertStringContainsString('01/10/2026', $html);
        $this->assertStringNotContainsString('<script>alert(1)</script>', $html);
        $this->assertStringContainsString('&lt;script&gt;', $html);

        $response = $this->get(route('app.documents.pdf', $document));
        $response->assertOk()->assertHeader('content-type', 'application/pdf');
        $this->assertStringStartsWith('%PDF-', $response->getContent());
    }

    public function test_email_uses_the_saved_document_design_and_taxes(): void
    {
        Mail::fake();
        [$user] = $this->membership();
        $document = $this->createDocument($user);
        $this->post(route('app.documents.send', $document), ['email' => 'client@example.com'])->assertSessionHasNoErrors()->assertRedirect();
        Mail::assertSent(DocumentSentMail::class, fn (DocumentSentMail $mail): bool => $mail->hasTo('client@example.com') && $mail->document->design === 'executive' && $mail->document->net_total === 113750);
    }

    /** @return array{0: User, 1: Tenant} */
    private function membership(): array
    {
        $user = User::factory()->create();
        $tenant = Tenant::query()->create(['name' => 'Atelier Démo', 'email' => $user->email, 'plan' => 'pro', 'currency' => 'XAF', 'is_active' => true, 'subscribed_at' => now(), 'subscription_ends_at' => now()->addMonth(), 'onboarding_completed' => true]);
        $tenant->users()->attach($user, ['role' => 'owner']);
        $user->forceFill(['current_tenant_id' => $tenant->id])->save();

        return [$user, $tenant];
    }

    /** @param array<string, mixed> $overrides */
    private function createDocument(User $user, array $overrides = []): Document
    {
        $this->actingAs($user)->post(route('app.documents.store'), $this->payload($overrides))->assertSessionHasNoErrors()->assertRedirect();

        return Document::query()->latest('id')->firstOrFail();
    }

    /**
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    private function payload(array $overrides = []): array
    {
        return array_replace(['type' => 'quote', 'status' => 'draft', 'title' => 'Fournitures', 'currency' => 'XAF', 'issue_date' => '2026-10-01', 'tax_rate' => 19.25, 'ir_rate' => 5.5, 'ir_mode' => 'withholding', 'design' => 'executive', 'lines' => [['designation' => 'Prestation de démonstration', 'quantity' => 1, 'unit_price' => 100000]]], $overrides);
    }
}
