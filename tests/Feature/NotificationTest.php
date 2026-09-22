<?php

namespace Tests\Feature;

use App\Models\SupportTicket;
use App\Models\Tenant;
use App\Models\User;
use App\Notifications\TeamMemberJoinedNotification;
use App\Notifications\TicketRepliedNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class NotificationTest extends TestCase
{
    use RefreshDatabase;

    public function test_accepting_invitation_notifies_owners(): void
    {
        Notification::fake();

        $owner = User::factory()->create();
        $tenant = Tenant::query()->create([
            'name' => 'Cabinet Notif',
            'email' => $owner->email,
            'plan' => 'pro',
            'currency' => 'XAF',
            'is_active' => true,
            'subscribed_at' => now(),
            'subscription_ends_at' => now()->addMonth(),
        ]);
        $tenant->users()->attach($owner->id, ['role' => 'owner']);
        $owner->forceFill(['current_tenant_id' => $tenant->id])->save();

        $invitation = \App\Models\TenantInvitation::createFor(
            $tenant,
            $owner,
            'invitee@example.com',
            'editor',
        );

        $this->post(route('invitations.accept', $invitation->token), [
            'name' => 'Invitee',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ])->assertRedirect(route('app.dashboard'));

        Notification::assertSentTo($owner, TeamMemberJoinedNotification::class);
    }

    public function test_admin_ticket_reply_notifies_user(): void
    {
        Notification::fake();

        $admin = User::factory()->create(['is_platform_admin' => true]);
        $user = User::factory()->create();
        $tenant = Tenant::query()->create([
            'name' => 'T',
            'plan' => 'free',
            'currency' => 'XAF',
            'is_active' => true,
        ]);
        $tenant->users()->attach($user->id, ['role' => 'owner']);
        $user->forceFill(['current_tenant_id' => $tenant->id])->save();

        $ticket = SupportTicket::query()->create([
            'tenant_id' => $tenant->id,
            'user_id' => $user->id,
            'subject' => 'Aide',
            'body' => 'Besoin',
            'status' => 'open',
            'priority' => 'normal',
        ]);

        $this->actingAs($admin)
            ->put(route('admin.tickets.update', $ticket->id), [
                'status' => 'in_progress',
                'admin_reply' => 'Voici la reponse',
            ])
            ->assertRedirect();

        Notification::assertSentTo($user, TicketRepliedNotification::class);
    }

    public function test_user_can_view_notifications_page(): void
    {
        $user = User::factory()->create();
        $tenant = Tenant::query()->create([
            'name' => 'Org',
            'plan' => 'free',
            'currency' => 'XAF',
            'is_active' => true,
        ]);
        $tenant->users()->attach($user->id, ['role' => 'owner']);
        $user->forceFill(['current_tenant_id' => $tenant->id])->save();

        $this->actingAs($user)
            ->get(route('app.notifications.index'))
            ->assertOk();
    }
}
