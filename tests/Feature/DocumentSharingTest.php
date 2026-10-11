<?php

namespace Tests\Feature;

use App\Mail\DocumentSentMail;
use App\Models\Client;
use App\Models\Document;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\URL;
use Inertia\Testing\AssertableInertia as Assert;
use PHPUnit\Framework\Attributes\TestWith;
use Tests\TestCase;

class DocumentSharingTest extends TestCase
{
    use RefreshDatabase;

    #[TestWith(['contact@example.com'])]
    #[TestWith([null])]
    public function test_document_email_uses_mercuria_sender_and_company_or_sending_user_reply_address(?string $companyEmail): void
    {
        [$user, $tenant, $document] = $this->document();
        $tenant->update(['email' => $companyEmail]);
        config(['mail.from.address' => 'mercuria@example.com']);
        Mail::fake();

        $this->actingAs($user)->post(route('app.documents.send', $document), [
            'email' => 'client@example.com', 'from' => 'forged@example.com', 'reply_to' => 'forged@example.com',
        ])->assertRedirect()->assertSessionHasNoErrors();

        Mail::assertSent(DocumentSentMail::class, function (DocumentSentMail $mail) use ($companyEmail, $user): bool {
            $mail->assertFrom('mercuria@example.com', 'Entreprise Démo via Mercuria');
            $mail->assertHasReplyTo($companyEmail ?? $user->email, 'Entreprise Démo');
            $mail->assertTo('client@example.com');
            $mail->assertSeeInHtml('113 750 XAF');

            return true;
        });
        $this->assertSame('sent', $document->fresh()->status);
    }

    public function test_document_page_explains_where_replies_will_arrive(): void
    {
        [$user, , $document] = $this->document();
        config(['mail.from.address' => 'mercuria@example.com']);

        $this->actingAs($user)->get(route('app.documents.show', $document))->assertInertia(fn (Assert $page): Assert => $page
            ->component('App/Documents/Show')
            ->where('emailSender.name', 'Entreprise Démo via Mercuria')
            ->where('emailSender.address', 'mercuria@example.com')
            ->where('emailSender.reply_to', 'contact@example.com'));
    }

    #[TestWith(['+237 6 12 34 56 78'])]
    #[TestWith(['00237 (6)12-34-56-78'])]
    public function test_whatsapp_message_contains_signed_pdf_and_does_not_mark_document_as_sent(string $phone): void
    {
        $this->travelTo(Carbon::parse('2026-10-02 10:00:00 UTC'));
        [$user, , $document] = $this->document();
        Mail::fake();

        $response = $this->actingAs($user)->postJson($this->shareEndpoint($document), ['phone' => $phone, 'message' => 'Merci & à bientôt !']);

        $response->assertOk()->assertJsonPath('requires_pdf_attachment', false)->assertJsonPath('expires_at', '2026-10-09T10:00:00+00:00');
        $this->assertStringStartsWith('https://wa.me/237612345678?', $response->json('whatsapp_url'));
        parse_str(parse_url($response->json('whatsapp_url'), PHP_URL_QUERY), $query);
        $this->assertSame($response->json('message'), $query['text']);
        $this->assertStringContainsString('Merci & à bientôt !', $query['text']);
        $this->assertStringContainsString("\nNet à payer après retenue IR : 113 750 XAF.", $query['text']);
        $this->assertStringContainsString($response->json('pdf_url'), $query['text']);
        $this->assertStringContainsString('signature=', $response->json('pdf_url'));
        $this->assertSame('draft', $document->fresh()->status);
        $this->assertDatabaseHas('activity_logs', ['action' => 'document.whatsapp.prepared', 'subject_id' => $document->id]);
        Mail::assertNothingSent();
    }

    public function test_whatsapp_can_open_contact_picker_without_phone_number(): void
    {
        [$user, , $document] = $this->document();

        $response = $this->actingAs($user)->postJson($this->shareEndpoint($document));

        $response->assertOk();
        $this->assertStringStartsWith('https://wa.me/?text=', $response->json('whatsapp_url'));
    }

    #[TestWith(['localhost'])]
    #[TestWith(['127.0.0.1'])]
    #[TestWith(['192.168.1.20'])]
    public function test_local_installations_offer_pdf_attachment_instead_of_unreachable_link(string $host): void
    {
        [$user, , $document] = $this->document();

        $response = $this->actingAs($user)->postJson('http://'.$host.route('app.documents.whatsapp', $document, false));

        $response->assertOk()->assertJsonPath('requires_pdf_attachment', true)->assertJsonPath('pdf_url', null)->assertJsonPath('expires_at', null);
        $this->assertStringNotContainsString('/shared/documents/', $response->json('message'));
        $this->assertStringContainsString('Le document PDF est joint', $response->json('message'));
    }

    #[TestWith(['abc123'])]
    #[TestWith(['123'])]
    #[TestWith(['0123456789'])]
    public function test_invalid_phone_returns_422_without_preparing_share(string $phone): void
    {
        [$user, , $document] = $this->document();

        $this->actingAs($user)->postJson($this->shareEndpoint($document), ['phone' => $phone])
            ->assertUnprocessable()->assertJsonValidationErrors(['phone'])
            ->assertJsonPath('errors.phone.0', 'Indiquez un numéro international avec son indicatif, par exemple +237 6 12 34 56 78.');

        $this->assertDatabaseMissing('activity_logs', ['action' => 'document.whatsapp.prepared']);
    }

    public function test_overlong_message_returns_422(): void
    {
        [$user, , $document] = $this->document();

        $this->actingAs($user)->postJson($this->shareEndpoint($document), ['message' => str_repeat('a', 2001)])
            ->assertUnprocessable()->assertJsonPath('errors.message.0', 'Votre message ne doit pas dépasser 2 000 caractères.');
    }

    public function test_guest_cannot_generate_whatsapp_link(): void
    {
        [, , $document] = $this->document();

        $this->postJson($this->shareEndpoint($document))->assertUnauthorized();
    }

    public function test_viewer_cannot_generate_whatsapp_link(): void
    {
        [, $tenant, $document] = $this->document();
        $viewer = User::factory()->create(['current_tenant_id' => $tenant->id]);
        $tenant->users()->attach($viewer, ['role' => 'viewer']);

        $this->actingAs($viewer)->postJson($this->shareEndpoint($document))->assertForbidden();
    }

    public function test_other_tenant_cannot_generate_whatsapp_link(): void
    {
        [, , $document] = $this->document();
        [$otherUser] = $this->document();

        $this->actingAs($otherUser)->postJson($this->shareEndpoint($document))->assertNotFound();
    }

    public function test_signed_pdf_is_downloadable_by_client_without_login(): void
    {
        [$user, , $document] = $this->document();
        $link = $this->actingAs($user)->postJson($this->shareEndpoint($document))->json('pdf_url');
        auth()->logout();

        $response = $this->get($link);

        $response->assertOk()->assertHeader('Content-Type', 'application/pdf')->assertHeader('X-Robots-Tag', 'noindex, nofollow, noarchive');
        $this->assertStringContainsString('no-store', $response->headers->get('Cache-Control'));
        $this->assertStringStartsWith('%PDF-', $response->getContent());
    }

    public function test_signed_pdf_works_when_recipient_is_logged_into_another_tenant(): void
    {
        [$user, , $document] = $this->document();
        $link = $this->actingAs($user)->postJson($this->shareEndpoint($document))->json('pdf_url');
        [$recipient] = $this->document();

        $this->actingAs($recipient)->get($link)->assertOk()->assertHeader('Content-Type', 'application/pdf');
    }

    public function test_unsigned_pdf_access_is_forbidden(): void
    {
        [, , $document] = $this->document();

        $this->get(route('documents.shared.pdf', $document))->assertForbidden();
    }

    public function test_expired_pdf_link_is_forbidden(): void
    {
        $this->freezeTime();
        [$user, , $document] = $this->document();
        $link = $this->actingAs($user)->postJson($this->shareEndpoint($document))->json('pdf_url');
        auth()->logout();
        $this->travel(8)->days();

        $this->get($link)->assertForbidden();
    }

    public function test_changing_document_id_in_shared_link_is_forbidden(): void
    {
        [$user, , $document] = $this->document();
        [, , $otherDocument] = $this->document();
        $link = $this->actingAs($user)->postJson($this->shareEndpoint($document))->json('pdf_url');
        $tampered = str_replace('/'.$document->id.'/pdf', '/'.$otherDocument->id.'/pdf', $link);
        auth()->logout();

        $this->get($tampered)->assertForbidden();
    }

    public function test_disabled_company_shared_pdf_returns_404(): void
    {
        [, $tenant, $document] = $this->document();
        $link = URL::temporarySignedRoute('documents.shared.pdf', now()->addDays(7), ['document' => $document->id]);
        $tenant->update(['is_active' => false]);

        $this->get($link)->assertNotFound();
    }

    private function shareEndpoint(Document $document): string
    {
        return 'https://mercuria.example.com'.route('app.documents.whatsapp', $document, false);
    }

    /** @return array{0: User, 1: Tenant, 2: Document} */
    private function document(): array
    {
        $user = User::factory()->create();
        $tenant = Tenant::query()->create(['name' => 'Entreprise Démo', 'email' => 'contact@example.com', 'plan' => 'pro', 'currency' => 'XAF', 'is_active' => true, 'onboarding_completed' => true]);
        $tenant->users()->attach($user, ['role' => 'owner']);
        $user->forceFill(['current_tenant_id' => $tenant->id])->save();
        $client = Client::query()->create(['tenant_id' => $tenant->id, 'name' => 'Client Démo', 'email' => 'client@example.com']);
        $document = Document::query()->create([
            'tenant_id' => $tenant->id, 'created_by' => $user->id, 'client_id' => $client->id,
            'type' => 'quote', 'status' => 'draft', 'currency' => 'XAF', 'issue_date' => '2026-10-02',
            'design' => 'signature', 'tax_rate' => 19.25, 'ir_rate' => 5.5, 'ir_mode' => 'withholding',
        ]);
        $document->lines()->create(['designation' => 'Conseil', 'quantity' => 1, 'unit_price' => 100000, 'line_total' => 100000, 'position' => 0]);
        $document->recalculateTotals();

        return [$user, $tenant, $document];
    }
}
