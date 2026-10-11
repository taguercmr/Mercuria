import InputError from '@/Components/InputError';
import InputLabel from '@/Components/InputLabel';
import PasswordInput from '@/Components/PasswordInput';
import PrimaryButton from '@/Components/PrimaryButton';
import TextInput from '@/Components/TextInput';
import GuestLayout from '@/Layouts/GuestLayout';
import { Head, Link, useForm } from '@inertiajs/react';
import { FormEventHandler } from 'react';

export default function Register() {
    const { data, setData, post, processing, errors, reset } = useForm({
        name: '',
        email: '',
        password: '',
        password_confirmation: '',
    });

    const submit: FormEventHandler = (event) => {
        event.preventDefault();
        post(route('register'), {
            onFinish: () => reset('password', 'password_confirmation'),
        });
    };

    return (
        <GuestLayout
            mode="register"
            title="La suite commence ici."
            description="Créez votre compte et donnez de l’élan à vos projets."
        >
            <Head title="Créer un compte" />
            <form onSubmit={submit} className="ma-form" aria-busy={processing}>
                <div className="ma-field">
                    <InputLabel htmlFor="name" value="Nom complet" />
                    <TextInput
                        id="name"
                        name="name"
                        value={data.name}
                        className="ma-input"
                        placeholder="Votre prénom et votre nom"
                        autoComplete="name"
                        isFocused
                        required
                        aria-invalid={!!errors.name}
                        aria-describedby={errors.name ? 'name-error' : undefined}
                        onChange={(event) => setData('name', event.target.value)}
                    />
                    <InputError id="name-error" message={errors.name} className="ma-error" role="alert" />
                </div>

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
                        placeholder="Choisissez un mot de passe"
                        autoComplete="new-password"
                        required
                        aria-invalid={!!errors.password}
                        aria-describedby={errors.password ? 'password-error' : undefined}
                        onChange={(event) => setData('password', event.target.value)}
                    />
                    <InputError id="password-error" message={errors.password} className="ma-error" role="alert" />
                </div>

                <div className="ma-field">
                    <InputLabel htmlFor="password_confirmation" value="Confirmer le mot de passe" />
                    <PasswordInput
                        id="password_confirmation"
                        name="password_confirmation"
                        value={data.password_confirmation}
                        visibilityLabel="la confirmation du mot de passe"
                        className="ma-input"
                        placeholder="Saisissez-le une seconde fois"
                        autoComplete="new-password"
                        required
                        aria-invalid={!!errors.password_confirmation}
                        aria-describedby={errors.password_confirmation ? 'confirmation-error' : undefined}
                        onChange={(event) => setData('password_confirmation', event.target.value)}
                    />
                    <InputError id="confirmation-error" message={errors.password_confirmation} className="ma-error" role="alert" />
                </div>

                <PrimaryButton type="submit" className="ma-submit" disabled={processing}>
                    {processing ? 'Création de votre compte…' : 'Créer mon compte'}
                    <span aria-hidden="true" className={processing ? 'ma-spinner' : 'ma-arrow'}>{processing ? '' : '→'}</span>
                </PrimaryButton>
            </form>
            <p className="ma-account-link">Déjà un compte ? <Link href={route('login')}>Se connecter</Link></p>
        </GuestLayout>
    );
}
