import Checkbox from '@/Components/Checkbox';
import InputError from '@/Components/InputError';
import InputLabel from '@/Components/InputLabel';
import PasswordInput from '@/Components/PasswordInput';
import PrimaryButton from '@/Components/PrimaryButton';
import TextInput from '@/Components/TextInput';
import GuestLayout from '@/Layouts/GuestLayout';
import { Head, Link, useForm } from '@inertiajs/react';
import { FormEventHandler } from 'react';

export default function Login({ status, canResetPassword }: {
    status?: string;
    canResetPassword: boolean;
}) {
    const { data, setData, post, processing, errors, reset } = useForm({
        email: '',
        password: '',
        remember: false,
    });

    const submit: FormEventHandler = (event) => {
        event.preventDefault();
        post(route('login'), {
            onFinish: () => reset('password'),
        });
    };

    return (
        <GuestLayout
            mode="login"
            title="Heureux de vous retrouver."
            description="Vos projets avancent. Reprenons le fil ensemble."
        >
            <Head title="Connexion" />
            {status && <div className="ma-status" role="status">{status}</div>}

            <form onSubmit={submit} className="ma-form" aria-busy={processing}>
                <div className="ma-field">
                    <InputLabel htmlFor="email" value="Adresse email" />
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

                <div className="ma-field">
                    <InputLabel htmlFor="password" value="Mot de passe" />
                    <PasswordInput
                        id="password"
                        name="password"
                        value={data.password}
                        className="ma-input"
                        placeholder="Votre mot de passe"
                        autoComplete="current-password"
                        required
                        aria-invalid={!!errors.password}
                        aria-describedby={errors.password ? 'password-error' : undefined}
                        onChange={(event) => setData('password', event.target.value)}
                    />
                    <InputError id="password-error" message={errors.password} className="ma-error" role="alert" />
                </div>

                <div className="ma-options">
                    <label className="ma-remember">
                        <Checkbox name="remember" checked={data.remember} onChange={(event) => setData('remember', event.target.checked)} />
                        <span>Se souvenir de moi</span>
                    </label>
                    {canResetPassword && <Link href={route('password.request')} className="ma-link">Mot de passe oublié ?</Link>}
                </div>

                <PrimaryButton type="submit" className="ma-submit" disabled={processing}>
                    {processing ? 'Connexion en cours…' : 'Se connecter'}
                    <span aria-hidden="true" className={processing ? 'ma-spinner' : 'ma-arrow'}>{processing ? '' : '→'}</span>
                </PrimaryButton>
            </form>

            <p className="ma-account-link">Vous découvrez Mercuria ? <Link href={route('register')}>Créer un compte</Link></p>
        </GuestLayout>
    );
}
