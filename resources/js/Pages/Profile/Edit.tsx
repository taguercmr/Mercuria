import AppLayout from '@/Layouts/AppLayout';
import { PageProps } from '@/types';
import { Head, Link } from '@inertiajs/react';
import DeleteUserForm from './Partials/DeleteUserForm';
import UpdatePasswordForm from './Partials/UpdatePasswordForm';
import UpdateProfileInformationForm from './Partials/UpdateProfileInformationForm';

export default function Edit({
    mustVerifyEmail,
    status,
}: PageProps<{ mustVerifyEmail: boolean; status?: string }>) {
    return (
        <AppLayout title="Mon compte">
            <Head title="Mon compte" />

            <div className="mb-6 flex flex-wrap items-end justify-between gap-3">
                <p className="text-sm text-slate-600">
                    Identifiants personnels — nom, email et mot de passe.
                </p>
                <Link
                    href={route('app.settings.edit')}
                    className="rounded-xl border border-slate-300 bg-white px-4 py-2 text-sm"
                >
                    Personnalisation organisation
                </Link>
            </div>

            <div className="mx-auto max-w-2xl space-y-6">
                <div className="rounded-2xl border border-slate-200 bg-white p-6">
                    <UpdateProfileInformationForm
                        mustVerifyEmail={mustVerifyEmail}
                        status={status}
                        className="max-w-xl"
                    />
                </div>

                <div className="rounded-2xl border border-slate-200 bg-white p-6">
                    <UpdatePasswordForm className="max-w-xl" />
                </div>

                <div className="rounded-2xl border border-slate-200 bg-white p-6">
                    <DeleteUserForm className="max-w-xl" />
                </div>
            </div>
        </AppLayout>
    );
}
