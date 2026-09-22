import { Head, Link, useForm } from '@inertiajs/react';

type Ticket = {
    id: number;
    subject: string;
    body: string;
    status: string;
    priority: string;
    admin_reply?: string | null;
    user?: { name: string; email: string };
};

export default function AdminTickets({ tickets }: { tickets: { data: Ticket[] } }) {
    return (
        <div className="min-h-screen bg-slate-950 text-slate-100">
            <Head title="Tickets Admin" />
            <header className="border-b border-white/10 px-6 py-5">
                <div className="mx-auto flex max-w-5xl items-center justify-between">
                    <h1 className="text-xl font-semibold">Tickets support</h1>
                    <Link href="/admin" className="text-sm text-emerald-300 hover:underline">
                        Dashboard
                    </Link>
                </div>
            </header>
            <main className="mx-auto max-w-5xl space-y-4 px-6 py-8">
                {tickets.data.map((t) => (
                    <TicketCard key={t.id} ticket={t} />
                ))}
            </main>
        </div>
    );
}

function TicketCard({ ticket }: { ticket: Ticket }) {
    const { data, setData, put, processing } = useForm({
        status: ticket.status,
        admin_reply: ticket.admin_reply ?? '',
    });

    return (
        <div className="rounded-2xl border border-white/10 bg-white/5 p-5">
            <div className="flex items-start justify-between gap-3">
                <div>
                    <h2 className="font-semibold">{ticket.subject}</h2>
                    <p className="text-xs text-slate-400">
                        {ticket.user?.name} · {ticket.user?.email} · {ticket.priority}
                    </p>
                </div>
                <span className="rounded-full bg-white/10 px-2 py-0.5 text-xs">{ticket.status}</span>
            </div>
            <p className="mt-3 text-sm text-slate-300">{ticket.body}</p>
            <form
                className="mt-4 space-y-3"
                onSubmit={(e) => {
                    e.preventDefault();
                    put(route('admin.tickets.update', ticket.id));
                }}
            >
                <select
                    className="w-full rounded-xl border border-white/10 bg-slate-900 text-sm"
                    value={data.status}
                    onChange={(e) => setData('status', e.target.value)}
                >
                    <option value="open">open</option>
                    <option value="in_progress">in_progress</option>
                    <option value="resolved">resolved</option>
                    <option value="closed">closed</option>
                </select>
                <textarea
                    className="w-full rounded-xl border border-white/10 bg-slate-900 px-3 py-2 text-sm"
                    rows={3}
                    placeholder="Reponse admin"
                    value={data.admin_reply}
                    onChange={(e) => setData('admin_reply', e.target.value)}
                />
                <button
                    disabled={processing}
                    className="rounded-xl bg-emerald-500 px-4 py-2 text-sm font-semibold text-slate-950"
                >
                    Mettre a jour
                </button>
            </form>
        </div>
    );
}
