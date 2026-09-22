import AppLayout from '@/Layouts/AppLayout';
import { Head, Link, router } from '@inertiajs/react';

type Step = {
    key: string;
    title: string;
    done: boolean;
    href: string;
    hint: string;
};

type Props = {
    steps: Step[];
    completed: number;
    tenant: { name: string; plan: string };
    usage: { documents_used: number; documents_limit: number | null };
};

export default function OnboardingGuide({ steps, completed, tenant, usage }: Props) {
    return (
        <AppLayout title="Bienvenue">
            <Head title="Demarrage guide" />
            <div className="mx-auto max-w-3xl space-y-6">
                <section className="rounded-2xl border border-emerald-900/10 bg-gradient-to-br from-white to-emerald-50 p-6">
                    <p className="text-sm font-medium text-emerald-800">Bienvenue sur Mercuria</p>
                    <h2 className="mt-1 text-2xl font-semibold">{tenant.name}</h2>
                    <p className="mt-2 text-sm text-slate-600">
                        3 etapes pour etre operationnel. Progresssion : {completed}/3
                    </p>
                    <div className="mt-3 h-2 overflow-hidden rounded-full bg-emerald-100">
                        <div
                            className="h-full bg-emerald-700 transition-all"
                            style={{ width: `${(completed / 3) * 100}%` }}
                        />
                    </div>
                </section>

                <div className="space-y-3">
                    {steps.map((step) => (
                        <a
                            key={step.key}
                            href={step.href}
                            className={`block rounded-2xl border p-5 ${step.done ? 'border-emerald-200 bg-emerald-50' : 'border-slate-200 bg-white hover:border-emerald-300'}`}
                        >
                            <div className="flex items-center justify-between gap-3">
                                <h3 className="font-semibold">{step.title}</h3>
                                <span
                                    className={`rounded-full px-2 py-0.5 text-xs ${step.done ? 'bg-emerald-700 text-white' : 'bg-slate-100 text-slate-600'}`}
                                >
                                    {step.done ? 'Fait' : 'A faire'}
                                </span>
                            </div>
                            <p className="mt-1 text-sm text-slate-600">{step.hint}</p>
                        </a>
                    ))}
                </div>

                <div className="flex flex-wrap gap-3">
                    <Link
                        href={route('app.dashboard')}
                        className="rounded-xl bg-emerald-800 px-4 py-2 text-sm text-white"
                    >
                        Aller au tableau de bord
                    </Link>
                    <button
                        type="button"
                        onClick={() => router.post(route('app.onboarding.skip'))}
                        className="rounded-xl px-4 py-2 text-sm text-slate-600 hover:underline"
                    >
                        Passer pour l'instant
                    </button>
                </div>

                <p className="text-xs text-slate-500">
                    Plan {tenant.plan} · {usage.documents_used}
                    {usage.documents_limit !== null ? `/${usage.documents_limit}` : ''} documents ce
                    mois
                </p>
            </div>
        </AppLayout>
    );
}
