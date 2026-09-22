import { Head, Link, router, usePage } from '@inertiajs/react';
import { FormEvent, useState } from 'react';

type UserRow = {
    id: number;
    name: string;
    email: string;
    is_platform_admin: boolean;
    current_tenant?: { id: number; name: string } | null;
    tenants?: Array<{ id: number; name: string }>;
};

type Props = {
    users: {
        data: UserRow[];
        links: Array<{ url: string | null; label: string; active: boolean }>;
    };
    filters: { q: string };
};

export default function AdminUsers({ users, filters }: Props) {
    const { flash, auth } = usePage().props as {
        flash?: { success?: string };
        auth: { user: { id: number } | null };
    };
    const [q, setQ] = useState(filters.q || '');

    const search = (e: FormEvent) => {
        e.preventDefault();
        router.get(route('admin.users.index'), { q }, { preserveState: true });
    };

    return (
        <div className="min-h-screen bg-slate-950 text-slate-100">
            <Head title="Users — Admin" />
            <header className="border-b border-white/10 px-6 py-5">
                <div className="mx-auto flex max-w-6xl items-center justify-between">
                    <h1 className="text-xl font-semibold">Utilisateurs</h1>
                    <div className="flex gap-4 text-sm">
                        <Link href="/admin" className="text-emerald-300 hover:underline">
                            Dashboard
                        </Link>
                        <Link href="/admin/tenants" className="text-emerald-300 hover:underline">
                            Tenants
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

                <form onSubmit={search} className="flex gap-2">
                    <input
                        value={q}
                        onChange={(e) => setQ(e.target.value)}
                        className="rounded-xl border border-white/10 bg-slate-900 px-3 py-2 text-sm"
                        placeholder="Nom ou email..."
                    />
                    <button className="rounded-xl bg-emerald-500 px-4 py-2 text-sm font-semibold text-slate-950">
                        Rechercher
                    </button>
                </form>

                <div className="overflow-hidden rounded-2xl border border-white/10">
                    <table className="w-full text-left text-sm">
                        <thead className="border-b border-white/10 bg-white/5 text-xs uppercase text-slate-400">
                            <tr>
                                <th className="px-4 py-3">Utilisateur</th>
                                <th className="px-4 py-3">Tenant actif</th>
                                <th className="px-4 py-3">Admin plateforme</th>
                            </tr>
                        </thead>
                        <tbody>
                            {users.data.map((u) => (
                                <tr key={u.id} className="border-b border-white/5">
                                    <td className="px-4 py-3">
                                        <div className="font-medium">{u.name}</div>
                                        <div className="text-xs text-slate-400">{u.email}</div>
                                    </td>
                                    <td className="px-4 py-3 text-slate-300">
                                        {u.current_tenant?.name ?? '—'}
                                    </td>
                                    <td className="px-4 py-3">
                                        {auth.user?.id === u.id ? (
                                            <span className="text-xs text-slate-500">Vous</span>
                                        ) : (
                                            <button
                                                type="button"
                                                className={`rounded-full px-2 py-0.5 text-xs ${
                                                    u.is_platform_admin
                                                        ? 'bg-amber-500/20 text-amber-200'
                                                        : 'bg-white/10 text-slate-300'
                                                }`}
                                                onClick={() =>
                                                    router.put(route('admin.users.update', u.id), {
                                                        is_platform_admin: !u.is_platform_admin,
                                                    })
                                                }
                                            >
                                                {u.is_platform_admin ? 'Admin' : 'Standard'}
                                            </button>
                                        )}
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
