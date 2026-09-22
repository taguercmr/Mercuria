import InputError from '@/Components/InputError';
import InputLabel from '@/Components/InputLabel';
import PrimaryButton from '@/Components/PrimaryButton';
import TextInput from '@/Components/TextInput';
import GuestLayout from '@/Layouts/GuestLayout';
import { Head, Link, useForm, usePage } from '@inertiajs/react';
import { FormEventHandler } from 'react';

type Invitation = {
    email: string;
    role: string;
    tenant?: { name: string } | null;
    token: string;
};

export default function AcceptInvitation({
    invitation,
    existingUser,
}: {
    invitation: Invitation;
    existingUser: boolean;
}) {
    const { auth, flash } = usePage().props as {
        auth?: { user?: { email?: string } | null };
        flash?: { error?: string };
    };

    const { data, setData, post, processing, errors } = useForm({
        name: '',
        password: '',
        password_confirmation: '',
    });

    const submit: FormEventHandler = (e) => {
        e.preventDefault();
        post(route('invitations.accept', invitation.token));
    };

    const loggedInAsInvitee = auth?.user?.email === invitation.email;

    return (
        <GuestLayout>
            <Head title="Invitation equipe" />

            <div className="mb-6 text-center">
                <h1 className="text-xl font-semibold text-slate-900">Rejoindre l&apos;equipe</h1>
                <p className="mt-2 text-sm text-slate-600">
                    {invitation.tenant?.name ?? 'Organisation'} vous invite en tant que{' '}
                    <strong>{invitation.role}</strong> ({invitation.email}).
                </p>
            </div>

            {flash?.error && (
                <div className="mb-4 rounded-xl bg-rose-50 px-4 py-3 text-sm text-rose-800">
                    {flash.error}
                </div>
            )}

            {existingUser && !loggedInAsInvitee ? (
                <div className="space-y-4 text-sm text-slate-700">
                    <p>
                        Un compte existe deja pour cet email. Connectez-vous pour accepter
                        l&apos;invitation.
                    </p>
                    <Link
                        href={route('login')}
                        className="inline-flex rounded-xl bg-emerald-800 px-4 py-2 text-white"
                    >
                        Se connecter
                    </Link>
                </div>
            ) : existingUser && loggedInAsInvitee ? (
                <form onSubmit={submit}>
                    <PrimaryButton disabled={processing} className="w-full justify-center">
                        Accepter l&apos;invitation
                    </PrimaryButton>
                </form>
            ) : (
                <form onSubmit={submit} className="space-y-4">
                    <div>
                        <InputLabel htmlFor="name" value="Nom complet" />
                        <TextInput
                            id="name"
                            className="mt-1 block w-full"
                            value={data.name}
                            onChange={(e) => setData('name', e.target.value)}
                            required
                            isFocused
                        />
                        <InputError message={errors.name} className="mt-2" />
                    </div>
                    <div>
                        <InputLabel htmlFor="password" value="Mot de passe" />
                        <TextInput
                            id="password"
                            type="password"
                            className="mt-1 block w-full"
                            value={data.password}
                            onChange={(e) => setData('password', e.target.value)}
                            required
                        />
                        <InputError message={errors.password} className="mt-2" />
                    </div>
                    <div>
                        <InputLabel
                            htmlFor="password_confirmation"
                            value="Confirmer le mot de passe"
                        />
                        <TextInput
                            id="password_confirmation"
                            type="password"
                            className="mt-1 block w-full"
                            value={data.password_confirmation}
                            onChange={(e) =>
                                setData('password_confirmation', e.target.value)
                            }
                            required
                        />
                    </div>
                    <PrimaryButton disabled={processing} className="w-full justify-center">
                        Creer mon compte et rejoindre
                    </PrimaryButton>
                </form>
            )}
        </GuestLayout>
    );
}
