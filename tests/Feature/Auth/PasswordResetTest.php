<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use App\Notifications\PasswordResetCode;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Exceptions;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Inertia\Testing\AssertableInertia as Assert;
use Symfony\Component\Mailer\Exception\TransportException;
use Tests\TestCase;

class PasswordResetTest extends TestCase
{
    use RefreshDatabase;

    public function test_reset_code_request_screen_can_be_rendered(): void
    {
        $this->get(route('password.request'))
            ->assertOk()
            ->assertInertia(fn (Assert $page): Assert => $page->component('Auth/ForgotPassword'));
    }

    public function test_seven_character_code_is_sent_and_only_its_hash_is_stored(): void
    {
        Notification::fake();
        $user = User::factory()->create();

        $this->post(route('password.email'), ['email' => $user->email])
            ->assertRedirect(route('password.reset'))
            ->assertSessionHas('password_reset_email', $user->email)
            ->assertSessionHasNoErrors();

        Notification::assertSentTo($user, PasswordResetCode::class, function (PasswordResetCode $notification, array $channels) use ($user): bool {
            $this->assertSame(['mail'], $channels);
            $this->assertMatchesRegularExpression('/^[A-HJ-NP-Z2-9]{7}$/', $notification->code);
            $storedToken = DB::table('password_reset_tokens')->where('email', $user->email)->value('token');
            $this->assertNotSame($notification->code, $storedToken);
            $this->assertTrue(Hash::check($notification->code, $storedToken));

            return true;
        });
    }

    public function test_reset_screen_accepts_manual_code_entry_without_a_token_in_the_url(): void
    {
        $this->withSession(['password_reset_email' => 'person@example.com'])
            ->get(route('password.reset'))
            ->assertOk()
            ->assertInertia(fn (Assert $page): Assert => $page
                ->component('Auth/ResetPassword')
                ->where('email', 'person@example.com')
                ->where('expiresInMinutes', 15)
                ->missing('token')
                ->missing('code')
            );
    }

    public function test_valid_code_changes_password_and_cannot_be_reused(): void
    {
        $user = User::factory()->create(['remember_token' => 'old-remember-token']);
        $code = $this->requestCode($user);
        Event::fake([PasswordReset::class]);

        $this->post(route('password.store'), $this->resetPayload($user, strtolower($code)))
            ->assertSessionHasNoErrors()
            ->assertSessionMissing('password_reset_email')
            ->assertRedirect(route('login'));

        $this->assertTrue(Hash::check('New-secure-password-42', $user->fresh()->password));
        $this->assertNotSame('old-remember-token', $user->fresh()->remember_token);
        $this->assertDatabaseMissing('password_reset_tokens', ['email' => $user->email]);
        Event::assertDispatched(PasswordReset::class, fn (PasswordReset $event): bool => $event->user->is($user));
        $this->assertGuest();

        $this->post(route('password.store'), $this->resetPayload($user, $code, 'Another-password-42'))
            ->assertSessionHasErrors('code');
        $this->assertTrue(Hash::check('New-secure-password-42', $user->fresh()->password));
    }

    public function test_invalid_code_does_not_change_password(): void
    {
        $user = User::factory()->create();
        $originalPassword = $user->password;

        $this->from(route('password.reset'))->post(route('password.store'), $this->resetPayload($user, 'AAAAAAA'))
            ->assertRedirect(route('password.reset'))
            ->assertSessionHasErrors(['code' => 'Ce code est invalide ou expiré. Vérifiez l’adresse email et le code, ou demandez-en un nouveau.']);

        $this->assertSame($originalPassword, $user->fresh()->password);
        $this->assertNull(session()->getOldInput('code'));
    }

    public function test_code_cannot_reset_another_account(): void
    {
        $owner = User::factory()->create();
        $other = User::factory()->create();
        $originalPassword = $other->password;
        $code = $this->requestCode($owner);

        $this->post(route('password.store'), $this->resetPayload($other, $code))
            ->assertSessionHasErrors('code');

        $this->assertSame($originalPassword, $other->fresh()->password);
        $this->assertDatabaseHas('password_reset_tokens', ['email' => $owner->email]);
    }

    public function test_code_expires_after_fifteen_minutes(): void
    {
        $this->freezeTime();
        $user = User::factory()->create();
        $originalPassword = $user->password;
        $code = $this->requestCode($user);
        $this->travel(16)->minutes();

        $this->post(route('password.store'), $this->resetPayload($user, $code))
            ->assertSessionHasErrors('code');

        $this->assertSame($originalPassword, $user->fresh()->password);
    }

    public function test_resending_invalidates_the_previous_code(): void
    {
        $this->freezeTime();
        $user = User::factory()->create();
        $oldCode = $this->requestCode($user);
        $this->travel(61)->seconds();
        $newCode = $this->requestCode($user);

        $this->post(route('password.store'), $this->resetPayload($user, $oldCode))
            ->assertSessionHasErrors('code');
        $this->post(route('password.store'), $this->resetPayload($user, $newCode))
            ->assertSessionHasNoErrors()
            ->assertRedirect(route('login'));
    }

    public function test_resending_requires_a_one_minute_wait(): void
    {
        $this->freezeTime();
        $user = User::factory()->create();
        $this->requestCode($user);

        $this->from(route('password.reset'))->post(route('password.email'), ['email' => $user->email])
            ->assertRedirect(route('password.reset'))
            ->assertSessionHasErrors(['email' => 'Veuillez patienter une minute avant de demander un nouveau code.']);

        Notification::assertSentToTimes($user, PasswordResetCode::class, 1);
    }

    public function test_reset_attempts_are_limited_per_email_even_from_different_ip_addresses(): void
    {
        $this->freezeTime();
        $user = User::factory()->create();
        $originalPassword = $user->password;
        $code = $this->requestCode($user);

        for ($attempt = 1; $attempt <= 5; $attempt++) {
            $this->withServerVariables(['REMOTE_ADDR' => '192.0.2.'.$attempt])
                ->post(route('password.store'), $this->resetPayload($user, 'AAAAAAA'))
                ->assertSessionHasErrors('code');
        }

        $payload = $this->resetPayload($user, $code);
        $payload['email'] = strtoupper($user->email);
        $this->withServerVariables(['REMOTE_ADDR' => '192.0.2.20'])
            ->post(route('password.store'), $payload)
            ->assertSessionHasErrors(['code' => 'Trop de tentatives. Patientez 15 minutes avant de réessayer, puis demandez un nouveau code.']);

        $this->assertSame($originalPassword, $user->fresh()->password);
    }

    public function test_unknown_email_receives_the_same_public_response(): void
    {
        Notification::fake();
        $user = User::factory()->create();
        $this->post(route('password.email'), ['email' => $user->email])
            ->assertRedirect(route('password.reset'));
        $expectedStatus = session('status');
        Notification::assertSentTo($user, PasswordResetCode::class);
        Notification::fake();

        $this->post(route('password.email'), ['email' => 'absent@example.com'])
            ->assertRedirect(route('password.reset'))
            ->assertSessionHasNoErrors()
            ->assertSessionHas('status', $expectedStatus);

        Notification::assertNothingSent();
        $this->assertDatabaseMissing('password_reset_tokens', ['email' => 'absent@example.com']);
    }

    public function test_password_confirmation_is_required_before_consuming_the_code(): void
    {
        $user = User::factory()->create();
        $originalPassword = $user->password;
        $code = $this->requestCode($user);
        $payload = $this->resetPayload($user, $code);
        $payload['password_confirmation'] = 'Different-password';

        $this->post(route('password.store'), $payload)
            ->assertSessionHasErrors(['password' => 'Les deux mots de passe ne correspondent pas.']);

        $this->assertSame($originalPassword, $user->fresh()->password);
        $this->assertDatabaseHas('password_reset_tokens', ['email' => $user->email]);
    }

    public function test_short_codes_are_rejected_and_not_flashed_to_the_session(): void
    {
        $user = User::factory()->create();

        $this->post(route('password.store'), $this->resetPayload($user, 'ABC'))
            ->assertSessionHasErrors(['code' => 'Le code doit contenir exactement 7 lettres ou chiffres.']);

        $this->assertNull(session()->getOldInput('code'));
        $this->assertNull(session()->getOldInput('password'));
    }

    public function test_email_contains_the_code_and_expiry_without_putting_the_code_in_a_link(): void
    {
        $notification = new PasswordResetCode('ABCD234');
        $message = $notification->toMail(User::factory()->make());
        $html = (string) $message->render();

        $this->assertStringContainsString('ABCD234', $html);
        $this->assertStringContainsString('15 minutes', $html);
        $this->assertSame(route('password.reset'), $message->actionUrl);
        $this->assertSame('Votre code de réinitialisation Mercuria', $message->subject);
    }

    public function test_mail_delivery_failure_returns_a_useful_error(): void
    {
        Exceptions::fake();
        $user = User::factory()->create();
        Notification::shouldReceive('send')->once()->andThrow(new TransportException('SMTP unavailable'));

        $this->from(route('password.request'))->post(route('password.email'), ['email' => $user->email])
            ->assertRedirect(route('password.request'))
            ->assertSessionHasErrors(['email' => 'L’envoi de l’email est momentanément indisponible. Réessayez dans une minute.']);

        Exceptions::assertReported(TransportException::class);
    }

    private function requestCode(User $user): string
    {
        Notification::fake();
        $this->post(route('password.email'), ['email' => $user->email])
            ->assertRedirect(route('password.reset'))
            ->assertSessionHasNoErrors();
        Notification::assertSentTo($user, PasswordResetCode::class);

        return Notification::sent($user, PasswordResetCode::class)->first()->code;
    }

    /**
     * @return array{email: string, code: string, password: string, password_confirmation: string}
     */
    private function resetPayload(User $user, string $code, string $password = 'New-secure-password-42'): array
    {
        return [
            'email' => $user->email,
            'code' => $code,
            'password' => $password,
            'password_confirmation' => $password,
        ];
    }
}
