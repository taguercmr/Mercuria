import AppLayout from '@/Layouts/AppLayout';
import { Head, Link } from '@inertiajs/react';
import { ReactNode } from 'react';

type Point = { day: string; label: string; total: number };
type Slice = { key: string; label: string; total: number };

type Props = {
    tenant: { id: number; name: string; plan: string; currency: string } | null;
    usage?: {
        documents_used: number;
        documents_limit: number | null;
        plan_name: string;
    } | null;
    onboarding?: { branding: boolean; client: boolean; document: boolean } | null;
    mercurialeYear: number;
    stats: {
        documents_month: number;
        documents_total: number;
        clients: number;
        drafts: number;
        revenue_month: number;
        currency: string;
    };
    series: { documents: Point[]; revenue: Point[] };
    breakdowns: { types: Slice[]; statuses: Slice[] };
    recentDocuments: Array<{
        id: number;
        type: string;
        number: string | null;
        status: string;
        total: number;
        currency: string;
        client?: { name: string } | null;
    }>;
    topClients: Array<{
        id: number;
        name: string;
        documents_count: number;
        revenue: number;
    }>;
    quickActions: Array<{ label: string; href: string; hint: string }>;
};

const money = (n: number, currency = 'XAF') =>
    `${new Intl.NumberFormat('fr-FR').format(n)} ${currency}`;

export default function Dashboard({
    tenant,
    usage,
    onboarding,
    mercurialeYear,
    stats,
    series,
    breakdowns,
    recentDocuments,
    topClients,
    quickActions,
}: Props) {
    return (
        <AppLayout title="Tableau de bord">
            <Head title="Tableau de bord" />
            <style>{`
                @keyframes rise {
                    from { transform: scaleY(0); opacity: 0.35; }
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

            <div className="space-y-6">
                <section className="rounded-2xl border border-emerald-900/10 bg-gradient-to-br from-white to-emerald-50/70 p-6">
                    <p className="text-sm font-medium text-[#0b6e4f]">Bienvenue</p>
                    <h2 className="mt-1 text-2xl font-semibold tracking-tight">
                        {tenant?.name ?? 'Votre espace Mercuria'}
                    </h2>
                    <p className="mt-2 max-w-2xl text-sm text-slate-600">
                        Mercuriale {mercurialeYear}, devis, clients et API — votre activité en un
                        coup d&apos;œil.
                    </p>
                    {usage && (
                        <p className="mt-3 text-xs text-slate-500">
                            Plan {usage.plan_name} · {usage.documents_used}
                            {usage.documents_limit !== null
                                ? ` / ${usage.documents_limit}`
                                : ' illimités'}{' '}
                            documents ce mois
                        </p>
                    )}
                </section>

                {onboarding && (
                    <section className="rounded-2xl border border-amber-200 bg-amber-50 p-5">
                        <div className="flex flex-wrap items-center justify-between gap-3">
                            <div>
                                <h3 className="font-semibold text-amber-950">Démarrage guidé</h3>
                                <p className="text-sm text-amber-900/80">
                                    Compléter branding, client et premier devis pour débloquer tout
                                    le potentiel.
                                </p>
                            </div>
                            <Link
                                href={route('app.onboarding.guide')}
                                className="rounded-xl bg-amber-700 px-4 py-2 text-sm text-white"
                            >
                                Continuer
                            </Link>
                        </div>
                    </section>
                )}

                <section className="grid gap-3 sm:grid-cols-2 xl:grid-cols-4">
                    <Kpi
                        label="Documents ce mois"
                        value={stats.documents_month}
                        hint={`${stats.documents_total} au total`}
                    />
                    <Kpi
                        label="CA documents"
                        value={money(stats.revenue_month, stats.currency)}
                        hint="Émis / envoyés ce mois"
                    />
                    <Kpi label="Clients" value={stats.clients} hint="Carnet actif" />
                    <Kpi
                        label="Brouillons"
                        value={stats.drafts}
                        hint="À finaliser"
                        tone={stats.drafts > 0 ? 'warn' : 'default'}
                    />
                </section>

                <section>
                    <h3 className="mb-3 text-sm font-semibold uppercase tracking-wide text-slate-500">
                        Actions indispensables
                    </h3>
                    <div className="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
                        {quickActions.map((action) => (
                            <Link
                                key={action.href}
                                href={action.href}
                                className="rounded-2xl border border-slate-200 bg-white p-4 transition hover:border-[#0b6e4f]/40 hover:shadow-xs"
                            >
                                <div className="font-medium text-slate-900">{action.label}</div>
                                <div className="mt-1 text-sm text-slate-500">{action.hint}</div>
                            </Link>
                        ))}
                    </div>
                </section>

                <section className="grid gap-4 xl:grid-cols-3">
                    <Panel
                        className="xl:col-span-2"
                        title="Activité — 14 jours"
                        subtitle="Volume de documents créés"
                    >
                        <Bars points={series.documents} color="#0b6e4f" />
                    </Panel>
                    <Panel title="Types de documents" subtitle="Répartition du catalogue">
                        <Donut
                            slices={breakdowns.types}
                            colors={['#0b6e4f', '#0ea5e9', '#f59e0b', '#8b5cf6']}
                        />
                    </Panel>
                </section>

                <section className="grid gap-4 xl:grid-cols-3">
                    <Panel
                        className="xl:col-span-2"
                        title="Montants — 14 jours"
                        subtitle={`Totaux documents (${stats.currency})`}
                    >
                        <Bars
                            points={series.revenue}
                            color="#0284c7"
                            formatValue={(n) => money(n, stats.currency)}
                        />
                    </Panel>
                    <Panel title="Statuts" subtitle="Documents en cours">
                        <Donut
                            slices={
                                breakdowns.statuses.length
                                    ? breakdowns.statuses
                                    : [{ key: 'empty', label: 'Aucun', total: 0 }]
                            }
                            colors={['#94a3b8', '#0b6e4f', '#38bdf8', '#a78bfa', '#f43f5e']}
                        />
                    </Panel>
                </section>

                <section className="grid gap-4 lg:grid-cols-2">
                    <Panel
                        title="Documents récents"
                        action={{ href: route('app.documents.index'), label: 'Voir tout' }}
                    >
                        <div className="space-y-2 text-sm">
                            {recentDocuments.length === 0 && (
                                <p className="text-slate-500">Aucun document pour le moment.</p>
                            )}
                            {recentDocuments.map((d) => (
                                <Link
                                    key={d.id}
                                    href={route('app.documents.show', d.id)}
                                    className="flex items-center justify-between rounded-xl border border-slate-100 px-3 py-2 hover:bg-slate-50"
                                >
                                    <span>
                                        {d.number ?? 'Brouillon'} · {d.client?.name ?? 'Sans client'}
                                    </span>
                                    <span className="text-slate-500">
                                        {money(Number(d.total), d.currency)}
                                    </span>
                                </Link>
                            ))}
                        </div>
                    </Panel>

                    <Panel
                        title="Top clients du mois"
                        action={{ href: route('app.clients.index'), label: 'Clients' }}
                    >
                        <div className="space-y-2 text-sm">
                            {topClients.length === 0 && (
                                <p className="text-slate-500">
                                    Pas encore assez d&apos;activité ce mois.
                                </p>
                            )}
                            {topClients.map((client, index) => (
                                <div
                                    key={client.id}
                                    className="flex items-center justify-between rounded-xl border border-slate-100 px-3 py-2"
                                    style={{ animation: `fade-up 420ms ${index * 50}ms both` }}
                                >
                                    <div>
                                        <div className="font-medium">{client.name}</div>
                                        <div className="text-xs text-slate-500">
                                            {client.documents_count} document
                                            {client.documents_count > 1 ? 's' : ''}
                                        </div>
                                    </div>
                                    <div className="font-medium text-emerald-800">
                                        {money(client.revenue, stats.currency)}
                                    </div>
                                </div>
                            ))}
                        </div>
                    </Panel>
                </section>
            </div>
        </AppLayout>
    );
}

function Kpi({
    label,
    value,
    hint,
    tone = 'default',
}: {
    label: string;
    value: string | number;
    hint: string;
    tone?: 'default' | 'warn';
}) {
    return (
        <article
            className="rounded-2xl border border-slate-200 bg-white p-4"
            style={{ animation: 'fade-up 450ms both' }}
        >
            <div className="text-[11px] font-semibold uppercase tracking-[0.14em] text-slate-400">
                {label}
            </div>
            <div
                className={`mt-2 text-2xl font-semibold tracking-tight ${tone === 'warn' ? 'text-amber-700' : 'text-slate-900'}`}
            >
                {value}
            </div>
            <div className="mt-1 text-xs text-slate-500">{hint}</div>
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
        <section className={`rounded-2xl border border-slate-200 bg-white p-5 ${className}`}>
            <div className="mb-4 flex items-start justify-between gap-3">
                <div>
                    <h3 className="font-semibold text-slate-900">{title}</h3>
                    {subtitle && <p className="mt-0.5 text-xs text-slate-500">{subtitle}</p>}
                </div>
                {action && (
                    <Link href={action.href} className="text-sm text-emerald-800 hover:underline">
                        {action.label}
                    </Link>
                )}
            </div>
            {children}
        </section>
    );
}

function Bars({
    points,
    color,
    formatValue,
}: {
    points: Point[];
    color: string;
    formatValue?: (n: number) => string;
}) {
    const max = Math.max(1, ...points.map((point) => point.total));
    const total = points.reduce((sum, point) => sum + point.total, 0);

    return (
        <div>
            <div className="mb-2 flex items-baseline justify-between text-xs text-slate-500">
                <span>
                    Total période :{' '}
                    <strong className="text-slate-800">
                        {formatValue ? formatValue(total) : total}
                    </strong>
                </span>
            </div>
            <div className="flex h-40 items-end gap-1.5">
                {points.map((point, index) => (
                    <div
                        key={point.day}
                        className="group relative flex h-full flex-1 items-end"
                        title={`${point.label} : ${formatValue ? formatValue(point.total) : point.total}`}
                    >
                        <div
                            className="w-full origin-bottom rounded-t-md"
                            style={{
                                height: `${Math.max(point.total > 0 ? 8 : 0, (point.total / max) * 100)}%`,
                                background: color,
                                animation: `rise 700ms ${index * 30}ms both`,
                            }}
                        />
                    </div>
                ))}
            </div>
            <div className="mt-2 flex justify-between text-[10px] text-slate-400">
                <span>{points[0]?.label}</span>
                <span>{points[points.length - 1]?.label}</span>
            </div>
        </div>
    );
}

function Donut({ slices, colors }: { slices: Slice[]; colors: string[] }) {
    const total = slices.reduce((sum, slice) => sum + slice.total, 0);
    const radius = 36;
    const circumference = 2 * Math.PI * radius;
    let cursor = 0;

    return (
        <div className="flex items-center gap-4">
            <svg viewBox="0 0 100 100" className="h-28 w-28 shrink-0 -rotate-90">
                <circle
                    cx="50"
                    cy="50"
                    r={radius}
                    fill="none"
                    stroke="#e2e8f0"
                    strokeWidth="12"
                />
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
                                style={{ animation: 'sweep 900ms ease both' }}
                            />
                        );
                        cursor += length;
                        return circle;
                    })}
            </svg>
            <ul className="min-w-0 space-y-1.5 text-xs">
                {slices.map((slice, index) => (
                    <li key={slice.key} className="flex items-center gap-2 text-slate-600">
                        <span
                            className="h-2.5 w-2.5 rounded-full"
                            style={{ background: colors[index % colors.length] }}
                        />
                        <span className="truncate">{slice.label}</span>
                        <span className="ml-auto font-medium text-slate-800">{slice.total}</span>
                    </li>
                ))}
            </ul>
        </div>
    );
}
