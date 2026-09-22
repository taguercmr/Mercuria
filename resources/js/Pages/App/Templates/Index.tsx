import AppLayout from '@/Layouts/AppLayout';
import { Head, Link, router } from '@inertiajs/react';

type Template = {
    id: number;
    name: string;
    type: string;
    title?: string;
    lines: any[];
    created_at: string;
};

export default function TemplatesIndex({
    templates,
    types,
}: {
    templates: Template[];
    types: Record<string, string>;
}) {
    return (
        <AppLayout title="Modeles">
            <Head title="Modeles de documents" />
            <p className="mb-4 text-sm text-slate-600">
                Reutilisez un modele pour creer un devis en un clic. Sauvegardez un modele depuis
                la fiche d'un document.
            </p>
            <div className="grid gap-4 md:grid-cols-2">
                {templates.length === 0 && (
                    <div className="rounded-2xl border border-dashed border-slate-300 p-8 text-sm text-slate-500">
                        Aucun modele pour le moment.
                    </div>
                )}
                {templates.map((t) => (
                    <div
                        key={t.id}
                        className="rounded-2xl border border-slate-200 bg-white p-5"
                    >
                        <div className="flex items-start justify-between gap-3">
                            <div>
                                <h3 className="font-semibold">{t.name}</h3>
                                <p className="text-xs text-slate-500">
                                    {types[t.type] ?? t.type} · {t.lines?.length ?? 0} lignes
                                </p>
                            </div>
                            <button
                                type="button"
                                className="text-xs text-red-600"
                                onClick={() =>
                                    router.delete(route('app.templates.destroy', t.id))
                                }
                            >
                                Supprimer
                            </button>
                        </div>
                        <Link
                            href={route('app.documents.create', { template: t.id })}
                            className="mt-4 inline-block rounded-xl bg-emerald-800 px-4 py-2 text-sm text-white"
                        >
                            Utiliser ce modele
                        </Link>
                    </div>
                ))}
            </div>
        </AppLayout>
    );
}
