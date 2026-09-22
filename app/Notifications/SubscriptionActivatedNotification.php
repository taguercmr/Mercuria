<?php

namespace App\Notifications;

use App\Models\Tenant;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class SubscriptionActivatedNotification extends Notification
{
    use Queueable;

    public function __construct(
        public Tenant $tenant,
        public string $endsAtLabel,
    ) {}

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
            'type' => 'subscription.activated',
            'title' => 'Abonnement Pro active',
            'body' => "{$this->tenant->name} est maintenant en plan Pro jusqu'au {$this->endsAtLabel}.",
            'url' => route('app.subscription.show'),
            'tenant_id' => $this->tenant->id,
        ];
    }
}
