import AppLayout from '@/Layouts/AppLayout';
import { Head, Link } from '@inertiajs/react';
import { type ReactNode } from 'react';

type Plan = {
    code: string;
    name: string;
    documents_per_month: number | null;
    clients_limit: number | null;
    team_members: number;
    api_access: boolean;
    price_monthly_xaf?: number;
};

type Props = {
    mercurialeYear: number;
    mercurialeSource: string;
    plans: { free: Plan; pro: Plan };
};

const sections = [
    { id: 'demarrage', title: 'Démarrage' },
    { id: 'personnalisation', title: 'Personnalisation' },
    { id: 'mercuriale', title: 'Mercuriale' },
    { id: 'clients', title: 'Clients' },
    { id: 'documents', title: 'Documents & PDF' },
    { id: 'modeles', title: 'Modèles' },
    { id: 'equipe', title: 'Équipe & rôles' },
    { id: 'abonnement', title: 'Abonnement' },
    { id: 'api', title: 'API développeurs' },
    { id: 'support', title: 'Support & compte' },
];

function money(n: number) {
    return new Intl.NumberFormat('fr-FR').format(n);
}

export default function GuideIndex({ mercurialeYear, mercurialeSource, plans }: Props) {
    const free = plans.free;
    const pro = plans.pro;

    return (
        <AppLayout title="Guide d'utilisation">
            <Head title="Guide d'utilisation Mercuria" />

            <div className="mx-auto max-w-4xl">
                <header className="mb-8 rounded-2xl border border-emerald-900/10 bg-gradient-to-br from-white to-emerald-50 p-6">
                    <p className="text-sm font-medium text-emerald-800">Documentation utilisateur</p>
                    <h2 className="mt-1 text-2xl font-semibold tracking-tight">
                        Guide complet Mercuria
                    </h2>
                    <p className="mt-2 max-w-2xl text-sm text-slate-600">
                        Mercuria vous aide à créer devis, proformas, bons de commande et factures
                        à partir de la mercuriale officielle {mercurialeYear} ({mercurialeSource}).
                    </p>
                </header>

                <nav className="mb-8 rounded-2xl border border-slate-200 bg-white p-4">
                    <p className="mb-3 text-xs font-semibold uppercase tracking-wide text-slate-500">
                        Sommaire
                    </p>
                    <div className="flex flex-wrap gap-2">
                        {sections.map((s) => (
                            <a
                                key={s.id}
                                href={`#${s.id}`}
                                className="rounded-lg border border-slate-200 bg-slate-50 px-3 py-1.5 text-sm text-slate-700 hover:border-emerald-300 hover:bg-emerald-50"
                            >
                                {s.title}
                            </a>
                        ))}
                    </div>
                </nav>

                <div className="space-y-8">
                    <Section id="demarrage" title="1. Démarrage">
                        <ol className="list-decimal space-y-2 pl-5 text-sm text-slate-700">
                            <li>
                                Créez un compte via <strong>Inscription</strong>, puis confirmez
                                votre e-mail si demandé.
                            </li>
                            <li>
                                Renseignez votre <strong>organisation</strong> (nom, devise XAF,
                                etc.) à l’onboarding.
                            </li>
                            <li>
                                Suivez le <Link className="text-emerald-800 underline" href={route('app.onboarding.guide')}>démarrage guidé</Link> :
                                personnalisation → premier client → premier document.
                            </li>
                        </ol>
                        <Callout>
                            Sans organisation active, Mercuria vous redirige automatiquement vers
                            l’onboarding.
                        </Callout>
                    </Section>

                    <Section id="personnalisation" title="2. Personnalisation">
                        <p className="text-sm text-slate-700">
                            Menu <Link className="text-emerald-800 underline" href={route('app.settings.edit')}>Personnalisation</Link> :
                            identité de l’organisation affichée sur les PDF.
                        </p>
                        <ul className="mt-3 list-disc space-y-1 pl-5 text-sm text-slate-700">
                            <li>Nom, e-mail, téléphone, devise</li>
                            <li>En-tête (letterhead) et pied de page</li>
                            <li>Logo et signature (images)</li>
                        </ul>
                        <p className="mt-3 text-sm text-slate-600">
                            Les rôles <em>viewer</em> voient ces réglages en lecture seule.
                        </p>
                    </Section>

                    <Section id="mercuriale" title={`3. Mercuriale ${mercurialeYear}`}>
                        <p className="text-sm text-slate-700">
                            Catalogue officiel des prix unitaires. Ouvrez{' '}
                            <Link className="text-emerald-800 underline" href={route('app.catalogue.index')}>
                                Mercuriale {mercurialeYear}
                            </Link>
                            .
                        </p>
                        <ul className="mt-3 list-disc space-y-1 pl-5 text-sm text-slate-700">
                            <li>Recherche par mot-clé ou référence</li>
                            <li>Filtre par rubrique</li>
                            <li>Favoris pour retrouver rapidement vos articles</li>
                            <li>Ajout direct dans un nouveau devis depuis une fiche</li>
                        </ul>
                    </Section>

                    <Section id="clients" title="4. Clients">
                        <p className="text-sm text-slate-700">
                            Gérez vos clients dans{' '}
                            <Link className="text-emerald-800 underline" href={route('app.clients.index')}>
                                Clients
                            </Link>
                            . Vous pouvez aussi{' '}
                            <strong>exporter au CSV</strong> depuis la liste.
                        </p>
                        <p className="mt-2 text-sm text-slate-600">
                            Limite Free : {free.clients_limit ?? 'illimitée'} clients · Pro :
                            illimité.
                        </p>
                    </Section>

                    <Section id="documents" title="5. Documents & téléchargement PDF">
                        <p className="mb-3 text-sm text-slate-700">
                            Types supportés : <strong>devis</strong>, <strong>proforma</strong>,{' '}
                            <strong>bon de commande (BC)</strong>, <strong>facture</strong>.
                        </p>

                        <h4 className="text-sm font-semibold text-slate-900">Créer un document</h4>
                        <ol className="mt-2 list-decimal space-y-1 pl-5 text-sm text-slate-700">
                            <li>
                                <Link className="text-emerald-800 underline" href="/app/documents/create?type=quote">
                                    Nouveau devis
                                </Link>{' '}
                                (ou autre type depuis Documents).
                            </li>
                            <li>Choisissez un client, un titre, des lignes (articles mercuriale ou libres).</li>
                            <li>Enregistrez — un numéro est attribué selon votre séquence.</li>
                        </ol>

                        <h4 className="mt-4 text-sm font-semibold text-slate-900">
                            Télécharger un PDF
                        </h4>
                        <p className="mt-2 text-sm text-slate-700">
                            Sur la fiche d’un document, cliquez sur{' '}
                            <strong>Télécharger PDF</strong>. Le fichier inclut logo, en-tête,
                            lignes, totaux et signature selon votre personnalisation.
                        </p>
                        <Callout tone="success">
                            Oui — devis, proforma, BC et facture se téléchargent tous en PDF,
                            y compris avec le plan Gratuit. L’<strong>envoi par e-mail</strong>{' '}
                            (PDF en pièce jointe) est inclus avec le plan Pro.
                        </Callout>

                        <h4 className="mt-4 text-sm font-semibold text-slate-900">
                            Conversion (enchaînement métier)
                        </h4>
                        <ul className="mt-2 list-disc space-y-1 pl-5 text-sm text-slate-700">
                            <li>Devis → Proforma ou Facture</li>
                            <li>Proforma → Bon de commande</li>
                            <li>Bon de commande → Facture</li>
                        </ul>
                        <p className="mt-2 text-sm text-slate-600">
                            Autres actions : modifier, dupliquer, exporter la liste en CSV,
                            sauver comme modèle.
                        </p>
                        <p className="mt-2 text-sm text-slate-600">
                            Quota Free : {free.documents_per_month} documents / mois · Pro :
                            illimité.
                        </p>
                    </Section>

                    <Section id="modeles" title="6. Modèles">
                        <p className="text-sm text-slate-700">
                            Depuis un document, <strong>Sauver comme modèle</strong>, puis
                            réutilisez-le dans{' '}
                            <Link className="text-emerald-800 underline" href={route('app.templates.index')}>
                                Modèles
                            </Link>{' '}
                            pour accélérer les devis récurrents.
                        </p>
                    </Section>

                    <Section id="equipe" title="7. Équipe & rôles">
                        <p className="text-sm text-slate-700">
                            L’owner invite des membres depuis{' '}
                            <Link className="text-emerald-800 underline" href={route('app.team.index')}>
                                Équipe
                            </Link>
                            .
                        </p>
                        <div className="mt-3 overflow-x-auto">
                            <table className="min-w-full text-left text-sm">
                                <thead className="border-b border-slate-200 text-xs uppercase text-slate-500">
                                    <tr>
                                        <th className="py-2 pr-4">Rôle</th>
                                        <th className="py-2">Droits</th>
                                    </tr>
                                </thead>
                                <tbody className="divide-y divide-slate-100 text-slate-700">
                                    <tr>
                                        <td className="py-2 pr-4 font-medium">owner</td>
                                        <td className="py-2">Tout + invitations & rôles</td>
                                    </tr>
                                    <tr>
                                        <td className="py-2 pr-4 font-medium">editor</td>
                                        <td className="py-2">Créer / modifier documents & clients</td>
                                    </tr>
                                    <tr>
                                        <td className="py-2 pr-4 font-medium">viewer</td>
                                        <td className="py-2">Lecture seule</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                        <p className="mt-2 text-sm text-slate-600">
                            Free : {free.team_members} membre · Pro : jusqu’à {pro.team_members}{' '}
                            membres.
                        </p>
                    </Section>

                    <Section id="abonnement" title="8. Abonnement">
                        <p className="text-sm text-slate-700">
                            Gérez votre offre dans{' '}
                            <Link className="text-emerald-800 underline" href={route('app.subscription.show')}>
                                Abonnement
                            </Link>
                            .
                        </p>
                        <ul className="mt-3 list-disc space-y-1 pl-5 text-sm text-slate-700">
                            <li>
                                <strong>{free.name}</strong> — démarrer gratuitement
                            </li>
                            <li>
                                <strong>{pro.name}</strong>
                                {pro.price_monthly_xaf
                                    ? ` — à partir de ${money(pro.price_monthly_xaf)} XAF / mois`
                                    : ''}{' '}
                                : documents/clients illimités, API, équipe élargie
                            </li>
                        </ul>
                    </Section>

                    <Section id="api" title="9. API développeurs">
                        <p className="text-sm text-slate-700">
                            Réservée au plan Pro. Créez des clés et consultez la documentation dans{' '}
                            <Link className="text-emerald-800 underline" href={route('app.developers.index')}>
                                API Développeurs
                            </Link>
                            .
                        </p>
                        <ul className="mt-3 list-disc space-y-1 pl-5 text-sm text-slate-700">
                            <li>GET /api/v1/mercuriale/rubriques</li>
                            <li>GET /api/v1/mercuriale/articles (recherche, pagination)</li>
                            <li>GET /api/v1/mercuriale/articles/&#123;reference&#125;</li>
                        </ul>
                        <p className="mt-2 text-sm text-slate-600">Limite : 60 requêtes / minute / clé.</p>
                    </Section>

                    <Section id="support" title="10. Support, notifications & compte">
                        <ul className="list-disc space-y-2 pl-5 text-sm text-slate-700">
                            <li>
                                <Link className="text-emerald-800 underline" href={route('app.notifications.index')}>
                                    Notifications
                                </Link>{' '}
                                — invitations, tickets, abonnement
                            </li>
                            <li>
                                <Link className="text-emerald-800 underline" href={route('app.tickets.index')}>
                                    Support
                                </Link>{' '}
                                — ouvrir un ticket d’aide
                            </li>
                            <li>
                                <Link className="text-emerald-800 underline" href={route('profile.edit')}>
                                    Mon compte
                                </Link>{' '}
                                — nom, e-mail, mot de passe
                            </li>
                            <li>
                                <strong>Déconnexion</strong> — bouton en haut à droite ou bas du
                                menu latéral
                            </li>
                        </ul>
                    </Section>
                </div>

                <footer className="mt-10 rounded-2xl border border-slate-200 bg-white p-5 text-sm text-slate-600">
                    Besoin d’aide supplémentaire ? Ouvrez un ticket dans{' '}
                    <Link className="text-emerald-800 underline" href={route('app.tickets.index')}>
                        Support
                    </Link>
                    .
                </footer>
            </div>
        </AppLayout>
    );
}

function Section({
    id,
    title,
    children,
}: {
    id: string;
    title: string;
    children: ReactNode;
}) {
    return (
        <section id={id} className="scroll-mt-6 rounded-2xl border border-slate-200 bg-white p-6">
            <h3 className="text-lg font-semibold text-slate-900">{title}</h3>
            <div className="mt-4">{children}</div>
        </section>
    );
}

function Callout({
    children,
    tone = 'info',
}: {
    children: ReactNode;
    tone?: 'info' | 'success';
}) {
    const styles =
        tone === 'success'
            ? 'border-emerald-200 bg-emerald-50 text-emerald-950'
            : 'border-slate-200 bg-slate-50 text-slate-800';

    return (
        <div className={`mt-4 rounded-xl border px-4 py-3 text-sm ${styles}`}>{children}</div>
    );
}
