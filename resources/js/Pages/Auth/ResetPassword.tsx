import InputError from '@/Components/InputError';
import InputLabel from '@/Components/InputLabel';
import PasswordInput from '@/Components/PasswordInput';
import PrimaryButton from '@/Components/PrimaryButton';
import TextInput from '@/Components/TextInput';
import GuestLayout from '@/Layouts/GuestLayout';
import { Head, Link, useForm } from '@inertiajs/react';
import { FormEventHandler, useEffect, useState } from 'react';

export default function ResetPassword({ email, status, expiresInMinutes, resendAvailableIn }: {
    email: string;
    status?: string;
    expiresInMinutes: number;
    resendAvailableIn: number;
}) {
    const { data, setData, post, processing, errors, reset } = useForm({
        email,
        code: '',
        password: '',
        password_confirmation: '',
    });
    const resend = useForm({ email });
    const [secondsRemaining, setSecondsRemaining] = useState(resendAvailableIn);

    useEffect(() => {
        setSecondsRemaining(resendAvailableIn);
    }, [resendAvailableIn]);

    useEffect(() => {
        const timer = window.setInterval(() => setSecondsRemaining((seconds) => Math.max(0, seconds - 1)), 1000);
        return () => window.clearInterval(timer);
    }, []);

    const normalizeCode = (value: string) => value.replace(/\s/g, '').toUpperCase().slice(0, 7);

    const submit: FormEventHandler = (event) => {
        event.preventDefault();
        post(route('password.store'), {
            onFinish: () => reset('password', 'password_confirmation'),
        });
    };

    const resendCode: FormEventHandler = (event) => {
        event.preventDefault();
        resend.transform(() => ({ email: data.email }));
        resend.post(route('password.email'), {
            preserveScroll: true,
            onSuccess: () => {
                setSecondsRemaining(60);
                reset('code');
            },
        });
    };

    return (
        <GuestLayout title="Retrouvez votre espace." description="Saisissez votre code, puis choisissez un nouveau mot de passe.">
            <Head title="Nouveau mot de passe" />
            <ol className="ma-recovery-steps" aria-label="Étapes de récupération">
                <li><span>✓</span> Votre email</li>
                <li aria-current="step"><span>2</span> Nouveau mot de passe</li>
            </ol>
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
                        autoComplete="username"
                        autoCapitalize="none"
                        spellCheck={false}
                        required
                        isFocused={!email}
                        aria-invalid={!!errors.email}
                        aria-describedby={errors.email ? 'email-error' : undefined}
                        onChange={(event) => setData('email', event.target.value)}
                    />
                    <InputError id="email-error" message={errors.email} className="ma-error" role="alert" />
                </div>
                <div className="ma-field">
                    <InputLabel htmlFor="code" value="Code reçu par email" />
                    <TextInput
                        id="code"
                        name="code"
                        value={data.code}
                        className="ma-input ma-code-input"
                        placeholder="· · · · · · ·"
                        autoComplete="one-time-code"
                        autoCapitalize="characters"
                        spellCheck={false}
                        maxLength={7}
                        minLength={7}
                        pattern="[A-Z2-9]{7}"
                        required
                        isFocused={!!email}
                        aria-invalid={!!errors.code}
                        aria-describedby="code-hint code-error"
                        onChange={(event) => setData('code', normalizeCode(event.target.value))}
                        onPaste={(event) => {
                            event.preventDefault();
                            setData('code', normalizeCode(event.clipboardData.getData('text')));
                        }}
                    />
                    <p id="code-hint" className="ma-field-hint">7 caractères, valable {expiresInMinutes} minutes. Utilisez le dernier code reçu.</p>
                    <InputError id="code-error" message={errors.code} className="ma-error" role="alert" />
                </div>
                <div className="ma-field">
                    <InputLabel htmlFor="password" value="Nouveau mot de passe" />
                    <PasswordInput
                        id="password"
                        name="password"
                        value={data.password}
                        className="ma-input"
                        placeholder="Au moins 8 caractères"
                        autoComplete="new-password"
                        minLength={8}
                        required
                        aria-invalid={!!errors.password}
                        aria-describedby={errors.password ? 'password-error' : undefined}
                        onChange={(event) => setData('password', event.target.value)}
                    />
                    <InputError id="password-error" message={errors.password} className="ma-error" role="alert" />
                </div>
                <div className="ma-field">
                    <InputLabel htmlFor="password_confirmation" value="Confirmer le nouveau mot de passe" />
                    <PasswordInput
                        id="password_confirmation"
                        name="password_confirmation"
                        value={data.password_confirmation}
                        className="ma-input"
                        visibilityLabel="Afficher la confirmation du mot de passe"
                        autoComplete="new-password"
                        minLength={8}
                        required
                        aria-invalid={!!errors.password_confirmation}
                        aria-describedby={errors.password_confirmation ? 'confirmation-error' : undefined}
                        onChange={(event) => setData('password_confirmation', event.target.value)}
                    />
                    <InputError id="confirmation-error" message={errors.password_confirmation} className="ma-error" role="alert" />
                </div>
                <PrimaryButton type="submit" className="ma-submit" disabled={processing || resend.processing}>
                    {processing ? 'Enregistrement…' : 'Enregistrer mon mot de passe'}
                    <span aria-hidden="true" className={processing ? 'ma-spinner' : 'ma-arrow'}>{processing ? '' : '→'}</span>
                </PrimaryButton>
            </form>
            <form onSubmit={resendCode} className="ma-resend" aria-busy={resend.processing}>
                <span>Vous n’avez pas reçu le code ?</span>
                <button type="submit" disabled={resend.processing || processing || secondsRemaining > 0 || !data.email}>
                    {resend.processing ? 'Envoi en cours…' : secondsRemaining > 0 ? `Renvoyer dans ${secondsRemaining} s` : 'Renvoyer le code'}
                </button>
                <InputError message={resend.errors.email} className="ma-error" role="alert" />
            </form>
            <p className="ma-account-link"><Link href={route('login')}>← Retour à la connexion</Link></p>
        </GuestLayout>
    );
}
