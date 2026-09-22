import AppLayout from '@/Layouts/AppLayout';
import { Head, Link, router } from '@inertiajs/react';
import { FormEvent, useState } from 'react';

type Article = {
    id: number;
    reference: string;
    designation: string;
    unit: string | null;
    unit_price: number;
    rubrique?: { code: string; name: string };
};

type Props = {
    articles: {
        data: Article[];
        links: Array<{ url: string | null; label: string; active: boolean }>;
    };
    rubriques: Array<{ id: number; code: string; name: string; articles_count: number }>;
    filters: { q: string; rubrique?: string; favorites?: boolean };
    favoriteIds: number[];
    stats: { total: number; rubriques: number; favorites: number };
    searchDriver?: string;
};

function money(n: number) {
    return new Intl.NumberFormat('fr-FR').format(n) + ' XAF';
}

export default function CatalogueIndex({
    articles,
    rubriques,
    filters,
    favoriteIds,
    stats,
    searchDriver = 'sql',
}: Props) {
    const [q, setQ] = useState(filters.q || '');
    const favSet = new Set(favoriteIds);

    const search = (e: FormEvent) => {
        e.preventDefault();
        router.get(
            route('app.catalogue.index'),
            { q, rubrique: filters.rubrique, favorites: filters.favorites ? 1 : undefined },
            { preserveState: true },
        );
    };

    const toggleFavorite = (id: number, isFav: boolean) => {
        if (isFav) {
            router.delete(route('app.catalogue.unfavorite', id), { preserveScroll: true });
        } else {
            router.post(route('app.catalogue.favorite', id), {}, { preserveScroll: true });
        }
    };

    return (
        <AppLayout title="Catalogue mercuriale">
            <Head title="Catalogue mercuriale 2026" />
            <div className="space-y-6">
                <section className="rounded-2xl border border-emerald-900/10 bg-white p-5">
                    <div className="flex flex-wrap items-end justify-between gap-4">
                        <div>
                            <h2 className="text-xl font-semibold">Mercuriale MINCOMMERCE 2026</h2>
                            <p className="mt-1 text-sm text-slate-600">
                                {stats.total.toLocaleString('fr-FR')} articles · {stats.rubriques}{' '}
                                rubriques · {stats.favorites} favoris
                                <span className="ml-2 text-xs text-slate-400">
                                    recherche {searchDriver}
                                </span>
                            </p>
                        </div>
                        <form onSubmit={search} className="flex gap-2">
                            <input
                                value={q}
                                onChange={(e) => setQ(e.target.value)}
                                placeholder="Reference ou designation..."
                                className="rounded-xl border border-slate-300 px-3 py-2 text-sm"
                            />
                            <button className="rounded-xl bg-emerald-800 px-4 py-2 text-sm font-medium text-white">
                                Rechercher
                            </button>
                        </form>
                    </div>
                    <div className="mt-4 flex flex-wrap gap-2">
                        <Link
                            href={route('app.catalogue.index', { q })}
                            className={`rounded-full px-3 py-1 text-xs ${!filters.rubrique && !filters.favorites ? 'bg-emerald-800 text-white' : 'bg-slate-100 text-slate-700'}`}
                        >
                            Tous
                        </Link>
                        <Link
                            href={route('app.catalogue.index', { q, favorites: 1 })}
                            className={`rounded-full px-3 py-1 text-xs ${filters.favorites ? 'bg-amber-500 text-white' : 'bg-slate-100 text-slate-700'}`}
                        >
                            Favoris ({stats.favorites})
                        </Link>
                        {rubriques.slice(0, 12).map((r) => (
                            <Link
                                key={r.code}
                                href={route('app.catalogue.index', { q, rubrique: r.code })}
                                className={`rounded-full px-3 py-1 text-xs ${filters.rubrique === r.code ? 'bg-emerald-800 text-white' : 'bg-slate-100 text-slate-700'}`}
                            >
                                {r.code} ({r.articles_count})
                            </Link>
                        ))}
                    </div>
                </section>

                <section className="overflow-hidden rounded-2xl border border-slate-200 bg-white">
                    <table className="min-w-full text-left text-sm">
                        <thead className="bg-slate-50 text-xs uppercase tracking-wide text-slate-500">
                            <tr>
                                <th className="px-4 py-3">Reference</th>
                                <th className="px-4 py-3">Designation</th>
                                <th className="px-4 py-3">Unite</th>
                                <th className="px-4 py-3 text-right">Prix</th>
                                <th className="px-4 py-3"></th>
                            </tr>
                        </thead>
                        <tbody>
                            {articles.data.map((a) => {
                                const isFav = favSet.has(a.id);
                                return (
                                    <tr key={a.id} className="border-t border-slate-100">
                                        <td className="px-4 py-3 font-mono text-xs">{a.reference}</td>
                                        <td className="px-4 py-3">
                                            <div className="max-w-xl">{a.designation}</div>
                                            <div className="text-xs text-slate-400">
                                                Rubrique {a.rubrique?.code}
                                            </div>
                                        </td>
                                        <td className="px-4 py-3">{a.unit ?? '-'}</td>
                                        <td className="px-4 py-3 text-right font-medium">
                                            {money(a.unit_price)}
                                        </td>
                                        <td className="px-4 py-3 text-right space-x-3">
                                            <button
                                                type="button"
                                                onClick={() => toggleFavorite(a.id, isFav)}
                                                className={`text-xs font-medium ${isFav ? 'text-amber-600' : 'text-slate-500'} hover:underline`}
                                            >
                                                {isFav ? 'Retirer' : 'Favori'}
                                            </button>
                                            <Link
                                                href={route('app.documents.create', {
                                                    type: 'quote',
                                                    article: a.id,
                                                })}
                                                className="text-xs font-medium text-emerald-800 hover:underline"
                                            >
                                                Ajouter au devis
                                            </Link>
                                        </td>
                                    </tr>
                                );
                            })}
                        </tbody>
                    </table>
                    <div className="flex flex-wrap gap-2 border-t border-slate-100 p-3">
                        {articles.links.map((l, i) => (
                            <button
                                key={i}
                                disabled={!l.url}
                                onClick={() => l.url && router.get(l.url)}
                                className={`rounded px-2 py-1 text-xs ${l.active ? 'bg-emerald-800 text-white' : 'bg-slate-100'}`}
                                dangerouslySetInnerHTML={{ __html: l.label }}
                            />
                        ))}
                    </div>
                </section>
            </div>
        </AppLayout>
    );
}
