<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class NewPasswordController extends Controller
{
    /**
     * Display the password reset view.
     */
    public function create(Request $request): Response
    {
        return Inertia::render('Auth/ResetPassword', [
            'email' => $request->session()->get('password_reset_email', ''),
            'status' => $request->session()->get('status'),
            'expiresInMinutes' => config('auth.passwords.users.expire'),
            'resendAvailableIn' => max(0, 60 - (now()->timestamp - (int) $request->session()->get('password_reset_requested_at', 0))),
        ]);
    }

    /**
     * Handle an incoming new password request.
     *
     * @throws ValidationException
     */
    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'code' => ['required', 'string', 'regex:/^[A-Za-z2-9]{7}$/'],
            'email' => ['required', 'string', 'email', 'max:255'],
            'password' => ['required', 'confirmed', Rules\Password::defaults()],
        ], [
            'email.required' => 'Indiquez votre adresse email.',
            'email.email' => 'Indiquez une adresse email valide.',
            'code.required' => 'Saisissez le code reçu par email.',
            'code.regex' => 'Le code doit contenir exactement 7 lettres ou chiffres.',
            'password.required' => 'Choisissez un nouveau mot de passe.',
            'password.min' => 'Le mot de passe doit contenir au moins :min caractères.',
            'password.confirmed' => 'Les deux mots de passe ne correspondent pas.',
        ]);

        $status = Password::reset(
            [
                'email' => mb_strtolower(trim($data['email'])),
                'token' => strtoupper($data['code']),
                'password' => $data['password'],
            ],
            function (User $user, string $password): void {
                $user->forceFill([
                    'password' => Hash::make($password),
                    'remember_token' => Str::random(60),
                ])->save();

                event(new PasswordReset($user));
            }
        );

        if ($status == Password::PASSWORD_RESET) {
            $request->session()->forget(['password_reset_email', 'password_reset_requested_at']);

            return redirect()->route('login')->with('status', 'Votre mot de passe a été modifié. Vous pouvez maintenant vous connecter.');
        }

        throw ValidationException::withMessages([
            'code' => 'Ce code est invalide ou expiré. Vérifiez l’adresse email et le code, ou demandez-en un nouveau.',
        ]);
    }
}
