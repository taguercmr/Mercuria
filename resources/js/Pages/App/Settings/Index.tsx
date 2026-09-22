import AppLayout from '@/Layouts/AppLayout';
import InputLabel from '@/Components/InputLabel';
import PrimaryButton from '@/Components/PrimaryButton';
import TextInput from '@/Components/TextInput';
import { Head, useForm, usePage } from '@inertiajs/react';
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
    plan?: string;
};

export default function SettingsIndex({ tenant }: { tenant: Tenant | null }) {
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
        post(route('app.settings.update'), { forceFormData: true });
    };

    return (
        <AppLayout title="Parametres">
            <Head title="Parametres" />
            {flash?.success && (
                <div className="mb-4 rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800">
                    {flash.success}
                </div>
            )}
            <form onSubmit={submit} className="mx-auto max-w-2xl space-y-4 rounded-2xl border border-slate-200 bg-white p-6">
                <p className="text-sm text-slate-500">Plan actuel : {tenant?.plan ?? 'free'}</p>
                <div>
                    <InputLabel value="Nom de l'organisation" />
                    <TextInput
                        className="mt-1 block w-full"
                        value={data.name}
                        onChange={(e) => setData('name', e.target.value)}
                        required
                    />
                </div>
                <div className="grid gap-4 md:grid-cols-2">
                    <div>
                        <InputLabel value="Email" />
                        <TextInput
                            className="mt-1 block w-full"
                            value={data.email}
                            onChange={(e) => setData('email', e.target.value)}
                        />
                    </div>
                    <div>
                        <InputLabel value="Telephone" />
                        <TextInput
                            className="mt-1 block w-full"
                            value={data.phone}
                            onChange={(e) => setData('phone', e.target.value)}
                        />
                    </div>
                </div>
                <div>
                    <InputLabel value="Devise" />
                    <TextInput
                        className="mt-1 block w-full"
                        value={data.currency}
                        onChange={(e) => setData('currency', e.target.value)}
                    />
                </div>
                <div>
                    <InputLabel value="En-tete (letterhead)" />
                    <textarea
                        className="mt-1 w-full rounded-md border-gray-300 text-sm"
                        rows={4}
                        value={data.letterhead}
                        onChange={(e) => setData('letterhead', e.target.value)}
                    />
                </div>
                <div>
                    <InputLabel value="Pied de page" />
                    <textarea
                        className="mt-1 w-full rounded-md border-gray-300 text-sm"
                        rows={3}
                        value={data.footer_text}
                        onChange={(e) => setData('footer_text', e.target.value)}
                    />
                </div>
                <div className="grid gap-4 md:grid-cols-2">
                    <div>
                        <InputLabel value="Logo" />
                        <input
                            type="file"
                            accept="image/*"
                            className="mt-1 block w-full text-sm"
                            onChange={(e) => setData('logo', e.target.files?.[0] ?? null)}
                        />
                    </div>
                    <div>
                        <InputLabel value="Signature" />
                        <input
                            type="file"
                            accept="image/*"
                            className="mt-1 block w-full text-sm"
                            onChange={(e) => setData('signature', e.target.files?.[0] ?? null)}
                        />
                    </div>
                </div>
                <PrimaryButton disabled={processing}>Enregistrer</PrimaryButton>
            </form>
        </AppLayout>
    );
}
