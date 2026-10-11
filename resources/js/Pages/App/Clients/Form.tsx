import AppLayout from '@/Layouts/AppLayout';
import InputError from '@/Components/InputError';
import InputLabel from '@/Components/InputLabel';
import PrimaryButton from '@/Components/PrimaryButton';
import TextInput from '@/Components/TextInput';
import { Head, useForm } from '@inertiajs/react';
import { FormEventHandler } from 'react';

type Client = {
    id?: number;
    name: string;
    email?: string;
    phone?: string;
    company?: string;
    tax_id?: string;
    address?: string;
    city?: string;
    country?: string;
    notes?: string;
};

export default function ClientForm({ client }: { client: Client | null }) {
    const { data, setData, post, put, processing, errors } = useForm({
        name: client?.name ?? '',
        email: client?.email ?? '',
        phone: client?.phone ?? '',
        company: client?.company ?? '',
        tax_id: client?.tax_id ?? '',
        address: client?.address ?? '',
        city: client?.city ?? '',
        country: client?.country ?? 'Cameroun',
        notes: client?.notes ?? '',
    });

    const submit: FormEventHandler = (e) => {
        e.preventDefault();
        if (client?.id) {
            put(route('app.clients.update', client.id));
        } else {
            post(route('app.clients.store'));
        }
    };

    return (
        <AppLayout title={client ? 'Modifier client' : 'Nouveau client'}>
            <Head title={client ? 'Modifier client' : 'Nouveau client'} />
            <form onSubmit={submit} className="mx-auto max-w-2xl space-y-4 rounded-2xl border border-slate-200 bg-white p-6">
                {[
                    ['name', 'Nom'],
                    ['company', 'Entreprise'],
                    ['email', 'Email'],
                    ['phone', 'Telephone'],
                    ['tax_id', 'NIU / RCCM'],
                    ['city', 'Ville'],
                    ['country', 'Pays'],
                ].map(([key, label]) => (
                    <div key={key}>
                        <InputLabel htmlFor={key} value={label} />
                        <TextInput
                            id={key}
                            className="mt-1 block w-full"
                            value={(data as any)[key]}
                            onChange={(e) => setData(key as any, e.target.value)}
                            required={key === 'name'}
                        />
                        <InputError message={(errors as any)[key]} className="mt-2" />
                    </div>
                ))}
                <div>
                    <InputLabel htmlFor="address" value="Adresse" />
                    <textarea
                        id="address"
                        className="mt-1 w-full rounded-md border-gray-300 shadow-xs"
                        rows={3}
                        value={data.address}
                        onChange={(e) => setData('address', e.target.value)}
                    />
                </div>
                <PrimaryButton disabled={processing}>Enregistrer</PrimaryButton>
            </form>
        </AppLayout>
    );
}
