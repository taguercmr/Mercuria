<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Password;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\Mailer\Exception\TransportExceptionInterface;

class PasswordResetLinkController extends Controller
{
    /**
     * Display the password reset code request view.
     */
    public function create(): Response
    {
        return Inertia::render('Auth/ForgotPassword', [
            'status' => session('status'),
            'email' => session('password_reset_email', ''),
        ]);
    }

    /**
     * Send a recovery code without disclosing whether the account exists.
     *
     * @throws ValidationException
     */
    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'email' => ['required', 'string', 'email', 'max:255'],
        ], [
            'email.required' => 'Indiquez votre adresse email.',
            'email.email' => 'Indiquez une adresse email valide.',
        ]);
        $email = mb_strtolower(trim($data['email']));

        try {
            Password::sendResetLink(['email' => $email]);
        } catch (TransportExceptionInterface $exception) {
            report($exception);

            throw ValidationException::withMessages([
                'email' => 'L’envoi de l’email est momentanément indisponible. Réessayez dans une minute.',
            ]);
        }

        $request->session()->put([
            'password_reset_email' => $email,
            'password_reset_requested_at' => now()->timestamp,
        ]);

        return redirect()->route('password.reset')->with(
            'status',
            'Si un compte correspond à cette adresse, un code de 7 caractères vous a été envoyé. Vérifiez aussi vos courriers indésirables.',
        );
    }
}
