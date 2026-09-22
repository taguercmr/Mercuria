import AppLayout from '@/Layouts/AppLayout';
import { Head, Link, router } from '@inertiajs/react';
import { FormEvent, useState } from 'react';

type Client = {
    id: number;
    name: string;
    email: string | null;
    phone: string | null;
    company: string | null;
    city: string | null;
};

type Props = {
    clients: {
        data: Client[];
        links: Array<{ url: string | null; label: string; active: boolean }>;
    };
    filters: { q: string };
};

export default function ClientsIndex({ clients, filters }: Props) {
    const [q, setQ] = useState(filters.q || '');

    const search = (e: FormEvent) => {
        e.preventDefault();
        router.get(route('app.clients.index'), { q }, { preserveState: true });
    };

    return (
        <AppLayout title="Clients">
            <Head title="Clients" />
            <div className="mb-4 flex flex-wrap items-center justify-between gap-3">
                <form onSubmit={search} className="flex gap-2">
                    <input
                        value={q}
                        onChange={(e) => setQ(e.target.value)}
                        className="rounded-xl border border-slate-300 px-3 py-2 text-sm"
                        placeholder="Rechercher un client..."
                    />
                    <button className="rounded-xl bg-slate-900 px-4 py-2 text-sm text-white">OK</button>
                </form>
                <div className="flex flex-wrap gap-2">
                    <a
                        href={route('app.clients.export', { q })}
                        className="rounded-xl border border-slate-300 bg-white px-4 py-2 text-sm"
                    >
                        Export CSV
                    </a>
                    <Link
                        href={route('app.clients.create')}
                        className="rounded-xl bg-emerald-800 px-4 py-2 text-sm font-medium text-white"
                    >
                        Nouveau client
                    </Link>
                </div>
            </div>
            <div className="overflow-hidden rounded-2xl border border-slate-200 bg-white">
                <table className="min-w-full text-sm">
                    <thead className="bg-slate-50 text-left text-xs uppercase text-slate-500">
                        <tr>
                            <th className="px-4 py-3">Nom</th>
                            <th className="px-4 py-3">Contact</th>
                            <th className="px-4 py-3">Ville</th>
                            <th className="px-4 py-3"></th>
                        </tr>
                    </thead>
                    <tbody>
                        {clients.data.map((c) => (
                            <tr key={c.id} className="border-t border-slate-100">
                                <td className="px-4 py-3">
                                    <div className="font-medium">{c.name}</div>
                                    <div className="text-xs text-slate-500">{c.company}</div>
                                </td>
                                <td className="px-4 py-3">
                                    <div>{c.email}</div>
                                    <div className="text-xs text-slate-500">{c.phone}</div>
                                </td>
                                <td className="px-4 py-3">{c.city}</td>
                                <td className="px-4 py-3 text-right">
                                    <Link
                                        href={route('app.clients.edit', c.id)}
                                        className="text-emerald-800 hover:underline"
                                    >
                                        Modifier
                                    </Link>
                                </td>
                            </tr>
                        ))}
                    </tbody>
                </table>
            </div>
        </AppLayout>
    );
}
