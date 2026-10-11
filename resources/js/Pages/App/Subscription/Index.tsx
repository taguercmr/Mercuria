import AppLayout from '@/Layouts/AppLayout';
import { Head, useForm, usePage } from '@inertiajs/react';
import { FormEventHandler } from 'react';

type Props = {
    usage: {
        plan: string;
        plan_name: string;
        documents_used: number;
        documents_limit: number | null;
        api_access: boolean;
        is_paid: boolean;
    };
    plans: any;
    provider: string;
    transactions: Array<{
        id: number;
        provider: string;
        amount: number;
        currency: string;
        status: string;
        created_at: string;
    }>;
};

export default function SubscriptionIndex({ usage, plans, provider, transactions }: Props) {
    const { flash } = usePage().props as { flash?: { success?: string; error?: string } };
    const { data, setData, post, processing } = useForm({
        billing_cycle: 'monthly',
        provider: provider || 'camerpay',
        customer_phone: '',
    });

    const submit: FormEventHandler = (e) => {
        e.preventDefault();
        post(route('app.subscription.checkout'));
    };

    const proPrice =
        data.billing_cycle === 'yearly'
            ? plans.pro?.price_yearly_xaf ?? 99000
            : plans.pro?.price_monthly_xaf ?? 9900;

    return (
        <AppLayout title="Abonnement">
            <Head title="Abonnement" />
            {(flash?.success || flash?.error) && (
                <div
                    className={`mb-4 rounded-xl px-4 py-3 text-sm ${flash.error ? 'border border-red-200 bg-red-50 text-red-700' : 'border border-emerald-200 bg-emerald-50 text-emerald-800'}`}
                >
                    {flash.error || flash.success}
                </div>
            )}

            <div className="grid gap-6 lg:grid-cols-2">
                <section className="rounded-2xl border border-slate-200 bg-white p-6">
                    <h2 className="text-lg font-semibold">Plan actuel : {usage.plan_name}</h2>
                    <p className="mt-2 text-sm text-slate-600">
                        Documents ce mois : {usage.documents_used}
                        {usage.documents_limit !== null ? ` / ${usage.documents_limit}` : ' (illimite)'}
                    </p>
                    <p className="mt-1 text-sm text-slate-600">
                        API mercuriale : {usage.api_access ? 'Activee' : 'Reserve au plan Pro'}
                    </p>
                </section>

                <section className="rounded-2xl border border-emerald-700/30 bg-emerald-50 p-6">
                    <h2 className="text-lg font-semibold">Passer au plan Pro</h2>
                    <p className="mt-1 text-3xl font-semibold">
                        {Number(proPrice).toLocaleString('fr-FR')} FCFA
                        <span className="text-sm font-normal text-slate-600">
                            {data.billing_cycle === 'yearly' ? ' / an' : ' / mois'}
                        </span>
                    </p>
                    <form onSubmit={submit} className="mt-4 space-y-3">
                        <select
                            className="w-full rounded-xl border-slate-300 text-sm"
                            value={data.billing_cycle}
                            onChange={(e) => setData('billing_cycle', e.target.value)}
                        >
                            <option value="monthly">Mensuel</option>
                            <option value="yearly">Annuel</option>
                        </select>
                        <input
                            type="tel"
                            className="w-full rounded-xl border-slate-300 text-sm"
                            placeholder="Téléphone Mobile Money (+237 6XX XX XX XX)"
                            value={data.customer_phone}
                            onChange={(e) => setData('customer_phone', e.target.value)}
                        />
                        <select
                            className="w-full rounded-xl border-slate-300 text-sm"
                            value={data.provider}
                            onChange={(e) => setData('provider', e.target.value)}
                        >
                            <option value="camerpay">CamerPay (Orange / MTN / carte)</option>
                            <option value="fapshi">Fapshi</option>
                            <option value="cinetpay">CinetPay</option>
                        </select>
                        <button
                            disabled={processing || usage.is_paid}
                            className="rounded-xl bg-emerald-800 px-4 py-2 text-sm font-medium text-white disabled:opacity-50"
                        >
                            {usage.is_paid ? 'Deja abonne Pro' : 'Payer maintenant'}
                        </button>
                    </form>
                </section>
            </div>

            <section className="mt-6 rounded-2xl border border-slate-200 bg-white p-6">
                <h3 className="font-semibold">Historique paiements</h3>
                <div className="mt-3 space-y-2 text-sm">
                    {transactions.length === 0 && (
                        <p className="text-slate-500">Aucun paiement pour le moment.</p>
                    )}
                    {transactions.map((t) => (
                        <div key={t.id} className="flex justify-between border-b border-slate-100 py-2">
                            <span>
                                {t.provider} · {t.status}
                            </span>
                            <span>
                                {Number(t.amount).toLocaleString('fr-FR')} {t.currency}
                            </span>
                        </div>
                    ))}
                </div>
            </section>
        </AppLayout>
    );
}
