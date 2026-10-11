import { Head, Link, router, useForm, usePage } from '@inertiajs/react';
import { FormEventHandler } from 'react';

type YearRow = { year: number; articles: number; is_active: boolean };

type Props = {
    activeYear: number;
    years: YearRow[];
    defaultYear: number;
};

export default function AdminMercuriale({ activeYear, years, defaultYear }: Props) {
    const { flash } = usePage().props as { flash?: { success?: string; error?: string } };
    const form = useForm<{
        year: number;
        fresh: boolean;
        activate: boolean;
        source: File | null;
    }>({
        year: Math.max(defaultYear, activeYear + 1),
        fresh: true,
        activate: true,
        source: null,
    });

    const submit: FormEventHandler = (e) => {
        e.preventDefault();
        form.post(route('admin.mercuriale.import'), {
            forceFormData: true,
            onSuccess: () => form.setData('source', null),
        });
    };

    return (
        <div className="min-h-screen bg-slate-950 text-slate-100">
            <Head title="Mercuriales — Admin" />
            <header className="border-b border-white/10 px-6 py-5">
                <div className="mx-auto flex max-w-6xl flex-wrap items-center justify-between gap-3">
                    <div>
                        <p className="text-xs uppercase tracking-[0.2em] text-emerald-300">
                            Catalogue officiel
                        </p>
                        <h1 className="text-xl font-semibold">Mercuriales multi-années</h1>
                    </div>
                    <nav className="flex flex-wrap gap-3 text-sm">
                        <Link href="/admin" className="text-emerald-300 hover:underline">
                            Dashboard
                        </Link>
                        <Link href="/admin/payments" className="text-emerald-300 hover:underline">
                            Paiements
                        </Link>
                        <Link href="/admin/tenants" className="text-emerald-300 hover:underline">
                            Organisations
                        </Link>
                        <Link href="/admin/tickets" className="text-emerald-300 hover:underline">
                            Tickets
                        </Link>
                    </nav>
                </div>
            </header>

            <main className="mx-auto max-w-6xl space-y-6 px-6 py-8">
                {(flash?.success || flash?.error) && (
                    <div
                        className={`rounded-xl px-4 py-3 text-sm ${flash.error ? 'border border-rose-400/30 bg-rose-400/10 text-rose-100' : 'border border-emerald-400/30 bg-emerald-400/10 text-emerald-100'}`}
                    >
                        {flash.error || flash.success}
                    </div>
                )}

                <section className="rounded-2xl border border-white/10 bg-white/5 p-5">
                    <p className="text-sm text-slate-400">Année active plateforme</p>
                    <p className="mt-1 text-3xl font-semibold text-emerald-300">{activeYear}</p>
                    <p className="mt-2 max-w-2xl text-sm text-slate-400">
                        Les utilisateurs et l’API utilisent cette année par défaut. Vous pouvez
                        importer 2027+ sans écraser les années précédentes.
                    </p>
                </section>

                <div className="grid gap-6 lg:grid-cols-2">
                    <section className="rounded-2xl border border-white/10 bg-[#0c1824] p-5">
                        <h2 className="font-semibold">Importer une année</h2>
                        <p className="mt-1 text-xs text-slate-500">
                            Fichier texte extrait du PDF officiel (comme{' '}
                            <code>_mercuriale_extract.txt</code>).
                        </p>
                        <form onSubmit={submit} className="mt-4 space-y-4">
                            <div>
                                <label className="text-xs uppercase tracking-wide text-slate-500">
                                    Année budget
                                </label>
                                <input
                                    type="number"
                                    min={2020}
                                    max={2100}
                                    className="mt-1 w-full rounded-xl border border-white/10 bg-slate-900 px-3 py-2 text-sm"
                                    value={form.data.year}
                                    onChange={(e) => form.setData('year', Number(e.target.value))}
                                    required
                                />
                            </div>
                            <div>
                                <label className="text-xs uppercase tracking-wide text-slate-500">
                                    Fichier .txt
                                </label>
                                <input
                                    type="file"
                                    accept=".txt,text/plain"
                                    className="mt-1 block w-full text-sm text-slate-300"
                                    onChange={(e) =>
                                        form.setData('source', e.target.files?.[0] ?? null)
                                    }
                                    required
                                />
                            </div>
                            <label className="flex items-center gap-2 text-sm">
                                <input
                                    type="checkbox"
                                    checked={form.data.fresh}
                                    onChange={(e) => form.setData('fresh', e.target.checked)}
                                    className="rounded-sm border-slate-600 bg-slate-900 text-emerald-500"
                                />
                                Remplacer les articles de cette année uniquement
                            </label>
                            <label className="flex items-center gap-2 text-sm">
                                <input
                                    type="checkbox"
                                    checked={form.data.activate}
                                    onChange={(e) => form.setData('activate', e.target.checked)}
                                    className="rounded-sm border-slate-600 bg-slate-900 text-emerald-500"
                                />
                                Activer cette année après import
                            </label>
                            <button
                                type="submit"
                                disabled={form.processing || !form.data.source}
                                className="rounded-xl bg-emerald-400 px-4 py-2 text-sm font-semibold text-slate-950 hover:bg-emerald-300 disabled:opacity-50"
                            >
                                {form.processing ? 'Import…' : 'Lancer l’import'}
                            </button>
                        </form>
                    </section>

                    <section className="rounded-2xl border border-white/10 bg-[#0c1824] p-5">
                        <h2 className="font-semibold">Années disponibles</h2>
                        <div className="mt-4 space-y-2">
                            {years.map((row) => (
                                <div
                                    key={row.year}
                                    className="flex flex-wrap items-center justify-between gap-3 rounded-xl border border-white/10 bg-white/[0.03] px-4 py-3"
                                >
                                    <div>
                                        <div className="font-medium">
                                            {row.year}{' '}
                                            {row.is_active && (
                                                <span className="ml-2 rounded-full bg-emerald-400/20 px-2 py-0.5 text-xs text-emerald-200">
                                                    active
                                                </span>
                                            )}
                                        </div>
                                        <div className="text-xs text-slate-500">
                                            {row.articles.toLocaleString('fr-FR')} articles
                                        </div>
                                    </div>
                                    <div className="flex gap-2">
                                        {!row.is_active && row.articles > 0 && (
                                            <button
                                                type="button"
                                                className="rounded-lg border border-emerald-400/40 px-3 py-1.5 text-xs text-emerald-200 hover:bg-emerald-400/10"
                                                onClick={() =>
                                                    router.post(route('admin.mercuriale.activate'), {
                                                        year: row.year,
                                                    })
                                                }
                                            >
                                                Activer
                                            </button>
                                        )}
                                        {row.articles > 0 && (
                                            <button
                                                type="button"
                                                className="rounded-lg border border-rose-400/30 px-3 py-1.5 text-xs text-rose-200 hover:bg-rose-400/10"
                                                onClick={() => {
                                                    if (
                                                        confirm(
                                                            `Supprimer tous les articles ${row.year} ?`,
                                                        )
                                                    ) {
                                                        router.delete(
                                                            route('admin.mercuriale.destroy'),
                                                            { data: { year: row.year } },
                                                        );
                                                    }
                                                }}
                                            >
                                                Supprimer
                                            </button>
                                        )}
                                    </div>
                                </div>
                            ))}
                        </div>
                        <p className="mt-4 text-xs text-slate-500">
                            CLI :{' '}
                            <code className="rounded-sm bg-black/40 px-1">
                                php artisan mercuriale:import --year=2027 --fresh --activate
                            </code>
                        </p>
                    </section>
                </div>
            </main>
        </div>
    );
}
