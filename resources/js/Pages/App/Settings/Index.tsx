import AppLayout from '@/Layouts/AppLayout';
import InputLabel from '@/Components/InputLabel';
import PrimaryButton from '@/Components/PrimaryButton';
import TextInput from '@/Components/TextInput';
import { Head, Link, useForm, usePage } from '@inertiajs/react';
import { FormEventHandler } from 'react';

type Tenant = {
    id: number;
    name: string;
    email?: string;
    phone?: string;
    currency: string;
    letterhead?: string;
    footer_text?: string;
    logo_path?: string;
    signature_path?: string;
    logo_url?: string | null;
    signature_url?: string | null;
    plan?: string;
};

export default function SettingsIndex({
    tenant,
    canWrite = true,
    hasAdvancedCustomization = false,
}: {
    tenant: Tenant | null;
    canWrite?: boolean;
    hasAdvancedCustomization?: boolean;
}) {
    const { flash } = usePage().props as { flash?: { success?: string } };
    const { data, setData, post, processing } = useForm({
        name: tenant?.name ?? '',
        email: tenant?.email ?? '',
        phone: tenant?.phone ?? '',
        currency: tenant?.currency ?? 'XAF',
        letterhead: tenant?.letterhead ?? '',
        footer_text: tenant?.footer_text ?? '',
        logo: null as File | null,
        signature: null as File | null,
    });

    const submit: FormEventHandler = (e) => {
        e.preventDefault();
        if (!canWrite) return;
        post(route('app.settings.update'), { forceFormData: true });
    };

    return (
        <AppLayout title="Personnalisation">
            <Head title="Personnalisation" />
            {flash?.success && (
                <div className="mb-4 rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800">
                    {flash.success}
                </div>
            )}

            <div className="mb-6 flex flex-wrap items-end justify-between gap-3">
                <div>
                    <h2 className="text-xl font-semibold">Identité de l&apos;organisation</h2>
                    <p className="mt-1 text-sm text-slate-600">
                        Le plan Gratuit inclut les coordonnées, la devise et les designs standards.
                    </p>
                </div>
                <Link
                    href={route('profile.edit')}
                    className="rounded-xl border border-slate-300 bg-white px-4 py-2 text-sm"
                >
                    Mon compte utilisateur
                </Link>
            </div>

            {!canWrite && (
                <div className="mb-4 rounded-xl border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-900">
                    Mode lecture seule (rôle viewer) — contactez un propriétaire pour modifier.
                </div>
            )}

            <form
                onSubmit={submit}
                className="mx-auto max-w-2xl space-y-6 rounded-2xl border border-slate-200 bg-white p-6"
            >
                <p className="text-sm text-slate-500">Plan actuel : {tenant?.plan ?? 'free'}</p>

                <section className="space-y-4">
                    <h3 className="text-sm font-semibold uppercase tracking-wide text-slate-500">
                        Organisation
                    </h3>
                    <div>
                        <InputLabel value="Nom de l'organisation" />
                        <TextInput
                            className="mt-1 block w-full"
                            value={data.name}
                            onChange={(e) => setData('name', e.target.value)}
                            required
                            disabled={!canWrite}
                        />
                    </div>
                    <div className="grid gap-4 md:grid-cols-2">
                        <div>
                            <InputLabel value="Email" />
                            <TextInput
                                className="mt-1 block w-full"
                                value={data.email}
                                onChange={(e) => setData('email', e.target.value)}
                                disabled={!canWrite}
                            />
                        </div>
                        <div>
                            <InputLabel value="Téléphone" />
                            <TextInput
                                className="mt-1 block w-full"
                                value={data.phone}
                                onChange={(e) => setData('phone', e.target.value)}
                                disabled={!canWrite}
                            />
                        </div>
                    </div>
                    <div>
                        <InputLabel value="Devise" />
                        <TextInput
                            className="mt-1 block w-full"
                            value={data.currency}
                            onChange={(e) => setData('currency', e.target.value)}
                            disabled={!canWrite}
                        />
                    </div>
                </section>

                <section className="space-y-4 border-t border-slate-100 pt-6">
                    <div className="flex flex-wrap items-center justify-between gap-2">
                        <h3 className="text-sm font-semibold uppercase tracking-wide text-slate-500">
                            Personnalisation complète des PDF
                        </h3>
                        {!hasAdvancedCustomization && <Link href={route('app.subscription.show')} className="rounded-full bg-amber-50 px-3 py-1 text-xs font-medium text-amber-900">Disponible avec Pro</Link>}
                    </div>
                    {!hasAdvancedCustomization && <p className="rounded-xl border border-amber-200 bg-amber-50 p-3 text-sm text-amber-900">Le logo, l’en-tête personnalisé, le pied de page et la signature sont inclus dans le plan Pro.</p>}
                    <div>
                        <InputLabel value="En-tête (letterhead)" />
                        <textarea
                            className="mt-1 w-full rounded-md border-gray-300 text-sm disabled:bg-slate-50"
                            rows={4}
                            value={data.letterhead}
                            onChange={(e) => setData('letterhead', e.target.value)}
                            disabled={!canWrite || !hasAdvancedCustomization}
                            placeholder="Adresse, NIU, mentions légales..."
                        />
                    </div>
                    <div>
                        <InputLabel value="Pied de page" />
                        <textarea
                            className="mt-1 w-full rounded-md border-gray-300 text-sm disabled:bg-slate-50"
                            rows={3}
                            value={data.footer_text}
                            onChange={(e) => setData('footer_text', e.target.value)}
                            disabled={!canWrite || !hasAdvancedCustomization}
                            placeholder="Conditions de paiement, merci de votre confiance..."
                        />
                    </div>
                    <div className="grid gap-4 md:grid-cols-2">
                        <div>
                            <InputLabel value="Logo" />
                            {tenant?.logo_url && (
                                <img
                                    src={tenant.logo_url}
                                    alt="Logo actuel"
                                    className="mb-2 h-16 w-auto rounded-sm border border-slate-200 bg-slate-50 object-contain p-1"
                                />
                            )}
                            <input
                                type="file"
                                accept="image/*"
                                className="mt-1 block w-full text-sm"
                                disabled={!canWrite || !hasAdvancedCustomization}
                                onChange={(e) => setData('logo', e.target.files?.[0] ?? null)}
                            />
                        </div>
                        <div>
                            <InputLabel value="Signature" />
                            {tenant?.signature_url && (
                                <img
                                    src={tenant.signature_url}
                                    alt="Signature actuelle"
                                    className="mb-2 h-16 w-auto rounded-sm border border-slate-200 bg-slate-50 object-contain p-1"
                                />
                            )}
                            <input
                                type="file"
                                accept="image/*"
                                className="mt-1 block w-full text-sm"
                                disabled={!canWrite || !hasAdvancedCustomization}
                                onChange={(e) => setData('signature', e.target.files?.[0] ?? null)}
                            />
                        </div>
                    </div>
                </section>

                {canWrite && (
                    <PrimaryButton disabled={processing}>Enregistrer la personnalisation</PrimaryButton>
                )}
            </form>
        </AppLayout>
    );
}
