import { Head, Link, useForm, usePage, usePoll } from '@inertiajs/react';
import { FormEventHandler, ReactNode, useState } from 'react';

type Point = { day: string; label: string; total: number };
type Slice = { key: string; label: string; total: number };
type HealthItem = { key: string; label: string; status: 'ok' | 'warn' | 'alert'; detail: string };

type Props = {
    generatedAt: string;
    stats: {
        tenants: number;
        tenants_week: number;
        users: number;
        users_week: number;
        paid_tenants: number;
        free_tenants: number;
        documents: number;
        documents_week: number;
        documents_prev_week: number;
        articles: number;
        open_tickets: number;
        pending_jobs: number;
        failed_jobs: number;
        revenue_pending: number;
        revenue_paid: number;
        api_requests: number;
        active_keys: number;
        maintenance: boolean;
    };
    series: { documents: Point[]; tenants: Point[]; users: Point[] };
    breakdowns: { plans: Slice[]; documents: Slice[] };
    health: HealthItem[];
    maintenanceMessage: string;
    recentTenants: Array<{
        id: number;
        name: string;
        plan: string;
        is_active: boolean;
        users_count: number;
        created_at: string | null;
    }>;
    recentTickets: Array<{
        id: number;
        subject: string;
        status: string;
        priority: string | null;
        author: string | null;
        created_at: string | null;
    }>;
    recentPayments: Array<{
        id: number;
        tenant: string | null;
        amount: number;
        status: string;
        provider: string | null;
        created_at: string | null;
    }>;
    activity: Array<{
        id: number;
        action: string;
        user: string | null;
        tenant: string | null;
        created_at: string | null;
    }>;
    api: { active_keys: number; requests: number; last_used_at: string | null };
};

const money = (n: number) => `${new Intl.NumberFormat('fr-FR').format(n)} XAF`;

function when(value: string | null) {
    if (!value) return '—';
    return new Intl.DateTimeFormat('fr-FR', {
        day: '2-digit',
        month: 'short',
        hour: '2-digit',
        minute: '2-digit',
    }).format(new Date(value));
}

const statusTone: Record<HealthItem['status'], string> = {
    ok: 'bg-emerald-400 shadow-[0_0_12px_rgba(52,211,153,0.7)]',
    warn: 'bg-amber-400 shadow-[0_0_12px_rgba(251,191,36,0.7)]',
    alert: 'bg-rose-500 shadow-[0_0_12px_rgba(244,63,94,0.75)]',
};

export default function AdminDashboard(props: Props) {
    const { stats, series, breakdowns, health, recentTenants, recentTickets, recentPayments, activity, api } =
        props;
    const { flash } = usePage().props as { flash?: { success?: string } };
    const [live, setLive] = useState(true);
    const { start, stop } = usePoll(15000, {}, { autoStart: true, keepAlive: true });

    const { data, setData, put, processing } = useForm({
        enabled: Boolean(stats.maintenance),
        message: props.maintenanceMessage,
    });

    const submit: FormEventHandler = (e) => {
        e.preventDefault();
        put(route('admin.maintenance.update'));
    };

    const alerts = health.filter((item) => item.status !== 'ok');

    return (
        <div className="min-h-screen bg-[#061018] text-slate-100">
            <Head title="Console admin" />
            <style>{`
                @keyframes rise {
                    from { transform: scaleY(0); opacity: 0.4; }
                    to { transform: scaleY(1); opacity: 1; }
                }
                @keyframes sweep {
                    from { stroke-dashoffset: 280; }
                    to { stroke-dashoffset: 0; }
                }
                @keyframes fade-up {
                    from { opacity: 0; transform: translateY(8px); }
                    to { opacity: 1; transform: translateY(0); }
                }
            `}</style>

            <header className="sticky top-0 z-20 border-b border-white/10 bg-[#061018]/90 backdrop-blur-sm">
                <div className="mx-auto flex max-w-7xl flex-wrap items-center justify-between gap-4 px-5 py-4">
                    <div>
                        <p className="text-[11px] font-semibold uppercase tracking-[0.22em] text-emerald-300">
                            Console plateforme
                        </p>
                        <h1 className="text-xl font-semibold tracking-tight">Mercuria Admin</h1>
                    </div>
                    <nav className="flex flex-wrap items-center gap-2 text-sm">
                        {[
                            ['/admin/payments', 'Paiements'],
                            ['/admin/mercuriale', 'Mercuriales'],
                            ['/admin/tenants', 'Organisations'],
                            ['/admin/users', 'Utilisateurs'],
                            ['/admin/tickets', 'Tickets'],
                        ].map(([href, label]) => (
                            <Link
                                key={href}
                                href={href}
                                className="rounded-full border border-white/10 bg-white/5 px-3 py-1.5 text-slate-200 hover:border-emerald-400/40 hover:text-white"
                            >
                                {label}
                            </Link>
                        ))}
                        <a
                            href="/app/dashboard"
                            className="rounded-full px-3 py-1.5 text-emerald-300 hover:underline"
                        >
                            Retour app
                        </a>
                        <button
                            type="button"
                            onClick={() => {
                                if (live) {
                                    stop();
                                    setLive(false);
                                } else {
                                    start();
                                    setLive(true);
                                }
                            }}
                            className="inline-flex items-center gap-2 rounded-full border border-white/10 bg-white/5 px-3 py-1.5"
                        >
                            <span
                                className={`h-2 w-2 rounded-full ${live ? 'animate-pulse bg-emerald-400' : 'bg-slate-500'}`}
                            />
                            {live ? 'Live 15s' : 'Pause'}
                        </button>
                    </nav>
                </div>
            </header>

            <main className="mx-auto max-w-7xl space-y-6 px-5 py-6">
                {flash?.success && (
                    <div className="rounded-xl border border-emerald-400/30 bg-emerald-400/10 px-4 py-3 text-sm text-emerald-100">
                        {flash.success}
                    </div>
                )}

                {alerts.length > 0 && (
                    <div className="flex flex-wrap gap-2">
                        {alerts.map((item) => (
                            <span
                                key={item.key}
                                className="rounded-full border border-amber-300/30 bg-amber-400/10 px-3 py-1 text-xs text-amber-100"
                            >
                                {item.label} — {item.detail}
                            </span>
                        ))}
                    </div>
                )}

                <section className="grid gap-3 sm:grid-cols-2 xl:grid-cols-4">
                    <Kpi label="Organisations" value={stats.tenants} hint={`+${stats.tenants_week} / 7 j`} />
                    <Kpi label="Utilisateurs" value={stats.users} hint={`+${stats.users_week} / 7 j`} />
                    <Kpi
                        label="Documents"
                        value={stats.documents}
                        hint={`${stats.documents_week} cette semaine · ${stats.documents_prev_week} la précédente`}
                    />
                    <Kpi label="CA encaissé" value={money(stats.revenue_paid)} hint={`${money(stats.revenue_pending)} en attente`} />
                </section>

                <section className="grid gap-4 xl:grid-cols-3">
                    <Panel className="xl:col-span-2" title="Activité — 14 jours" subtitle="Documents créés, organisations et comptes">
                        <div className="grid gap-6 lg:grid-cols-3">
                            <Bars title="Documents" points={series.documents} color="#34d399" />
                            <Bars title="Organisations" points={series.tenants} color="#38bdf8" />
                            <Bars title="Utilisateurs" points={series.users} color="#a78bfa" />
                        </div>
                    </Panel>
                    <Panel title="Répartition" subtitle="Plans et types de documents">
                        <Donut
                            title="Plans"
                            slices={breakdowns.plans}
                            colors={['#64748b', '#34d399']}
                        />
                        <div className="mt-5">
                            <Donut
                                title="Documents"
                                slices={breakdowns.documents}
                                colors={['#38bdf8', '#fbbf24', '#a78bfa', '#fb7185']}
                            />
                        </div>
                    </Panel>
                </section>

                <section className="grid gap-4 lg:grid-cols-3">
                    <Panel title="Monitoring" subtitle={`Actualisé ${when(props.generatedAt)}`}>
                        <ul className="space-y-3">
                            {health.map((item, index) => (
                                <li
                                    key={item.key}
                                    className="flex items-start gap-3"
                                    style={{ animation: `fade-up 500ms ${index * 60}ms both` }}
                                >
                                    <span className={`mt-1.5 h-2.5 w-2.5 shrink-0 rounded-full ${statusTone[item.status]}`} />
                                    <div>
                                        <div className="text-sm font-medium">{item.label}</div>
                                        <div className="text-xs text-slate-400">{item.detail}</div>
                                    </div>
                                </li>
                            ))}
                        </ul>
                    </Panel>

                    <Panel title="File & API" subtitle="Charge technique">
                        <dl className="grid grid-cols-2 gap-3 text-sm">
                            <Metric label="Jobs en attente" value={stats.pending_jobs} />
                            <Metric label="Jobs en échec" value={stats.failed_jobs} warn={stats.failed_jobs > 0} />
                            <Metric label="Clés API actives" value={api.active_keys} />
                            <Metric label="Requêtes API" value={new Intl.NumberFormat('fr-FR').format(api.requests)} />
                            <Metric label="Articles mercuriale" value={new Intl.NumberFormat('fr-FR').format(stats.articles)} />
                            <Metric label="Dernier appel API" value={when(api.last_used_at)} />
                        </dl>
                    </Panel>

                    <Panel title="Mode maintenance" subtitle="Bloque l’accès public, pas les admins">
                        <form onSubmit={submit} className="space-y-3">
                            <label className="flex items-center gap-3 text-sm">
                                <input
                                    type="checkbox"
                                    checked={data.enabled}
                                    onChange={(e) => setData('enabled', e.target.checked)}
                                    className="rounded-sm border-slate-600 bg-slate-900 text-emerald-500"
                                />
                                Activer la maintenance
                            </label>
                            <textarea
                                className="w-full rounded-xl border border-white/10 bg-[#0b1722] px-3 py-2 text-sm text-slate-100"
                                rows={3}
                                value={data.message}
                                onChange={(e) => setData('message', e.target.value)}
                            />
                            <button
                                type="submit"
                                disabled={processing}
                                className="rounded-xl bg-emerald-400 px-4 py-2 text-sm font-semibold text-slate-950 hover:bg-emerald-300 disabled:opacity-50"
                            >
                                Enregistrer
                            </button>
                        </form>
                    </Panel>
                </section>

                <section className="grid gap-4 xl:grid-cols-2">
                    <Panel title="Dernières organisations" action={{ href: '/admin/tenants', label: 'Tout voir' }}>
                        <table className="w-full text-left text-sm">
                            <thead className="text-xs uppercase tracking-wide text-slate-500">
                                <tr>
                                    <th className="pb-2 font-medium">Nom</th>
                                    <th className="pb-2 font-medium">Plan</th>
                                    <th className="pb-2 font-medium">Membres</th>
                                    <th className="pb-2 font-medium">Créée</th>
                                </tr>
                            </thead>
                            <tbody className="divide-y divide-white/5">
                                {recentTenants.length === 0 && (
                                    <tr>
                                        <td colSpan={4} className="py-4 text-slate-500">
                                            Aucune organisation.
                                        </td>
                                    </tr>
                                )}
                                {recentTenants.map((tenant) => (
                                    <tr key={tenant.id}>
                                        <td className="py-2.5">
                                            <div className="font-medium">{tenant.name}</div>
                                            <div className="text-xs text-slate-500">
                                                {tenant.is_active ? 'active' : 'inactive'}
                                            </div>
                                        </td>
                                        <td className="py-2.5 uppercase text-emerald-300">{tenant.plan}</td>
                                        <td className="py-2.5">{tenant.users_count}</td>
                                        <td className="py-2.5 text-slate-400">{when(tenant.created_at)}</td>
                                    </tr>
                                ))}
                            </tbody>
                        </table>
                    </Panel>

                    <Panel title="Tickets récents" action={{ href: '/admin/tickets', label: 'Traiter' }}>
                        <ul className="divide-y divide-white/5 text-sm">
                            {recentTickets.length === 0 && (
                                <li className="py-3 text-slate-500">Aucun ticket.</li>
                            )}
                            {recentTickets.map((ticket) => (
                                <li key={ticket.id} className="flex items-start justify-between gap-3 py-3">
                                    <div>
                                        <div className="font-medium">{ticket.subject}</div>
                                        <div className="text-xs text-slate-500">
                                            {ticket.author ?? '—'} · {when(ticket.created_at)}
                                        </div>
                                    </div>
                                    <span className="rounded-full bg-white/10 px-2 py-0.5 text-xs uppercase">
                                        {ticket.status}
                                    </span>
                                </li>
                            ))}
                        </ul>
                    </Panel>

                    <Panel title="Paiements récents">
                        <ul className="divide-y divide-white/5 text-sm">
                            {recentPayments.length === 0 && (
                                <li className="py-3 text-slate-500">Aucun paiement.</li>
                            )}
                            {recentPayments.map((payment) => (
                                <li key={payment.id} className="flex items-center justify-between gap-3 py-3">
                                    <div>
                                        <div className="font-medium">{payment.tenant ?? 'Organisation'}</div>
                                        <div className="text-xs text-slate-500">
                                            {payment.provider ?? '—'} · {when(payment.created_at)}
                                        </div>
                                    </div>
                                    <div className="text-right">
                                        <div>{money(payment.amount)}</div>
                                        <div className="text-xs uppercase text-slate-400">{payment.status}</div>
                                    </div>
                                </li>
                            ))}
                        </ul>
                    </Panel>

                    <Panel title="Journal d’activité">
                        <ul className="divide-y divide-white/5 text-sm">
                            {activity.length === 0 && (
                                <li className="py-3 text-slate-500">Aucune activité enregistrée.</li>
                            )}
                            {activity.map((entry) => (
                                <li key={entry.id} className="flex items-start justify-between gap-3 py-3">
                                    <div>
                                        <div className="font-medium">{entry.action}</div>
                                        <div className="text-xs text-slate-500">
                                            {entry.user ?? 'Système'}
                                            {entry.tenant ? ` · ${entry.tenant}` : ''}
                                        </div>
                                    </div>
                                    <span className="shrink-0 text-xs text-slate-500">{when(entry.created_at)}</span>
                                </li>
                            ))}
                        </ul>
                    </Panel>
                </section>
            </main>
        </div>
    );
}

function Kpi({ label, value, hint }: { label: string; value: string | number; hint: string }) {
    return (
        <article
            className="rounded-2xl border border-white/10 bg-gradient-to-br from-white/[0.07] to-white/[0.02] p-4"
            style={{ animation: 'fade-up 500ms both' }}
        >
            <div className="text-[11px] font-semibold uppercase tracking-[0.16em] text-slate-400">{label}</div>
            <div className="mt-2 text-2xl font-semibold tracking-tight">{value}</div>
            <div className="mt-1 text-xs text-emerald-200/80">{hint}</div>
        </article>
    );
}

function Panel({
    title,
    subtitle,
    action,
    className = '',
    children,
}: {
    title: string;
    subtitle?: string;
    action?: { href: string; label: string };
    className?: string;
    children: ReactNode;
}) {
    return (
        <section className={`rounded-2xl border border-white/10 bg-[#0c1824] p-5 ${className}`}>
            <div className="mb-4 flex items-start justify-between gap-3">
                <div>
                    <h2 className="font-semibold">{title}</h2>
                    {subtitle && <p className="mt-0.5 text-xs text-slate-500">{subtitle}</p>}
                </div>
                {action && (
                    <Link href={action.href} className="text-xs text-emerald-300 hover:underline">
                        {action.label}
                    </Link>
                )}
            </div>
            {children}
        </section>
    );
}

function Metric({ label, value, warn = false }: { label: string; value: string | number; warn?: boolean }) {
    return (
        <div className="rounded-xl border border-white/5 bg-white/[0.03] p-3">
            <dt className="text-[11px] uppercase tracking-wide text-slate-500">{label}</dt>
            <dd className={`mt-1 text-lg font-semibold ${warn ? 'text-rose-300' : ''}`}>{value}</dd>
        </div>
    );
}

function Bars({ title, points, color }: { title: string; points: Point[]; color: string }) {
    const max = Math.max(1, ...points.map((point) => point.total));
    const total = points.reduce((sum, point) => sum + point.total, 0);

    return (
        <div>
            <div className="mb-2 flex items-baseline justify-between">
                <h3 className="text-sm font-medium">{title}</h3>
                <span className="text-xs text-slate-400">{total}</span>
            </div>
            <div className="flex h-36 items-end gap-1">
                {points.map((point, index) => (
                    <div key={point.day} className="group flex h-full flex-1 items-end" title={`${point.label} : ${point.total}`}>
                        <div
                            className="w-full origin-bottom rounded-t-sm"
                            style={{
                                height: `${Math.max(point.total > 0 ? 6 : 0, (point.total / max) * 100)}%`,
                                background: color,
                                animation: `rise 700ms ${index * 35}ms both`,
                            }}
                        />
                    </div>
                ))}
            </div>
            <div className="mt-2 flex justify-between text-[10px] text-slate-500">
                <span>{points[0]?.label}</span>
                <span>{points[points.length - 1]?.label}</span>
            </div>
        </div>
    );
}

function Donut({ title, slices, colors }: { title: string; slices: Slice[]; colors: string[] }) {
    const total = slices.reduce((sum, slice) => sum + slice.total, 0);
    const radius = 36;
    const circumference = 2 * Math.PI * radius;
    let cursor = 0;

    return (
        <div className="flex items-center gap-4">
            <svg viewBox="0 0 100 100" className="h-24 w-24 shrink-0 -rotate-90">
                <circle cx="50" cy="50" r={radius} fill="none" stroke="rgba(255,255,255,0.06)" strokeWidth="12" />
                {total > 0 &&
                    slices.map((slice, index) => {
                        const length = (slice.total / total) * circumference;
                        const circle = (
                            <circle
                                key={slice.key}
                                cx="50"
                                cy="50"
                                r={radius}
                                fill="none"
                                stroke={colors[index % colors.length]}
                                strokeWidth="12"
                                strokeDasharray={`${length} ${circumference - length}`}
                                strokeDashoffset={-cursor}
                                strokeLinecap="butt"
                                style={{ animation: 'sweep 900ms ease both' }}
                            />
                        );
                        cursor += length;
                        return circle;
                    })}
            </svg>
            <div className="min-w-0">
                <div className="text-xs uppercase tracking-wide text-slate-500">{title}</div>
                <ul className="mt-1 space-y-1 text-xs">
                    {slices.map((slice, index) => (
                        <li key={slice.key} className="flex items-center gap-2 text-slate-300">
                            <span
                                className="h-2 w-2 rounded-full"
                                style={{ background: colors[index % colors.length] }}
                            />
                            <span className="truncate">{slice.label}</span>
                            <span className="ml-auto text-slate-400">{slice.total}</span>
                        </li>
                    ))}
                </ul>
            </div>
        </div>
    );
}
