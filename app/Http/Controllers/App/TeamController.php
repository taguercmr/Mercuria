<?php

namespace App\Http\Controllers\App;

use App\Http\Controllers\Controller;
use App\Mail\TeamInvitationMail;
use App\Models\ActivityLog;
use App\Models\TenantInvitation;
use App\Models\User;
use App\Notifications\TeamMemberJoinedNotification;
use App\Services\Billing\PlanQuotaService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;
use RuntimeException;

class TeamController extends Controller
{
    public function index(Request $request, PlanQuotaService $quotas): Response
    {
        $tenant = $request->user()->currentTenant;
        abort_unless($tenant, 403);

        return Inertia::render('App/Team/Index', [
            'members' => $tenant->users()
                ->get(['users.id', 'users.name', 'users.email'])
                ->map(fn (User $u) => [
                    'id' => $u->id,
                    'name' => $u->name,
                    'email' => $u->email,
                    'role' => $u->pivot->role,
                    'is_self' => $u->id === $request->user()->id,
                ]),
            'invitations' => TenantInvitation::query()
                ->where('tenant_id', $tenant->id)
                ->whereNull('accepted_at')
                ->where('expires_at', '>', now())
                ->latest()
                ->get(['id', 'email', 'role', 'expires_at', 'created_at']),
            'usage' => $quotas->usageSummary($tenant, $request->user()),
            'canManage' => in_array($request->user()->tenantRole(), ['owner'], true)
                || $request->user()->is_platform_admin,
            'roles' => [
                'owner' => 'Proprietaire',
                'editor' => 'Editeur',
                'viewer' => 'Lecteur',
            ],
        ]);
    }

    public function invite(Request $request, PlanQuotaService $quotas): RedirectResponse
    {
        $tenant = $request->user()->currentTenant;
        abort_unless($tenant, 403);
        abort_unless($request->user()->tenantRole() === 'owner' || $request->user()->is_platform_admin, 403);

        $data = $request->validate([
            'email' => ['required', 'email', 'max:255'],
            'role' => ['required', 'in:editor,viewer'],
        ]);

        try {
            $quotas->ensureTeamSlot($tenant, $request->user());
        } catch (RuntimeException $e) {
            return back()->with('error', $e->getMessage());
        }

        $email = Str::lower($data['email']);

        if ($tenant->users()->where('email', $email)->exists()) {
            return back()->with('error', 'Cet utilisateur fait deja partie de l\'equipe.');
        }

        $invitation = TenantInvitation::createFor(
            $tenant,
            $request->user(),
            $email,
            $data['role'],
        );

        Mail::to($email)->send(new TeamInvitationMail($invitation));

        ActivityLog::record('team.invited', null, [
            'email' => $email,
            'role' => $data['role'],
        ]);

        return back()->with('success', "Invitation envoyee a {$email}.");
    }

    public function updateRole(Request $request, User $user): RedirectResponse
    {
        $tenant = $request->user()->currentTenant;
        abort_unless($tenant, 403);
        abort_unless($request->user()->tenantRole() === 'owner', 403);
        abort_if($user->id === $request->user()->id, 422, 'Vous ne pouvez pas modifier votre propre role.');

        $data = $request->validate([
            'role' => ['required', 'in:owner,editor,viewer'],
        ]);

        $tenant->users()->updateExistingPivot($user->id, ['role' => $data['role']]);

        return back()->with('success', 'Role mis a jour.');
    }

    public function remove(Request $request, User $user): RedirectResponse
    {
        $tenant = $request->user()->currentTenant;
        abort_unless($tenant, 403);
        abort_unless($request->user()->tenantRole() === 'owner', 403);
        abort_if($user->id === $request->user()->id, 422, 'Vous ne pouvez pas vous retirer vous-meme.');

        $tenant->users()->detach($user->id);

        if ($user->current_tenant_id === $tenant->id) {
            $next = $user->tenants()->first();
            $user->forceFill(['current_tenant_id' => $next?->id])->save();
        }

        return back()->with('success', 'Membre retire de l\'equipe.');
    }

    public function showAccept(string $token): Response|RedirectResponse
    {
        $invitation = TenantInvitation::query()->where('token', $token)->firstOrFail();

        if ($invitation->isExpired()) {
            return redirect()->route('landing')->with('error', 'Invitation expiree ou deja utilisee.');
        }

        return Inertia::render('Auth/AcceptInvitation', [
            'invitation' => [
                'email' => $invitation->email,
                'role' => $invitation->role,
                'tenant' => $invitation->tenant?->only(['name']),
                'token' => $invitation->token,
            ],
            'existingUser' => User::query()->where('email', $invitation->email)->exists(),
        ]);
    }

    public function accept(Request $request, string $token): RedirectResponse
    {
        $invitation = TenantInvitation::query()->where('token', $token)->firstOrFail();

        if ($invitation->isExpired()) {
            return redirect()->route('landing')->with('error', 'Invitation expiree ou deja utilisee.');
        }

        $user = User::query()->where('email', $invitation->email)->first();

        if (! $user) {
            $data = $request->validate([
                'name' => ['required', 'string', 'max:255'],
                'password' => ['required', 'confirmed', 'min:8'],
            ]);

            $user = User::query()->create([
                'name' => $data['name'],
                'email' => $invitation->email,
                'password' => Hash::make($data['password']),
                'email_verified_at' => now(),
            ]);
        } elseif (! $request->user()) {
            return redirect()->route('login')->with('error', 'Connectez-vous avec '.$invitation->email.' pour accepter.');
        } elseif ($request->user()->email !== $invitation->email) {
            return back()->with('error', 'Connectez-vous avec l\'email invite.');
        }

        if (! $invitation->tenant->users()->where('users.id', $user->id)->exists()) {
            $invitation->tenant->users()->attach($user->id, ['role' => $invitation->role]);
        }

        $user->forceFill(['current_tenant_id' => $invitation->tenant_id])->save();
        $invitation->update(['accepted_at' => now()]);

        $owners = $invitation->tenant->users()
            ->wherePivot('role', 'owner')
            ->where('users.id', '!=', $user->id)
            ->get();

        if ($owners->isNotEmpty()) {
            Notification::send(
                $owners,
                new TeamMemberJoinedNotification($invitation->tenant, $user, $invitation->role),
            );
        }

        auth()->login($user);

        return redirect()->route('app.dashboard')->with('success', 'Bienvenue dans l\'equipe '.$invitation->tenant->name.' !');
    }
}
