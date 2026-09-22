<?php

namespace App\Notifications;

use App\Models\SupportTicket;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class TicketRepliedNotification extends Notification
{
    use Queueable;

    public function __construct(public SupportTicket $ticket) {}

    /**
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['database'];
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'ticket.replied',
            'title' => 'Reponse support',
            'body' => "Votre ticket « {$this->ticket->subject} » a ete mis a jour ({$this->ticket->status}).",
            'url' => route('app.tickets.index'),
            'ticket_id' => $this->ticket->id,
        ];
    }
}
