import { Head, Link, router, usePage } from '@inertiajs/react';
import { FormEvent, useState } from 'react';

type TenantRow = {
    id: number;
    name: string;
    email: string | null;
    plan: string;
    is_active: boolean;
    users_count: number;
    subscription_ends_at: string | null;
    created_at: string;
};

type Props = {
    tenants: {
        data: TenantRow[];
        links: Array<{ url: string | null; label: string; active: boolean }>;
    };
    filters: { q: string; plan?: string };
    plans: string[];
};

export default function AdminTenants({ tenants, filters, plans }: Props) {
    const { flash } = usePage().props as { flash?: { success?: string } };
    const [q, setQ] = useState(filters.q || '');

    const search = (e: FormEvent) => {
        e.preventDefault();
        router.get(route('admin.tenants.index'), { q, plan: filters.plan }, { preserveState: true });
    };

    return (
        <div className="min-h-screen bg-slate-950 text-slate-100">
            <Head title="Tenants — Admin" />
            <header className="border-b border-white/10 px-6 py-5">
                <div className="mx-auto flex max-w-6xl items-center justify-between">
                    <h1 className="text-xl font-semibold">Tenants</h1>
                    <div className="flex gap-4 text-sm">
                        <Link href="/admin" className="text-emerald-300 hover:underline">
                            Dashboard
                        </Link>
                        <Link href="/admin/users" className="text-emerald-300 hover:underline">
                            Users
                        </Link>
                        <Link href="/admin/tickets" className="text-emerald-300 hover:underline">
                            Tickets
                        </Link>
                    </div>
                </div>
            </header>

            <main className="mx-auto max-w-6xl space-y-6 px-6 py-8">
                {flash?.success && (
                    <div className="rounded-xl border border-emerald-400/30 bg-emerald-400/10 px-4 py-3 text-sm text-emerald-200">
                        {flash.success}
                    </div>
                )}

                <form onSubmit={search} className="flex flex-wrap gap-2">
                    <input
                        value={q}
                        onChange={(e) => setQ(e.target.value)}
                        className="rounded-xl border border-white/10 bg-slate-900 px-3 py-2 text-sm"
                        placeholder="Rechercher..."
                    />
                    <select
                        className="rounded-xl border border-white/10 bg-slate-900 px-3 py-2 text-sm"
                        value={filters.plan || ''}
                        onChange={(e) =>
                            router.get(route('admin.tenants.index'), {
                                q,
                                plan: e.target.value || undefined,
                            })
                        }
                    >
                        <option value="">Tous les plans</option>
                        {plans.map((p) => (
                            <option key={p} value={p}>
                                {p}
                            </option>
                        ))}
                    </select>
                    <button className="rounded-xl bg-emerald-500 px-4 py-2 text-sm font-semibold text-slate-950">
                        Filtrer
                    </button>
                </form>

                <div className="overflow-hidden rounded-2xl border border-white/10">
                    <table className="w-full text-left text-sm">
                        <thead className="border-b border-white/10 bg-white/5 text-xs uppercase text-slate-400">
                            <tr>
                                <th className="px-4 py-3">Organisation</th>
                                <th className="px-4 py-3">Plan</th>
                                <th className="px-4 py-3">Users</th>
                                <th className="px-4 py-3">Actif</th>
                                <th className="px-4 py-3">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            {tenants.data.map((t) => (
                                <tr key={t.id} className="border-b border-white/5">
                                    <td className="px-4 py-3">
                                        <div className="font-medium">{t.name}</div>
                                        <div className="text-xs text-slate-400">{t.email}</div>
                                    </td>
                                    <td className="px-4 py-3">
                                        <select
                                            className="rounded-lg border border-white/10 bg-slate-900 text-sm"
                                            value={t.plan}
                                            onChange={(e) =>
                                                router.put(route('admin.tenants.update', t.id), {
                                                    plan: e.target.value,
                                                })
                                            }
                                        >
                                            {plans.map((p) => (
                                                <option key={p} value={p}>
                                                    {p}
                                                </option>
                                            ))}
                                        </select>
                                    </td>
                                    <td className="px-4 py-3">{t.users_count}</td>
                                    <td className="px-4 py-3">
                                        <button
                                            type="button"
                                            className={`rounded-full px-2 py-0.5 text-xs ${
                                                t.is_active
                                                    ? 'bg-emerald-500/20 text-emerald-300'
                                                    : 'bg-rose-500/20 text-rose-300'
                                            }`}
                                            onClick={() =>
                                                router.put(route('admin.tenants.update', t.id), {
                                                    is_active: !t.is_active,
                                                })
                                            }
                                        >
                                            {t.is_active ? 'Actif' : 'Suspendu'}
                                        </button>
                                    </td>
                                    <td className="px-4 py-3 text-xs text-slate-400">
                                        {new Date(t.created_at).toLocaleDateString('fr-FR')}
                                    </td>
                                </tr>
                            ))}
                        </tbody>
                    </table>
                </div>
            </main>
        </div>
    );
}
