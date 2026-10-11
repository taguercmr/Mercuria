import InputError from '@/Components/InputError';
import InputLabel from '@/Components/InputLabel';
import PrimaryButton from '@/Components/PrimaryButton';
import TextInput from '@/Components/TextInput';
import GuestLayout from '@/Layouts/GuestLayout';
import { Head, Link, useForm } from '@inertiajs/react';
import { FormEventHandler } from 'react';

export default function ForgotPassword({ email = '', status }: { email?: string; status?: string }) {
    const { data, setData, post, processing, errors } = useForm({ email });

    const submit: FormEventHandler = (event) => {
        event.preventDefault();
        post(route('password.email'));
    };

    return (
        <GuestLayout title="Un nouveau départ." description="Recevez un code par email pour retrouver votre espace Mercuria.">
            <Head title="Mot de passe oublié" />
            <ol className="ma-recovery-steps" aria-label="Étapes de récupération">
                <li aria-current="step"><span>1</span> Votre email</li>
                <li><span>2</span> Nouveau mot de passe</li>
            </ol>
            {status && <div className="ma-status" role="status">{status}</div>}
            <form onSubmit={submit} className="ma-form" aria-busy={processing}>
                <div className="ma-field">
                    <InputLabel htmlFor="email" value="Adresse email de votre compte" />
                    <TextInput
                        id="email"
                        type="email"
                        name="email"
                        value={data.email}
                        className="ma-input"
                        placeholder="vous@entreprise.com"
                        autoComplete="username"
                        autoCapitalize="none"
                        spellCheck={false}
                        isFocused
                        required
                        aria-invalid={!!errors.email}
                        aria-describedby={errors.email ? 'email-error' : undefined}
                        onChange={(event) => setData('email', event.target.value)}
                    />
                    <InputError id="email-error" message={errors.email} className="ma-error" role="alert" />
                </div>
                <p className="ma-field-hint">Vous recevrez un code de 7 caractères. Votre mot de passe reste inchangé jusqu’à sa validation.</p>
                <PrimaryButton type="submit" className="ma-submit" disabled={processing}>
                    {processing ? 'Envoi en cours…' : 'Recevoir mon code'}
                    <span aria-hidden="true" className={processing ? 'ma-spinner' : 'ma-arrow'}>{processing ? '' : '→'}</span>
                </PrimaryButton>
            </form>
            <p className="ma-account-link">Déjà un code ? <Link href={route('password.reset')}>Saisir mon code</Link></p>
            <p className="ma-account-link"><Link href={route('login')}>← Retour à la connexion</Link></p>
        </GuestLayout>
    );
}
