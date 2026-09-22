<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\SupportTicket;
use App\Notifications\TicketRepliedNotification;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class TicketController extends Controller
{
    public function index(): Response
    {
        return Inertia::render('Admin/Tickets', [
            'tickets' => SupportTicket::withoutGlobalScopes()
                ->with(['user:id,name,email'])
                ->latest()
                ->paginate(30),
        ]);
    }

    public function update(Request $request, int $ticket): RedirectResponse
    {
        $model = SupportTicket::withoutGlobalScopes()->findOrFail($ticket);

        $data = $request->validate([
            'status' => ['required', 'in:open,in_progress,resolved,closed'],
            'admin_reply' => ['nullable', 'string', 'max:5000'],
        ]);

        $hadReply = filled($data['admin_reply'] ?? null)
            && ($data['admin_reply'] !== $model->admin_reply);

        $model->update([
            'status' => $data['status'],
            'admin_reply' => $data['admin_reply'] ?? $model->admin_reply,
            'resolved_at' => in_array($data['status'], ['resolved', 'closed'], true) ? now() : null,
        ]);

        if (($hadReply || $model->wasChanged('status')) && $model->user) {
            $model->user->notify(new TicketRepliedNotification($model->fresh()));
        }

        return back()->with('success', 'Ticket mis a jour.');
    }
}
