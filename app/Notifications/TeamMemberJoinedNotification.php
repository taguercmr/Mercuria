<?php

namespace App\Notifications;

use App\Models\Tenant;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class TeamMemberJoinedNotification extends Notification
{
    use Queueable;

    public function __construct(
        public Tenant $tenant,
        public User $member,
        public string $role,
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
            'type' => 'team.member_joined',
            'title' => 'Nouveau membre',
            'body' => "{$this->member->name} a rejoint {$this->tenant->name} ({$this->role}).",
            'url' => route('app.team.index'),
            'tenant_id' => $this->tenant->id,
            'member_id' => $this->member->id,
        ];
    }
}
