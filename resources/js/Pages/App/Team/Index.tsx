import AppLayout from '@/Layouts/AppLayout';
import { Head, router, useForm, usePage } from '@inertiajs/react';
import { FormEventHandler } from 'react';

type Member = {
    id: number;
    name: string;
    email: string;
    role: string;
    is_self: boolean;
};

type Invitation = {
    id: number;
    email: string;
    role: string;
    expires_at: string;
    created_at: string;
};

type Usage = {
    team_members: number;
    team_limit: number | null;
    plan_name: string;
};

export default function TeamIndex({
    members,
    invitations,
    usage,
    canManage,
    roles,
}: {
    members: Member[];
    invitations: Invitation[];
    usage: Usage;
    canManage: boolean;
    roles: Record<string, string>;
}) {
    const { flash } = usePage().props as {
        flash?: { success?: string; error?: string };
    };

    const { data, setData, post, processing, reset, errors } = useForm({
        email: '',
        role: 'editor',
    });

    const submit: FormEventHandler = (e) => {
        e.preventDefault();
        post(route('app.team.invite'), {
            onSuccess: () => reset('email'),
        });
    };

    return (
        <AppLayout title="Equipe">
            <Head title="Equipe" />

            {(flash?.success || flash?.error) && (
                <div
                    className={`mb-4 rounded-xl px-4 py-3 text-sm ${
                        flash.error
                            ? 'bg-rose-50 text-rose-800'
                            : 'bg-emerald-50 text-emerald-800'
                    }`}
                >
                    {flash.error ?? flash.success}
                </div>
            )}

            <div className="mb-6 flex flex-wrap items-end justify-between gap-3">
                <p className="text-sm text-slate-600">
                    {usage.team_members}
                    {usage.team_limit != null ? ` / ${usage.team_limit}` : ''} membres — plan{' '}
                    {usage.plan_name}
                </p>
            </div>

            {canManage && (
                <form
                    onSubmit={submit}
                    className="mb-6 space-y-3 rounded-2xl border border-slate-200 bg-white p-5"
                >
                    <h2 className="font-semibold">Inviter un membre</h2>
                    <div className="flex flex-col gap-3 sm:flex-row">
                        <input
                            type="email"
                            className="flex-1 rounded-xl border-slate-300 text-sm"
                            placeholder="email@entreprise.cm"
                            value={data.email}
                            onChange={(e) => setData('email', e.target.value)}
                            required
                        />
                        <select
                            className="rounded-xl border-slate-300 text-sm"
                            value={data.role}
                            onChange={(e) => setData('role', e.target.value)}
                        >
                            <option value="editor">{roles.editor}</option>
                            <option value="viewer">{roles.viewer}</option>
                        </select>
                        <button
                            disabled={processing}
                            className="rounded-xl bg-emerald-800 px-4 py-2 text-sm text-white disabled:opacity-60"
                        >
                            Inviter
                        </button>
                    </div>
                    {errors.email && (
                        <p className="text-sm text-rose-600">{errors.email}</p>
                    )}
                </form>
            )}

            <div className="mb-8 overflow-hidden rounded-2xl border border-slate-200 bg-white">
                <table className="w-full text-left text-sm">
                    <thead className="border-b border-slate-100 bg-slate-50 text-xs uppercase text-slate-500">
                        <tr>
                            <th className="px-4 py-3">Membre</th>
                            <th className="px-4 py-3">Role</th>
                            {canManage && <th className="px-4 py-3 text-right">Actions</th>}
                        </tr>
                    </thead>
                    <tbody>
                        {members.map((m) => (
                            <tr key={m.id} className="border-b border-slate-50 last:border-0">
                                <td className="px-4 py-3">
                                    <div className="font-medium">{m.name}</div>
                                    <div className="text-slate-500">{m.email}</div>
                                </td>
                                <td className="px-4 py-3">
                                    {canManage && !m.is_self ? (
                                        <select
                                            className="rounded-lg border-slate-300 text-sm"
                                            value={m.role}
                                            onChange={(e) =>
                                                router.put(route('app.team.role', m.id), {
                                                    role: e.target.value,
                                                })
                                            }
                                        >
                                            {Object.entries(roles).map(([value, label]) => (
                                                <option key={value} value={value}>
                                                    {label}
                                                </option>
                                            ))}
                                        </select>
                                    ) : (
                                        <span className="rounded-full bg-emerald-50 px-2 py-0.5 text-xs text-emerald-800">
                                            {roles[m.role] ?? m.role}
                                        </span>
                                    )}
                                </td>
                                {canManage && (
                                    <td className="px-4 py-3 text-right">
                                        {!m.is_self && (
                                            <button
                                                type="button"
                                                className="text-sm text-rose-600 hover:underline"
                                                onClick={() => {
                                                    if (
                                                        confirm(
                                                            `Retirer ${m.name} de l'equipe ?`,
                                                        )
                                                    ) {
                                                        router.delete(
                                                            route('app.team.remove', m.id),
                                                        );
                                                    }
                                                }}
                                            >
                                                Retirer
                                            </button>
                                        )}
                                    </td>
                                )}
                            </tr>
                        ))}
                    </tbody>
                </table>
            </div>

            {invitations.length > 0 && (
                <div>
                    <h2 className="mb-3 font-semibold">Invitations en attente</h2>
                    <ul className="space-y-2">
                        {invitations.map((inv) => (
                            <li
                                key={inv.id}
                                className="flex items-center justify-between rounded-xl border border-slate-200 bg-white px-4 py-3 text-sm"
                            >
                                <span>
                                    {inv.email}{' '}
                                    <span className="text-slate-500">
                                        ({roles[inv.role] ?? inv.role})
                                    </span>
                                </span>
                                <span className="text-xs text-slate-400">
                                    expire {new Date(inv.expires_at).toLocaleDateString('fr-FR')}
                                </span>
                            </li>
                        ))}
                    </ul>
                </div>
            )}
        </AppLayout>
    );
}
