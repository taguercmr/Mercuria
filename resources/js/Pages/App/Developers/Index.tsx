import AppLayout from '@/Layouts/AppLayout';
import { Head, useForm, usePage } from '@inertiajs/react';
import { FormEventHandler } from 'react';

type DocParam = {
    name: string;
    type: string;
    required: boolean;
    description: string;
};

type DocEndpoint = {
    method: string;
    path: string;
    summary: string;
    params: DocParam[];
    example_request: string;
    example_response: unknown;
};

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
    docs: {
        base_url: string;
        rate_limit: string;
        plan: string;
        auth_headers: string[];
        auth_query: string;
        endpoints: DocEndpoint[];
        errors: Array<{ code: number; meaning: string }>;
    };
};

function CodeBlock({ children }: { children: string }) {
    return (
        <pre className="mt-2 overflow-x-auto rounded-xl bg-slate-900 p-3 text-[11px] leading-relaxed text-emerald-100">
            {children}
        </pre>
    );
}

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
            <Head title="API Mercuriale — Documentation" />
            {(flash?.success || flash?.error) && (
                <div
                    className={`mb-4 rounded-xl px-4 py-3 text-sm ${flash.error ? 'bg-red-50 text-red-700' : 'bg-emerald-50 text-emerald-800'}`}
                >
                    {flash.error || flash.success}
                </div>
            )}

            {!usage.api_access && (
                <div className="mb-4 rounded-xl border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-900">
                    L&apos;API publique est réservée au plan Pro. Passez Pro dans Abonnement.
                </div>
            )}

            {plainKey && (
                <div className="mb-4 rounded-xl border border-emerald-300 bg-emerald-50 px-4 py-3 text-sm">
                    <strong>Nouvelle clé (à copier maintenant) :</strong>
                    <code className="mt-2 block break-all rounded-sm bg-white p-2">{plainKey}</code>
                </div>
            )}

            <div className="grid gap-6 lg:grid-cols-2">
                <section className="rounded-2xl border border-slate-200 bg-white p-6">
                    <h2 className="font-semibold">Créer une clé API</h2>
                    <p className="mt-1 text-sm text-slate-600">
                        Plan actuel : {usage.plan_name}. La clé complète n&apos;est affichée qu&apos;une seule fois.
                    </p>
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
                            Générer
                        </button>
                    </form>

                    <div className="mt-6 space-y-2">
                        {apiKeys.length === 0 && (
                            <p className="text-sm text-slate-500">Aucune clé pour le moment.</p>
                        )}
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
                                        {k.requests_count} req · {k.is_active ? 'active' : 'révoquée'}
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
                                        Révoquer
                                    </button>
                                )}
                            </div>
                        ))}
                    </div>
                </section>

                <section className="rounded-2xl border border-slate-200 bg-white p-6">
                    <h2 className="font-semibold">Accès & limites</h2>
                    <dl className="mt-4 space-y-3 text-sm text-slate-700">
                        <div>
                            <dt className="font-medium text-slate-900">Base URL</dt>
                            <dd>
                                <code className="rounded-sm bg-slate-100 px-1.5 py-0.5 text-xs">
                                    {docs.base_url}
                                </code>
                            </dd>
                        </div>
                        <div>
                            <dt className="font-medium text-slate-900">Éligibilité</dt>
                            <dd>{docs.plan}</dd>
                        </div>
                        <div>
                            <dt className="font-medium text-slate-900">Débit</dt>
                            <dd>{docs.rate_limit}</dd>
                        </div>
                        <div>
                            <dt className="font-medium text-slate-900">Format</dt>
                            <dd>JSON — en-tête <code>Accept: application/json</code> recommandé</dd>
                        </div>
                    </dl>
                </section>
            </div>

            <section className="mt-6 rounded-2xl border border-slate-200 bg-white p-6">
                <h2 className="text-lg font-semibold">Documentation API Mercuriale</h2>
                <p className="mt-1 max-w-3xl text-sm text-slate-600">
                    API REST en lecture seule pour interroger le catalogue MINCOMMERCE Budget 2026
                    (rubriques et articles : référence, désignation, prix unitaires XAF).
                </p>

                <div className="mt-6 border-t border-slate-100 pt-6">
                    <h3 className="font-semibold">Authentification</h3>
                    <p className="mt-1 text-sm text-slate-600">
                        Envoyez votre clé sur chaque requête. Préférez un en-tête HTTP.
                    </p>
                    <ul className="mt-3 space-y-1 font-mono text-xs text-slate-800">
                        {docs.auth_headers.map((h) => (
                            <li key={h} className="rounded-lg bg-slate-50 px-3 py-2">
                                {h}
                            </li>
                        ))}
                        <li className="rounded-lg bg-slate-50 px-3 py-2 text-slate-500">
                            Query (tests seulement) : {docs.auth_query}
                        </li>
                    </ul>
                </div>

                <div className="mt-8 space-y-8 border-t border-slate-100 pt-6">
                    {docs.endpoints.map((endpoint) => (
                        <article key={endpoint.path} className="scroll-mt-4">
                            <div className="flex flex-wrap items-center gap-2">
                                <span className="rounded-md bg-emerald-100 px-2 py-0.5 text-xs font-semibold text-emerald-900">
                                    {endpoint.method}
                                </span>
                                <code className="text-sm font-semibold text-slate-900">
                                    {docs.base_url}
                                    {endpoint.path}
                                </code>
                            </div>
                            <p className="mt-2 text-sm text-slate-600">{endpoint.summary}</p>

                            {endpoint.params.length > 0 && (
                                <div className="mt-4 overflow-x-auto">
                                    <table className="min-w-full text-left text-sm">
                                        <thead className="border-b border-slate-200 text-xs uppercase tracking-wide text-slate-500">
                                            <tr>
                                                <th className="py-2 pr-4 font-medium">Paramètre</th>
                                                <th className="py-2 pr-4 font-medium">Type</th>
                                                <th className="py-2 pr-4 font-medium">Requis</th>
                                                <th className="py-2 font-medium">Description</th>
                                            </tr>
                                        </thead>
                                        <tbody className="divide-y divide-slate-100">
                                            {endpoint.params.map((p) => (
                                                <tr key={p.name}>
                                                    <td className="py-2 pr-4 font-mono text-xs">{p.name}</td>
                                                    <td className="py-2 pr-4 text-slate-600">{p.type}</td>
                                                    <td className="py-2 pr-4">
                                                        {p.required ? 'oui' : 'non'}
                                                    </td>
                                                    <td className="py-2 text-slate-600">{p.description}</td>
                                                </tr>
                                            ))}
                                        </tbody>
                                    </table>
                                </div>
                            )}

                            <div className="mt-4 grid gap-4 lg:grid-cols-2">
                                <div>
                                    <h4 className="text-xs font-semibold uppercase tracking-wide text-slate-500">
                                        Exemple requête
                                    </h4>
                                    <CodeBlock>{endpoint.example_request}</CodeBlock>
                                </div>
                                <div>
                                    <h4 className="text-xs font-semibold uppercase tracking-wide text-slate-500">
                                        Exemple réponse
                                    </h4>
                                    <CodeBlock>
                                        {JSON.stringify(endpoint.example_response, null, 2)}
                                    </CodeBlock>
                                </div>
                            </div>
                        </article>
                    ))}
                </div>

                <div className="mt-8 border-t border-slate-100 pt-6">
                    <h3 className="font-semibold">Codes d&apos;erreur</h3>
                    <div className="mt-3 overflow-x-auto">
                        <table className="min-w-full text-left text-sm">
                            <thead className="border-b border-slate-200 text-xs uppercase tracking-wide text-slate-500">
                                <tr>
                                    <th className="py-2 pr-4 font-medium">HTTP</th>
                                    <th className="py-2 font-medium">Signification</th>
                                </tr>
                            </thead>
                            <tbody className="divide-y divide-slate-100">
                                {docs.errors.map((err) => (
                                    <tr key={err.code}>
                                        <td className="py-2 pr-4 font-mono text-xs">{err.code}</td>
                                        <td className="py-2 text-slate-600">{err.meaning}</td>
                                    </tr>
                                ))}
                            </tbody>
                        </table>
                    </div>
                    <p className="mt-3 text-xs text-slate-500">
                        Corps d&apos;erreur typique :{' '}
                        <code className="rounded-sm bg-slate-100 px-1">{'{"message":"…"}'}</code>
                    </p>
                </div>
            </section>
        </AppLayout>
    );
}
