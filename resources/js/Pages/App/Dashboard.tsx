import AppLayout from '@/Layouts/AppLayout';
import { Head, Link } from '@inertiajs/react';

type Props = {
    tenant: { id: number; name: string; plan: string; currency: string } | null;
    usage?: {
        documents_used: number;
        documents_limit: number | null;
        plan_name: string;
    } | null;
    onboarding?: { branding: boolean; client: boolean; document: boolean } | null;
    recentDocuments: Array<{
        id: number;
        type: string;
        number: string | null;
        status: string;
        total: number;
        currency: string;
        client?: { name: string } | null;
    }>;
    quickActions: Array<{ label: string; href: string; hint: string }>;
};

export default function Dashboard({
    tenant,
    usage,
    onboarding,
    recentDocuments,
    quickActions,
}: Props) {
    return (
        <AppLayout title="Tableau de bord">
            <Head title="Tableau de bord" />
            <div className="space-y-8">
                <section className="rounded-2xl border border-emerald-900/10 bg-gradient-to-br from-white to-emerald-50/60 p-6">
                    <p className="text-sm font-medium text-[#0b6e4f]">Bienvenue</p>
                    <h2 className="mt-1 text-2xl font-semibold tracking-tight">
                        {tenant?.name ?? 'Votre espace Mercuria'}
                    </h2>
                    <p className="mt-2 max-w-2xl text-sm text-slate-600">
                        Mercuriale 2026, devis, clients et API — une plateforme a votre service.
                    </p>
                    {usage && (
                        <p className="mt-3 text-xs text-slate-500">
                            Plan {usage.plan_name} · {usage.documents_used}
                            {usage.documents_limit !== null
                                ? ` / ${usage.documents_limit}`
                                : ' illimites'}{' '}
                            documents ce mois
                        </p>
                    )}
                </section>

                {onboarding && (
                    <section className="rounded-2xl border border-amber-200 bg-amber-50 p-5">
                        <div className="flex flex-wrap items-center justify-between gap-3">
                            <div>
                                <h3 className="font-semibold text-amber-950">Demarrage guide</h3>
                                <p className="text-sm text-amber-900/80">
                                    Completer branding, client et premier devis pour debloquer tout
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

                <section>
                    <h3 className="mb-3 text-sm font-semibold uppercase tracking-wide text-slate-500">
                        Actions indispensables
                    </h3>
                    <div className="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
                        {quickActions.map((action) => (
                            <Link
                                key={action.href}
                                href={action.href}
                                className="rounded-2xl border border-slate-200 bg-white p-4 transition hover:border-[#0b6e4f]/40 hover:shadow-sm"
                            >
                                <div className="font-medium text-slate-900">{action.label}</div>
                                <div className="mt-1 text-sm text-slate-500">{action.hint}</div>
                            </Link>
                        ))}
                    </div>
                </section>

                <section className="rounded-2xl border border-slate-200 bg-white p-5">
                    <div className="mb-3 flex items-center justify-between">
                        <h3 className="font-semibold">Documents recents</h3>
                        <Link
                            href={route('app.documents.index')}
                            className="text-sm text-emerald-800 hover:underline"
                        >
                            Voir tout
                        </Link>
                    </div>
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
                                    {Number(d.total).toLocaleString('fr-FR')} {d.currency}
                                </span>
                            </Link>
                        ))}
                    </div>
                </section>
            </div>
        </AppLayout>
    );
}
