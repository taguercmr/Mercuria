import AppLayout from '@/Layouts/AppLayout';
import { Head, Link, router } from '@inertiajs/react';

type Doc = {
    id: number;
    type: string;
    number: string | null;
    status: string;
    total: number;
    currency: string;
    created_at: string;
    client?: { name: string } | null;
};

type Props = {
    documents: { data: Doc[] };
    filters: { type?: string; status?: string };
    types: Record<string, string>;
};

function money(n: number, currency = 'XAF') {
    return `${new Intl.NumberFormat('fr-FR').format(n)} ${currency}`;
}

export default function DocumentsIndex({ documents, filters, types }: Props) {
    return (
        <AppLayout title="Documents">
            <Head title="Documents" />
            <div className="mb-4 flex flex-wrap items-center justify-between gap-3">
                <div className="flex flex-wrap gap-2">
                    <Link
                        href={route('app.documents.index')}
                        className={`rounded-full px-3 py-1 text-xs ${!filters.type ? 'bg-emerald-800 text-white' : 'bg-slate-100'}`}
                    >
                        Tous
                    </Link>
                    {Object.entries(types).map(([key, label]) => (
                        <Link
                            key={key}
                            href={route('app.documents.index', { type: key })}
                            className={`rounded-full px-3 py-1 text-xs ${filters.type === key ? 'bg-emerald-800 text-white' : 'bg-slate-100'}`}
                        >
                            {label}
                        </Link>
                    ))}
                </div>
                <div className="flex flex-wrap gap-2">
                    <a
                        href={route('app.documents.export', { type: filters.type, status: filters.status })}
                        className="rounded-xl border border-slate-300 bg-white px-4 py-2 text-sm"
                    >
                        Export CSV
                    </a>
                    <Link
                        href={route('app.documents.create', { type: 'quote' })}
                        className="rounded-xl bg-emerald-800 px-4 py-2 text-sm font-medium text-white"
                    >
                        Nouveau devis
                    </Link>
                </div>
            </div>

            <div className="overflow-hidden rounded-2xl border border-slate-200 bg-white">
                <table className="min-w-full text-sm">
                    <thead className="bg-slate-50 text-left text-xs uppercase text-slate-500">
                        <tr>
                            <th className="px-4 py-3">Numero</th>
                            <th className="px-4 py-3">Type</th>
                            <th className="px-4 py-3">Client</th>
                            <th className="px-4 py-3">Statut</th>
                            <th className="px-4 py-3 text-right">Total</th>
                        </tr>
                    </thead>
                    <tbody>
                        {documents.data.map((d) => (
                            <tr
                                key={d.id}
                                className="cursor-pointer border-t border-slate-100 hover:bg-slate-50"
                                onClick={() => router.get(route('app.documents.show', d.id))}
                            >
                                <td className="px-4 py-3 font-medium">{d.number ?? 'Brouillon'}</td>
                                <td className="px-4 py-3">{types[d.type] ?? d.type}</td>
                                <td className="px-4 py-3">{d.client?.name ?? '-'}</td>
                                <td className="px-4 py-3">
                                    <span className="rounded-full bg-slate-100 px-2 py-0.5 text-xs">
                                        {d.status}
                                    </span>
                                </td>
                                <td className="px-4 py-3 text-right">{money(d.total, d.currency)}</td>
                            </tr>
                        ))}
                    </tbody>
                </table>
            </div>
        </AppLayout>
    );
}
