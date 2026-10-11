<?php

namespace App\Notifications;

use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class PasswordResetCode extends Notification
{
    public function __construct(public string $code) {}

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Votre code de réinitialisation Mercuria')
            ->greeting('Bonjour,')
            ->line('Vous avez demandé un nouveau mot de passe pour votre compte Mercuria.')
            ->line('Voici votre code de 7 caractères :')
            ->line('**'.$this->code.'**')
            ->line('Ce code est valable '.config('auth.passwords.users.expire').' minutes et ne peut être utilisé qu’une seule fois.')
            ->action('Choisir un nouveau mot de passe', route('password.reset'))
            ->line('Ne partagez ce code avec personne. Si vous n’êtes pas à l’origine de cette demande, ignorez cet email : votre mot de passe reste inchangé.')
            ->salutation('L’équipe Mercuria');
    }
}
