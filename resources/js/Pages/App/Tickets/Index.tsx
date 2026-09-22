import AppLayout from '@/Layouts/AppLayout';
import { Head, useForm, usePage } from '@inertiajs/react';
import { FormEventHandler } from 'react';

type Ticket = {
    id: number;
    subject: string;
    body: string;
    status: string;
    priority: string;
    admin_reply?: string | null;
    created_at: string;
};

export default function TicketsIndex({ tickets }: { tickets: { data: Ticket[] } }) {
    const { flash } = usePage().props as { flash?: { success?: string } };
    const { data, setData, post, processing, reset } = useForm({
        subject: '',
        body: '',
        priority: 'normal',
    });

    const submit: FormEventHandler = (e) => {
        e.preventDefault();
        post(route('app.tickets.store'), { onSuccess: () => reset() });
    };

    return (
        <AppLayout title="Support">
            <Head title="Tickets" />
            {flash?.success && (
                <div className="mb-4 rounded-xl bg-emerald-50 px-4 py-3 text-sm text-emerald-800">
                    {flash.success}
                </div>
            )}

            <form onSubmit={submit} className="mb-6 space-y-3 rounded-2xl border border-slate-200 bg-white p-5">
                <h2 className="font-semibold">Nouveau ticket</h2>
                <input
                    className="w-full rounded-xl border-slate-300 text-sm"
                    placeholder="Sujet"
                    value={data.subject}
                    onChange={(e) => setData('subject', e.target.value)}
                    required
                />
                <textarea
                    className="w-full rounded-xl border-slate-300 text-sm"
                    rows={4}
                    placeholder="Decrivez votre besoin..."
                    value={data.body}
                    onChange={(e) => setData('body', e.target.value)}
                    required
                />
                <select
                    className="rounded-xl border-slate-300 text-sm"
                    value={data.priority}
                    onChange={(e) => setData('priority', e.target.value)}
                >
                    <option value="low">Basse</option>
                    <option value="normal">Normale</option>
                    <option value="high">Haute</option>
                </select>
                <button
                    disabled={processing}
                    className="rounded-xl bg-emerald-800 px-4 py-2 text-sm text-white"
                >
                    Envoyer
                </button>
            </form>

            <div className="space-y-3">
                {tickets.data.map((t) => (
                    <div key={t.id} className="rounded-2xl border border-slate-200 bg-white p-4">
                        <div className="flex items-center justify-between gap-3">
                            <h3 className="font-medium">{t.subject}</h3>
                            <span className="rounded-full bg-slate-100 px-2 py-0.5 text-xs">{t.status}</span>
                        </div>
                        <p className="mt-2 text-sm text-slate-600">{t.body}</p>
                        {t.admin_reply && (
                            <p className="mt-3 rounded-xl bg-emerald-50 p-3 text-sm text-emerald-900">
                                Reponse : {t.admin_reply}
                            </p>
                        )}
                    </div>
                ))}
            </div>
        </AppLayout>
    );
}
