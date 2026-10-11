import { Head, Link, useForm, usePage } from '@inertiajs/react';
import { FormEventHandler } from 'react';

type ProviderMeta = {
    label: string;
    api_token_set?: boolean;
    callback_secret_set?: boolean;
    apiuser_set?: boolean;
    apikey_set?: boolean;
    webhook_secret_set?: boolean;
    notify_secret_set?: boolean;
    site_id?: string;
    base_url: string;
};

type Props = {
    default_provider: string;
    webhook_urls: Record<string, string>;
    providers: {
        camerpay: ProviderMeta;
        fapshi: ProviderMeta;
        cinetpay: ProviderMeta;
    };
};

export default function AdminPayments({ default_provider, webhook_urls, providers }: Props) {
    const { flash } = usePage().props as { flash?: { success?: string; error?: string } };
    const { data, setData, put, processing, errors, reset } = useForm({
        default_provider,
        camerpay: {
            api_token: '',
            callback_secret: '',
            base_url: providers.camerpay.base_url,
        },
        fapshi: {
            apiuser: '',
            apikey: '',
            webhook_secret: '',
            base_url: providers.fapshi.base_url,
        },
        cinetpay: {
            apikey: '',
            site_id: providers.cinetpay.site_id ?? '',
            notify_secret: '',
            base_url: providers.cinetpay.base_url,
        },
    });

    const submit: FormEventHandler = (e) => {
        e.preventDefault();
        put(route('admin.payments.update'), {
            onSuccess: () => reset('camerpay.api_token', 'camerpay.callback_secret', 'fapshi.apiuser', 'fapshi.apikey', 'fapshi.webhook_secret', 'cinetpay.apikey', 'cinetpay.notify_secret'),
        });
    };

    return (
        <div className="min-h-screen bg-slate-950 text-slate-100">
            <Head title="Paiements — Admin" />
            <header className="border-b border-white/10 px-6 py-5">
                <div className="mx-auto flex max-w-6xl flex-wrap items-center justify-between gap-3">
                    <div>
                        <p className="text-xs uppercase tracking-[0.2em] text-emerald-300">
                            Facturation
                        </p>
                        <h1 className="text-xl font-semibold">Paiements & CamerPay</h1>
                    </div>
                    <nav className="flex flex-wrap gap-3 text-sm">
                        <Link href="/admin" className="text-emerald-300 hover:underline">
                            Dashboard
                        </Link>
                        <Link href="/admin/mercuriale" className="text-emerald-300 hover:underline">
                            Mercuriales
                        </Link>
                        <Link href="/admin/tenants" className="text-emerald-300 hover:underline">
                            Organisations
                        </Link>
                    </nav>
                </div>
            </header>

            <main className="mx-auto max-w-6xl space-y-6 px-6 py-8">
                {(flash?.success || flash?.error) && (
                    <div
                        className={`rounded-xl px-4 py-3 text-sm ${flash.error ? 'border border-red-400/30 bg-red-400/10 text-red-100' : 'border border-emerald-400/30 bg-emerald-400/10 text-emerald-100'}`}
                    >
                        {flash.error || flash.success}
                    </div>
                )}

                <section className="rounded-2xl border border-white/10 bg-white/5 p-5 text-sm text-slate-300">
                    <p>
                        Agrégateur principal :{' '}
                        <strong className="text-white">CamerPay</strong> (Orange Money, MTN MoMo,
                        carte, PayPal). Générez un token sur{' '}
                        <a
                            href="https://camerpay.biz/client/api"
                            target="_blank"
                            rel="noreferrer"
                            className="text-emerald-300 hover:underline"
                        >
                            camerpay.biz/client/api
                        </a>
                        , puis collez le token et le <em>callback_secret</em> ci-dessous.
                    </p>
                    <p className="mt-2">
                        URL webhook à déclarer chez CamerPay :{' '}
                        <code className="break-all rounded-sm bg-black/40 px-2 py-0.5 text-emerald-200">
                            {webhook_urls.camerpay}
                        </code>
                    </p>
                    <p className="mt-2">
                        En production, utilisez le mode Live de CamerPay et une URL webhook publique en HTTPS.
                        Les paiements simulés ne peuvent pas activer un abonnement en production.
                    </p>
                </section>

                <form onSubmit={submit} className="space-y-6">
                    <section className="rounded-2xl border border-white/10 bg-white/5 p-5">
                        <h2 className="font-semibold text-white">Fournisseur par défaut</h2>
                        <select
                            className="mt-3 w-full max-w-md rounded-xl border-white/10 bg-slate-900 text-sm"
                            value={data.default_provider}
                            onChange={(e) => setData('default_provider', e.target.value)}
                        >
                            <option value="camerpay">CamerPay (recommandé)</option>
                            <option value="fapshi">Fapshi</option>
                            <option value="cinetpay">CinetPay</option>
                        </select>
                        {errors.default_provider && (
                            <p className="mt-2 text-sm text-red-300">{errors.default_provider}</p>
                        )}
                    </section>

                    <section className="rounded-2xl border border-emerald-500/20 bg-emerald-500/5 p-5">
                        <div className="flex flex-wrap items-center justify-between gap-2">
                            <h2 className="font-semibold text-white">CamerPay</h2>
                            <span className="text-xs text-slate-400">
                                Token {providers.camerpay.api_token_set ? 'configuré' : 'manquant'} ·
                                Secret{' '}
                                {providers.camerpay.callback_secret_set ? 'configuré' : 'manquant'}
                            </span>
                        </div>
                        <div className="mt-4 grid gap-4 md:grid-cols-2">
                            <Field
                                label="API token"
                                placeholder={
                                    providers.camerpay.api_token_set
                                        ? '•••••••• (laisser vide pour conserver)'
                                        : '2|xxxxxxxx'
                                }
                                value={data.camerpay.api_token}
                                onChange={(v) =>
                                    setData('camerpay', { ...data.camerpay, api_token: v })
                                }
                            />
                            <Field
                                label="Callback secret (HMAC)"
                                placeholder={
                                    providers.camerpay.callback_secret_set
                                        ? '•••••••• (laisser vide pour conserver)'
                                        : 'Secret dashboard CamerPay'
                                }
                                value={data.camerpay.callback_secret}
                                onChange={(v) =>
                                    setData('camerpay', { ...data.camerpay, callback_secret: v })
                                }
                            />
                            <Field
                                label="Base URL API"
                                value={data.camerpay.base_url}
                                onChange={(v) =>
                                    setData('camerpay', { ...data.camerpay, base_url: v })
                                }
                            />
                        </div>
                    </section>

                    <details className="rounded-2xl border border-white/10 bg-white/5 p-5">
                        <summary className="cursor-pointer font-semibold text-white">
                            Fapshi (secondaire)
                        </summary>
                        <div className="mt-4 grid gap-4 md:grid-cols-2">
                            <Field
                                label="API user"
                                placeholder={
                                    providers.fapshi.apiuser_set ? '••••••••' : 'apiuser'
                                }
                                value={data.fapshi.apiuser}
                                onChange={(v) => setData('fapshi', { ...data.fapshi, apiuser: v })}
                            />
                            <Field
                                label="API key"
                                placeholder={providers.fapshi.apikey_set ? '••••••••' : 'apikey'}
                                value={data.fapshi.apikey}
                                onChange={(v) => setData('fapshi', { ...data.fapshi, apikey: v })}
                            />
                            <Field
                                label="Webhook secret"
                                placeholder={
                                    providers.fapshi.webhook_secret_set ? '••••••••' : 'secret'
                                }
                                value={data.fapshi.webhook_secret}
                                onChange={(v) =>
                                    setData('fapshi', { ...data.fapshi, webhook_secret: v })
                                }
                            />
                            <Field
                                label="Base URL"
                                value={data.fapshi.base_url}
                                onChange={(v) => setData('fapshi', { ...data.fapshi, base_url: v })}
                            />
                        </div>
                        <p className="mt-3 text-xs text-slate-400">
                            Webhook : {webhook_urls.fapshi}
                        </p>
                    </details>

                    <details className="rounded-2xl border border-white/10 bg-white/5 p-5">
                        <summary className="cursor-pointer font-semibold text-white">
                            CinetPay (secondaire)
                        </summary>
                        <div className="mt-4 grid gap-4 md:grid-cols-2">
                            <Field
                                label="API key"
                                placeholder={providers.cinetpay.apikey_set ? '••••••••' : 'apikey'}
                                value={data.cinetpay.apikey}
                                onChange={(v) =>
                                    setData('cinetpay', { ...data.cinetpay, apikey: v })
                                }
                            />
                            <Field
                                label="Site ID"
                                value={data.cinetpay.site_id}
                                onChange={(v) =>
                                    setData('cinetpay', { ...data.cinetpay, site_id: v })
                                }
                            />
                            <Field
                                label="Notify secret"
                                placeholder={
                                    providers.cinetpay.notify_secret_set ? '••••••••' : 'secret'
                                }
                                value={data.cinetpay.notify_secret}
                                onChange={(v) =>
                                    setData('cinetpay', { ...data.cinetpay, notify_secret: v })
                                }
                            />
                            <Field
                                label="Base URL"
                                value={data.cinetpay.base_url}
                                onChange={(v) =>
                                    setData('cinetpay', { ...data.cinetpay, base_url: v })
                                }
                            />
                        </div>
                        <p className="mt-3 text-xs text-slate-400">
                            Webhook : {webhook_urls.cinetpay}
                        </p>
                    </details>

                    <button
                        type="submit"
                        disabled={processing}
                        className="rounded-xl bg-emerald-600 px-5 py-2.5 text-sm font-medium text-white hover:bg-emerald-500 disabled:opacity-50"
                    >
                        {processing ? 'Enregistrement…' : 'Enregistrer la configuration'}
                    </button>
                </form>
            </main>
        </div>
    );
}

function Field({
    label,
    value,
    onChange,
    placeholder,
}: {
    label: string;
    value: string;
    onChange: (value: string) => void;
    placeholder?: string;
}) {
    return (
        <label className="block text-sm">
            <span className="text-slate-400">{label}</span>
            <input
                type="text"
                className="mt-1 w-full rounded-xl border-white/10 bg-slate-900 text-sm text-white"
                value={value}
                placeholder={placeholder}
                onChange={(e) => onChange(e.target.value)}
                autoComplete="off"
            />
        </label>
    );
}
