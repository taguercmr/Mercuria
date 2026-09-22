import AppLayout from '@/Layouts/AppLayout';
import { Head, useForm, usePage } from '@inertiajs/react';
import { FormEventHandler } from 'react';

type Props = {
    usage: { api_access: boolean; plan_name: string };
    apiKeys: Array<{
        id: number;
        name: string;
        key_prefix: string;
        requests_count: number;
        is_active: boolean;
        last_used_at: string | null;
    }>;
    plainKey?: string | null;
    docs: { base_url: string; endpoints: string[] };
};

export default function DevelopersIndex({ usage, apiKeys, plainKey, docs }: Props) {
    const { flash } = usePage().props as { flash?: { success?: string; error?: string } };
    const createForm = useForm({ name: 'Cle production' });
    const revokeForm = useForm({});

    const createKey: FormEventHandler = (e) => {
        e.preventDefault();
        createForm.post(route('app.developers.keys.store'));
    };

    return (
        <AppLayout title="Espace developpeurs">
            <Head title="API Mercuriale" />
            {(flash?.success || flash?.error) && (
                <div
                    className={`mb-4 rounded-xl px-4 py-3 text-sm ${flash.error ? 'bg-red-50 text-red-700' : 'bg-emerald-50 text-emerald-800'}`}
                >
                    {flash.error || flash.success}
                </div>
            )}

            {!usage.api_access && (
                <div className="mb-4 rounded-xl border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-900">
                    L'API publique est reservee au plan Pro. Passez Pro dans Abonnement.
                </div>
            )}

            {plainKey && (
                <div className="mb-4 rounded-xl border border-emerald-300 bg-emerald-50 px-4 py-3 text-sm">
                    <strong>Nouvelle cle (a copier maintenant) :</strong>
                    <code className="mt-2 block break-all rounded bg-white p-2">{plainKey}</code>
                </div>
            )}

            <div className="grid gap-6 lg:grid-cols-2">
                <section className="rounded-2xl border border-slate-200 bg-white p-6">
                    <h2 className="font-semibold">Creer une cle API</h2>
                    <form onSubmit={createKey} className="mt-3 flex gap-2">
                        <input
                            className="flex-1 rounded-xl border-slate-300 text-sm"
                            value={createForm.data.name}
                            onChange={(e) => createForm.setData('name', e.target.value)}
                        />
                        <button
                            disabled={!usage.api_access || createForm.processing}
                            className="rounded-xl bg-emerald-800 px-4 py-2 text-sm text-white disabled:opacity-50"
                        >
                            Generer
                        </button>
                    </form>

                    <div className="mt-6 space-y-2">
                        {apiKeys.map((k) => (
                            <div
                                key={k.id}
                                className="flex items-center justify-between rounded-xl border border-slate-100 px-3 py-2 text-sm"
                            >
                                <div>
                                    <div className="font-medium">
                                        {k.name}{' '}
                                        <span className="text-xs text-slate-400">
                                            {k.key_prefix}…
                                        </span>
                                    </div>
                                    <div className="text-xs text-slate-500">
                                        {k.requests_count} req · {k.is_active ? 'active' : 'revoquee'}
                                    </div>
                                </div>
                                {k.is_active && (
                                    <button
                                        type="button"
                                        className="text-xs text-red-600"
                                        onClick={() =>
                                            revokeForm.delete(route('app.developers.keys.revoke', k.id))
                                        }
                                    >
                                        Revoquer
                                    </button>
                                )}
                            </div>
                        ))}
                    </div>
                </section>

                <section className="rounded-2xl border border-slate-200 bg-white p-6">
                    <h2 className="font-semibold">Documentation rapide</h2>
                    <p className="mt-2 text-sm text-slate-600">Base URL : {docs.base_url}</p>
                    <p className="mt-1 text-sm text-slate-600">
                        Auth : header <code>Authorization: Bearer mrc_...</code> ou{' '}
                        <code>X-Api-Key</code>
                    </p>
                    <ul className="mt-4 space-y-2 font-mono text-xs text-slate-700">
                        {docs.endpoints.map((e) => (
                            <li key={e}>{e}</li>
                        ))}
                    </ul>
                </section>
            </div>
        </AppLayout>
    );
}
