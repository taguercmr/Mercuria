<?php

namespace App\Mail;

use App\Models\TenantInvitation;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class TeamInvitationMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public TenantInvitation $invitation) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Invitation Mercuria — '.$this->invitation->tenant->name,
        );
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'emails.team-invitation',
            with: [
                'invitation' => $this->invitation,
                'acceptUrl' => route('invitations.show', $this->invitation->token),
                'tenantName' => $this->invitation->tenant->name,
                'role' => $this->invitation->role,
            ],
        );
    }
}
