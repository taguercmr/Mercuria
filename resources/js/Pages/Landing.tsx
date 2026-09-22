import { Head, Link } from '@inertiajs/react';

type Plan = {
    code: string;
    name: string;
    documents_per_month: number | null;
    clients_limit: number | null;
    api_access: boolean;
    price_monthly_xaf?: number;
};

type Props = {
    canLogin: boolean;
    canRegister: boolean;
    mercurialeYear: number;
    source: string;
    plans: Record<string, Plan>;
};

export default function Landing({
    canLogin,
    canRegister,
    mercurialeYear,
    source,
    plans,
}: Props) {
    const free = plans.free;
    const pro = plans.pro;

    return (
        <>
            <Head title="Mercuria — Devis & mercuriale MINCOMMERCE" />
            <div className="min-h-screen bg-[#f4f7f5] text-slate-900">
                <header className="mx-auto flex max-w-6xl items-center justify-between px-6 py-6">
                    <div className="text-2xl font-semibold tracking-tight text-emerald-800">
                        Mercuria
                    </div>
                    <nav className="flex items-center gap-3 text-sm">
                        {canLogin && (
                            <Link
                                href={route('login')}
                                className="rounded-lg px-3 py-2 text-slate-700 hover:bg-white"
                            >
                                Connexion
                            </Link>
                        )}
                        {canRegister && (
                            <Link
                                href={route('register')}
                                className="rounded-lg bg-emerald-800 px-4 py-2 font-medium text-white hover:bg-emerald-900"
                            >
                                Essayer gratuitement
                            </Link>
                        )}
                    </nav>
                </header>

                <main>
                    <section className="relative overflow-hidden border-b border-emerald-900/10 bg-[#eef6f2]">
                        <div className="relative mx-auto grid max-w-6xl gap-10 px-6 pb-20 pt-10 lg:grid-cols-2 lg:items-end">
                            <div>
                                <p className="mb-4 text-sm font-medium uppercase tracking-widest text-emerald-800">
                                    Traguer AI — Cameroun
                                </p>
                                <h1 className="max-w-2xl text-4xl font-semibold leading-tight tracking-tight text-slate-900 sm:text-5xl">
                                    Mercuria
                                </h1>
                                <p className="mt-4 max-w-xl text-lg leading-relaxed text-slate-600">
                                    Creez devis, proformas, bons de commande et factures a partir
                                    de la mercuriale officielle {mercurialeYear}. Simple, intuitif,
                                    pense pour votre quotidien.
                                </p>
                                <div className="mt-8 flex flex-wrap gap-3">
                                    <Link
                                        href={route('register')}
                                        className="rounded-xl bg-emerald-800 px-5 py-3 text-sm font-semibold text-white shadow-sm hover:bg-emerald-900"
                                    >
                                        Demarrer gratuitement
                                    </Link>
                                    <a
                                        href="#features"
                                        className="rounded-xl border border-slate-300 bg-white/70 px-5 py-3 text-sm font-medium text-slate-700 hover:bg-white"
                                    >
                                        Voir les modules
                                    </a>
                                </div>
                                <p className="mt-6 text-xs text-slate-500">{source}</p>
                            </div>
                            <div className="rounded-3xl border border-white/60 bg-white/80 p-6 shadow-xl backdrop-blur">
                                <p className="text-sm font-medium text-emerald-800">
                                    Une plateforme a votre service
                                </p>
                                <ul className="mt-4 space-y-3 text-sm text-slate-700">
                                    <li>49 137 articles mercuriale searchable</li>
                                    <li>PDF + envoi email en un clic</li>
                                    <li>Conversion devis → proforma → BC → facture</li>
                                    <li>API publique pour les abonnes Pro</li>
                                    <li>Modeles reutilisables & favoris</li>
                                </ul>
                            </div>
                        </div>
                    </section>

                    <section id="features" className="mx-auto max-w-6xl px-6 py-16">
                        <h2 className="text-2xl font-semibold tracking-tight">
                            Tout ce qu'il faut — rien de superflu
                        </h2>
                        <p className="mt-2 max-w-2xl text-slate-600">
                            Chaque module revele une utilite concrete des la premiere exploration.
                        </p>
                        <div className="mt-10 grid gap-6 md:grid-cols-3">
                            {[
                                {
                                    title: 'Documents',
                                    text: 'Devis, proforma, bon de commande, facture — PDF et email inclus.',
                                },
                                {
                                    title: 'Mercuriale 2026',
                                    text: 'Prix officiels MINCOMMERCE, favoris, recherche instantanee.',
                                },
                                {
                                    title: 'Personnalisation',
                                    text: 'Logo, en-tete, pied de page, signature, devise, modeles.',
                                },
                            ].map((item) => (
                                <div
                                    key={item.title}
                                    className="rounded-2xl border border-slate-200 bg-white p-5"
                                >
                                    <h3 className="font-semibold text-slate-900">{item.title}</h3>
                                    <p className="mt-2 text-sm leading-relaxed text-slate-600">
                                        {item.text}
                                    </p>
                                </div>
                            ))}
                        </div>
                    </section>

                    <section className="border-t border-slate-200 bg-white">
                        <div className="mx-auto grid max-w-6xl gap-6 px-6 py-16 md:grid-cols-2">
                            <div className="rounded-2xl border border-slate-200 p-6">
                                <h3 className="text-lg font-semibold">{free?.name ?? 'Gratuit'}</h3>
                                <p className="mt-2 text-3xl font-semibold">0 XAF</p>
                                <ul className="mt-4 space-y-2 text-sm text-slate-600">
                                    <li>{free?.documents_per_month ?? 10} documents / mois</li>
                                    <li>Jusqu'a {free?.clients_limit ?? 25} clients</li>
                                    <li>Branding de base</li>
                                </ul>
                            </div>
                            <div className="rounded-2xl border border-emerald-700/40 bg-emerald-50 p-6">
                                <h3 className="text-lg font-semibold">{pro?.name ?? 'Pro'}</h3>
                                <p className="mt-2 text-3xl font-semibold">
                                    {pro?.price_monthly_xaf?.toLocaleString('fr-FR') ?? '15 000'}{' '}
                                    XAF
                                    <span className="text-base font-normal text-slate-500">
                                        {' '}
                                        / mois
                                    </span>
                                </p>
                                <ul className="mt-4 space-y-2 text-sm text-slate-600">
                                    <li>Documents illimites</li>
                                    <li>API mercuriale developpeurs</li>
                                    <li>Equipe multi-utilisateurs</li>
                                    <li>Personnalisation complete</li>
                                </ul>
                            </div>
                        </div>
                    </section>
                </main>

                <footer className="border-t border-slate-200 px-6 py-8 text-center text-sm text-slate-500">
                    © {new Date().getFullYear()} Mercuria — Traguer AI —{' '}
                    <a className="text-emerald-800 hover:underline" href="https://traguer.com">
                        traguer.com
                    </a>
                </footer>
            </div>
        </>
    );
}
