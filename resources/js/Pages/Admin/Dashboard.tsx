import { Head, Link, useForm, usePage } from '@inertiajs/react';
import { FormEventHandler } from 'react';

type Props = {
    stats: Record<string, number | boolean | string>;
    maintenanceMessage: string;
    recentTenants: Array<{ id: number; name: string; plan: string; created_at: string }>;
};

export default function AdminDashboard({ stats, maintenanceMessage, recentTenants }: Props) {
    const { flash } = usePage().props as { flash?: { success?: string } };
    const { data, setData, put, processing } = useForm({
        enabled: Boolean(stats.maintenance),
        message: maintenanceMessage,
    });

    const submit: FormEventHandler = (e) => {
        e.preventDefault();
        put(route('admin.maintenance.update'));
    };

    return (
        <div className="min-h-screen bg-slate-950 text-slate-100">
            <Head title="Admin Mercuria" />
            <header className="border-b border-white/10 px-6 py-5">
                <div className="mx-auto flex max-w-6xl items-center justify-between">
                    <div>
                        <p className="text-xs uppercase tracking-[0.2em] text-emerald-300">
                            Console plateforme
                        </p>
                        <h1 className="text-xl font-semibold">Mercuria Admin</h1>
                    </div>
                    <div className="flex gap-4 text-sm">
                        <Link href="/admin/tenants" className="text-emerald-300 hover:underline">
                            Tenants
                        </Link>
                        <Link href="/admin/users" className="text-emerald-300 hover:underline">
                            Users
                        </Link>
                        <Link href="/admin/tickets" className="text-emerald-300 hover:underline">
                            Tickets
                        </Link>
                        <a href="/app/dashboard" className="text-emerald-300 hover:underline">
                            Retour app
                        </a>
                    </div>
                </div>
            </header>

            <main className="mx-auto max-w-6xl space-y-8 px-6 py-8">
                {flash?.success && (
                    <div className="rounded-xl border border-emerald-400/30 bg-emerald-400/10 px-4 py-3 text-sm text-emerald-200">
                        {flash.success}
                    </div>
                )}

                <section className="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
                    {[
                        ['Tenants', stats.tenants],
                        ['Utilisateurs', stats.users],
                        ['Abonnes Pro', stats.paid_tenants],
                        ['Documents', stats.documents],
                        ['Articles mercuriale', stats.articles],
                        ['Tickets ouverts', stats.open_tickets],
                        ['CA paye (XAF)', Number(stats.revenue_paid || 0).toLocaleString('fr-FR')],
                        ['Maintenance', stats.maintenance ? 'ON' : 'OFF'],
                    ].map(([label, value]) => (
                        <div
                            key={String(label)}
                            className="rounded-2xl border border-white/10 bg-white/5 p-4"
                        >
                            <div className="text-xs uppercase tracking-wide text-slate-400">
                                {label}
                            </div>
                            <div className="mt-2 text-2xl font-semibold">{value}</div>
                        </div>
                    ))}
                </section>

                <section className="grid gap-6 lg:grid-cols-2">
                    <div className="rounded-2xl border border-white/10 bg-white/5 p-6">
                        <h2 className="text-lg font-semibold">Mode maintenance</h2>
                        <form onSubmit={submit} className="mt-6 space-y-4">
                            <label className="flex items-center gap-3 text-sm">
                                <input
                                    type="checkbox"
                                    checked={data.enabled}
                                    onChange={(e) => setData('enabled', e.target.checked)}
                                    className="rounded border-slate-600 bg-slate-900"
                                />
                                Activer la maintenance
                            </label>
                            <textarea
                                className="w-full rounded-xl border border-white/10 bg-slate-900 px-3 py-2 text-sm text-slate-100"
                                rows={3}
                                value={data.message}
                                onChange={(e) => setData('message', e.target.value)}
                            />
                            <button
                                type="submit"
                                disabled={processing}
                                className="rounded-xl bg-emerald-500 px-4 py-2 text-sm font-semibold text-slate-950 hover:bg-emerald-400 disabled:opacity-50"
                            >
                                Enregistrer
                            </button>
                        </form>
                    </div>

                    <div className="rounded-2xl border border-white/10 bg-white/5 p-6">
                        <h2 className="text-lg font-semibold">Derniers tenants</h2>
                        <div className="mt-4 space-y-2 text-sm">
                            {recentTenants?.map((t) => (
                                <div
                                    key={t.id}
                                    className="flex justify-between border-b border-white/5 py-2"
                                >
                                    <span>{t.name}</span>
                                    <span className="text-slate-400">{t.plan}</span>
                                </div>
                            ))}
                        </div>
                    </div>
                </section>
            </main>
        </div>
    );
}
