import ApplicationLogo from '@/Components/ApplicationLogo';
import { Link } from '@inertiajs/react';
import { PropsWithChildren } from 'react';
import '../../css/auth.css';

type Props = PropsWithChildren<{
    mode?: 'login' | 'register';
    title?: string;
    description?: string;
}>;

export default function Guest({ children, mode, title, description }: Props) {
    return (
        <div className={`mercuria-auth ${mode ? `ma-${mode}` : ''}`}>
            <div className="ma-backdrop" aria-hidden="true">
                <div className="ma-halo ma-halo-green" />
                <div className="ma-halo ma-halo-gold" />
                <div className="ma-orbit ma-orbit-one" />
                <div className="ma-orbit ma-orbit-two" />
                <div className="ma-pearl" />
            </div>
            <header className="ma-topbar">
                <Link href={route('landing')} className="ma-back-link">
                    <span aria-hidden="true">←</span> Retour à l’accueil
                </Link>
                <span className="ma-top-note"><i /> Pensé au Cameroun. Pour vos ambitions.</span>
            </header>

            <main className="ma-main">
                <div className="ma-brand-block">
                    <Link href={route('landing')} className="ma-brand" aria-label="Mercuria — Accueil">
                        <ApplicationLogo />
                        <span>Mercuria<span className="ma-brand-dot">.</span></span>
                    </Link>
                    <p>Moins de paperasse. Plus de perspectives.</p>
                </div>

                <div className="ma-card">
                    {mode && (
                        <nav className="ma-switch" aria-label="Accès à votre espace">
                            <Link href={route('login')} aria-current={mode === 'login' ? 'page' : undefined}>Connexion</Link>
                            <Link href={route('register')} aria-current={mode === 'register' ? 'page' : undefined}>Inscription</Link>
                        </nav>
                    )}
                    {title && (
                        <div className="ma-heading">
                            <span className="ma-eyebrow">{mode === 'register' ? 'UN NOUVEAU CHAPITRE' : 'VOTRE ESPACE VOUS ATTEND'}</span>
                            <h1>{title}</h1>
                            {description && <p>{description}</p>}
                        </div>
                    )}
                    {children}
                </div>

                <div className="ma-bottom-note">
                    <span className="ma-gold-line" />
                    <p>Une bonne référence. Une offre soignée.<br />Et vous, prêt à avancer.</p>
                </div>
            </main>
            <footer className="ma-footer">© {new Date().getFullYear()} Mercuria <span>·</span> Par Traguer AI</footer>
        </div>
    );
}
