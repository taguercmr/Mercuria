import ApplicationLogo from '@/Components/ApplicationLogo';
import { Head, Link } from '@inertiajs/react';
import { useState } from 'react';
import type { ReactNode } from 'react';
import '../../css/landing.css';

type Plan = {
    code: string;
    name: string;
    documents_per_month: number | null;
    clients_limit: number | null;
    api_access: boolean;
    team_members?: number;
    price_monthly_xaf?: number;
};
type Props = {
    canLogin: boolean;
    canRegister: boolean;
    mercurialeYear: number;
    source: string;
    plans: Record<string, Plan>;
};
type IconName = 'arrow' | 'file' | 'search' | 'users' | 'check' | 'grid' | 'spark' | 'plus' | 'close' | 'menu' | 'chevron';
function Icon({ name, className = '' }: { name: IconName; className?: string }) {
    const shapes: Record<IconName, ReactNode> = {
        arrow: <path d="M5 12h14m-6-6 6 6-6 6" />,
        file: <><path d="M14 3H6a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V9Z" /><path d="M14 3v6h6M8 13h8m-8 4h5" /></>,
        search: <><circle cx="10.5" cy="10.5" r="6.5" /><path d="m16 16 5 5" /></>,
        users: <><circle cx="9" cy="8" r="3" /><path d="M3 21v-3a6 6 0 0 1 12 0v3m1-16a3 3 0 0 1 0 6m3 10v-3a6 6 0 0 0-2-4" /></>,
        check: <path d="m5 12 4 4L19 6" />,
        grid: <><rect x="3" y="3" width="7" height="7" rx="2" /><rect x="14" y="3" width="7" height="7" rx="2" /><rect x="3" y="14" width="7" height="7" rx="2" /><rect x="14" y="14" width="7" height="7" rx="2" /></>,
        spark: <path d="m12 3 2.4 6.6L21 12l-6.6 2.4L12 21l-2.4-6.6L3 12l6.6-2.4ZM20 2v4m-2-2h4" />,
        plus: <path d="M12 5v14M5 12h14" />,
        close: <path d="m6 6 12 12M6 18 18 6" />,
        menu: <path d="M4 6h16M4 12h16M4 18h16" />,
        chevron: <path d="m6 9 6 6 6-6" />,
    };
    return <svg className={`ml-icon ${className}`} viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="1.7" strokeLinecap="round" strokeLinejoin="round" aria-hidden="true">{shapes[name]}</svg>;
}
const money = (value: number) => new Intl.NumberFormat('fr-CM').format(value);
const sampleArticles = [
    { name: 'Papier A4 · ramette de 500 feuilles', category: 'Fournitures de bureau', price: 4500, ref: 'DEMO-001' },
    { name: 'Chaise de bureau', category: 'Mobilier', price: 65000, ref: 'DEMO-002' },
    { name: 'Cartouche d’encre', category: 'Consommables', price: 28500, ref: 'DEMO-003' },
];
const audiences = [
    { name: 'Indépendants', title: 'Votre savoir-faire.\nVotre signature.', description: 'Présentez vos prestations avec des documents soignés, à votre image.', icon: 'file' as IconName, detail: 'Devis & personnalisation' },
    { name: 'Entreprises', title: 'Toute votre activité,\nun même fil conducteur.', description: 'Reliez votre catalogue, vos clients et vos documents. Gardez le cap, ensemble.', icon: 'grid' as IconName, detail: 'Clients & collaboration' },
    { name: 'Fournisseurs', title: 'La bonne référence.\nLa bonne proposition.', description: 'Appuyez vos offres sur la mercuriale et réutilisez vos articles favoris.', icon: 'search' as IconName, detail: 'Mercuriale & favoris' },
];

export default function Landing({ canLogin, canRegister, mercurialeYear, source, plans }: Props) {
    const [menuOpen, setMenuOpen] = useState(false);
    const [preview, setPreview] = useState<'documents' | 'catalogue' | 'clients'>('documents');
    const [query, setQuery] = useState('');
    const [audience, setAudience] = useState(1);
    const [documentType, setDocumentType] = useState('Devis');
    const [selectedArticle, setSelectedArticle] = useState(0);
    const [quantity, setQuantity] = useState(10);
    const article = sampleArticles[selectedArticle];
    const total = article.price * quantity;
    const normalizeSearch = (value: string) => value.toLocaleLowerCase('fr').normalize('NFD').replace(/[\u0300-\u036f]/g, '');
    const filteredArticles = sampleArticles.filter(item =>
        normalizeSearch(`${item.name} ${item.category}`).includes(normalizeSearch(query)),
    );
    const links = [{ href: '#produit', label: 'Le produit' }, { href: '#parcours', label: 'Comment ça marche' }, { href: '#tarifs', label: 'Les offres' }];
    const registerUrl = canRegister ? route('register') : null;
    function StartLink({ children, secondary = false }: { children: ReactNode; secondary?: boolean }) {
        return registerUrl
            ? <Link href={registerUrl} className={`ml-button ${secondary ? 'ml-button-light' : 'ml-button-primary'}`}>{children}<Icon name="arrow" /></Link>
            : <a href={canLogin ? route('login') : '#produit'} className="ml-button ml-button-primary">{canLogin ? 'Se connecter' : 'Explorer Mercuria'}<Icon name="arrow" /></a>;
    }
    return (
        <>
            <Head title="Mercuria — Des références fiables, des projets qui avancent">
                <meta name="description" content={`Découvrez Mercuria : mercuriale ${mercurialeYear}, devis, proformas et factures personnalisés. Une plateforme pensée pour les professionnels au Cameroun.`} />
            </Head>
            <div className="mercuria-landing">
                <a href="#contenu" className="ml-skip">Aller au contenu</a>
                <header className="ml-header">
                    <div className="ml-container ml-nav">
                        <Link href={route('landing')} className="ml-brand" aria-label="Mercuria — Accueil"><ApplicationLogo /><span>Mercuria<span className="ml-brand-dot">.</span></span></Link>
                        <nav className="ml-desktop-nav" aria-label="Navigation principale">{links.map(link => <a key={link.href} href={link.href}>{link.label}</a>)}</nav>
                        <div className="ml-nav-actions">{canLogin && <Link href={route('login')} className="ml-login">Connexion</Link>}<div className="ml-nav-cta"><StartLink>Commencer</StartLink></div><button className="ml-menu-button" aria-label={menuOpen ? 'Fermer le menu' : 'Ouvrir le menu'} aria-expanded={menuOpen} aria-controls="mobile-navigation" onClick={() => setMenuOpen(!menuOpen)}><Icon name={menuOpen ? 'close' : 'menu'} /></button></div>
                    </div>
                    {menuOpen && <nav id="mobile-navigation" className="ml-mobile-nav" aria-label="Navigation mobile">{links.map(link => <a key={link.href} href={link.href} onClick={() => setMenuOpen(false)}>{link.label}<Icon name="arrow" /></a>)}{canLogin && <Link href={route('login')}>Connexion</Link>}<StartLink>Créer mon espace</StartLink></nav>}
                </header>
                <main id="contenu">
                    <section className="ml-hero ml-circuit">
                        <div className="ml-container">
                            <div className="ml-hero-copy">
                                <div className="ml-eyebrow"><span className="ml-status-dot" /> Pensé au Cameroun. Pour vos ambitions.</div>
                                <h1>Moins de paperasse.<br /><span>Plus de perspectives.</span></h1>
                                <p>De la mercuriale à la facture, donnez de l’élan à vos projets.<br className="ml-desktop-break" /> Vos références, vos clients et vos documents. Enfin réunis.</p>
                                <div className="ml-hero-actions"><StartLink>Démarrer gratuitement</StartLink><a className="ml-button ml-button-ghost" href="#produit"><span className="ml-play">▷</span> Découvrir l’interface</a></div>
                                <div className="ml-hero-note"><Icon name="check" /> Une offre gratuite pour commencer <span>·</span> Documents à votre image</div>
                            </div>
                            <div className="ml-product-scene" id="produit">
                                <div className="ml-orbit ml-orbit-one" aria-hidden="true" /><div className="ml-orbit ml-orbit-two" aria-hidden="true" />
                                <div className="ml-float ml-float-reference"><span className="ml-icon-tile"><Icon name="search" /></span><div><small>VOTRE POINT DE DÉPART</small><strong>Mercuriale {mercurialeYear}</strong><span>Des références pour vos offres</span></div></div>
                                <div className="ml-float ml-float-brand"><span className="ml-gold-spark"><Icon name="spark" /></span><span>Votre activité.<br /><strong>Votre signature.</strong></span></div>
                                <div className="ml-app-window">
                                    <div className="ml-window-bar"><div className="ml-window-dots" aria-hidden="true"><i /><i /><i /></div><span><span className="ml-status-dot" /> Votre espace Mercuria</span><span className="ml-demo-label">Démo interactive</span></div>
                                    <div className="ml-preview-layout">
                                        <aside className="ml-preview-sidebar"><div className="ml-preview-brand"><ApplicationLogo /><strong>Mercuria</strong></div><small>MON ESPACE</small>{([{ id: 'documents', label: 'Documents', icon: 'file' }, { id: 'catalogue', label: 'Mercuriale', icon: 'search' }, { id: 'clients', label: 'Clients', icon: 'users' }] as const).map(tab => <button key={tab.id} aria-pressed={preview === tab.id} onClick={() => setPreview(tab.id)}><Icon name={tab.icon} /><span>{tab.label}</span></button>)}<div className="ml-preview-profile"><span>AD</span><div>Atelier Démo<small>Espace illustratif</small></div></div></aside>
                                        <div className="ml-preview-content">
                                            <div className="ml-preview-heading"><div><small>UN ESPACE POUR TOUT RELIER</small><h2>{preview === 'documents' ? 'Vos idées deviennent des offres.' : preview === 'catalogue' ? 'Tout commence par une référence.' : 'Chaque relation compte.'}</h2></div><span className="ml-avatar">AD</span></div>
                                            {preview === 'documents' && <div className="ml-document-preview"><div className="ml-preview-summary"><span className="ml-icon-tile"><Icon name="file" /></span><small>Votre document</small><strong>{documentType}</strong><p>Un format clair.<br />Une présentation qui vous ressemble.</p><div className="ml-preview-format">PDF <span>·</span> XAF <span>·</span> Votre logo</div></div><div className="ml-mini-paper"><div className="ml-paper-top"><ApplicationLogo /><span>{documentType.toUpperCase()}<small>N° DEMO-001</small></span></div><div className="ml-paper-client">À l’attention de<strong>Les Ateliers du Centre</strong></div><div className="ml-paper-row"><span>Désignation</span><span>Montant</span></div><div className="ml-paper-item"><span>{article.name}<small>{quantity} × {money(article.price)} XAF</small></span><strong>{money(total)}</strong></div><div className="ml-paper-total"><span>Total HT</span><strong>{money(total)} <small>XAF</small></strong></div><span className="ml-paper-signature">Merci de votre confiance.</span></div></div>}
                                            {preview === 'catalogue' && <div className="ml-catalogue-preview"><label className="ml-demo-search"><Icon name="search" /><input aria-label="Rechercher dans les articles de démonstration" placeholder="Rechercher un article de démonstration…" value={query} onChange={event => setQuery(event.target.value)} /></label><div className="ml-catalogue-list">{filteredArticles.map(item => <div className="ml-catalogue-row" key={item.ref}><span className="ml-article-icon"><Icon name="grid" /></span><div><strong>{item.name}</strong><small>{item.category} · {item.ref}</small></div><span>{money(item.price)}<small>XAF / unité</small></span><button aria-label={`Ajouter ${item.name} au devis de démonstration`} onClick={() => { setSelectedArticle(sampleArticles.indexOf(item)); setQuantity(1); setPreview('documents'); }}><Icon name="plus" /></button></div>)}{filteredArticles.length === 0 && <p className="ml-empty">Aucun article dans cette démo. Essayez « papier », « chaise » ou « encre ».</p>}</div></div>}
                                            {preview === 'clients' && <div className="ml-client-preview">{[{ initials: 'AC', name: 'Les Ateliers du Centre', detail: 'Douala · Mobilier & fournitures' }, { initials: 'SE', name: 'Studio Équinoxe', detail: 'Yaoundé · Services créatifs' }, { initials: 'MT', name: 'Maison des Territoires', detail: 'Bafoussam · Aménagement' }].map(client => <div className="ml-client-row" key={client.initials}><span className="ml-avatar">{client.initials}</span><div><strong>{client.name}</strong><small>{client.detail}</small></div><span className="ml-client-tag">Fiche client</span></div>)}</div>}
                                            <p className="ml-demo-disclaimer">Aperçu simplifié · Entreprises, articles et montants illustratifs.</p>
                                        </div>
                                    </div>
                                </div>
                                <div className="ml-float ml-float-document"><span className="ml-check-orb"><Icon name="check" /></span><div><strong>De l’idée au document.</strong><span>Devis, proforma, commande, facture.</span></div></div>
                            </div>
                            <div className="ml-source-strip"><span>VOTRE BASE DE RÉFÉRENCE</span><strong>{source}</strong><span>Votre expertise fait le reste.</span></div>
                        </div>
                    </section>

                    <section className="ml-section ml-audience-section" aria-labelledby="audience-title">
                        <div className="ml-container">
                            <div className="ml-section-heading"><span className="ml-eyebrow"><Icon name="grid" /> À CHACUN SA FAÇON D’AVANCER</span><h2 id="audience-title">De grandes ambitions.<br /><span>Un outil à votre mesure.</span></h2><p>À votre compte ou en équipe, donnez de la clarté<br className="ml-desktop-break" /> à chaque étape de votre activité.</p></div>
                            <div className="ml-segmented" aria-label="Choisir votre profil">{audiences.map((item, index) => <button key={item.name} aria-pressed={audience === index} onClick={() => setAudience(index)}><Icon name={item.icon} />{item.name}</button>)}</div>
                            <div className="ml-audience-cards">{audiences.map((item, index) => <article key={item.name} className={`ml-audience-card ${audience === index ? 'is-selected' : ''}`}><div className="ml-audience-face"><span className="ml-icon-orb"><Icon name={item.icon} /></span><span className="ml-card-index">0{index + 1}</span><h3>{item.title.split('\n').map((line, i) => <span key={i}>{line}</span>)}</h3><p>{item.description}</p></div><div className="ml-card-bottom"><span><Icon name="check" />{item.detail}</span><a href="#produit" aria-label={`Explorer Mercuria pour les ${item.name.toLowerCase()}`} onClick={() => setPreview(index === 2 ? 'catalogue' : index === 1 ? 'clients' : 'documents')}><Icon name="arrow" /></a></div></article>)}</div>
                        </div>
                    </section>

                    <section className="ml-section ml-workflow-section ml-circuit" id="parcours">
                        <div className="ml-container ml-workflow-grid"><div className="ml-workflow-copy"><span className="ml-eyebrow"><Icon name="spark" /> DU PREMIER PRIX AU DERNIER DÉTAIL</span><h2>Gardez le fil.<br /><span>Pas les ressaisies.</span></h2><p>Une référence devient une ligne de devis. Votre devis devient une proforma, un bon de commande ou une facture. Votre travail continue, sans repartir de zéro.</p><ol className="ml-steps"><li><span>01</span><div><h3>Trouvez votre référence</h3><p>Explorez la mercuriale et gardez vos articles favoris à portée de main.</p></div></li><li><span>02</span><div><h3>Composez votre proposition</h3><p>Ajoutez vos quantités, votre client et votre identité visuelle.</p></div></li><li><span>03</span><div><h3>Faites avancer le projet</h3><p>Convertissez votre document, exportez-le en PDF ou envoyez-le par email.</p></div></li></ol><a href="#produit" className="ml-text-link" onClick={() => setPreview('catalogue')}>Explorer les références de démonstration <Icon name="arrow" /></a></div>
                            <div className="ml-builder-scene"><div className="ml-builder-halo" aria-hidden="true" /><div className="ml-builder"><div className="ml-builder-heading"><span className="ml-icon-tile"><Icon name="file" /></span><div><h3>À vous de jouer.</h3><p>Un aperçu, sans créer de compte.</p></div><span className="ml-demo-label">Démo</span></div><div className="ml-document-types" aria-label="Type de document de démonstration">{['Devis', 'Proforma', 'Commande', 'Facture'].map(type => <button key={type} aria-pressed={documentType === type} onClick={() => setDocumentType(type)}>{type}</button>)}</div><label className="ml-builder-label" htmlFor="demo-article">Votre article<select id="demo-article" value={selectedArticle} onChange={event => setSelectedArticle(Number(event.target.value))}>{sampleArticles.map((item, index) => <option value={index} key={item.ref}>{item.name}</option>)}</select></label><div className="ml-builder-line"><div><span>Prix unitaire illustratif</span><strong>{money(article.price)} XAF</strong></div><div className="ml-quantity"><span>Quantité</span><div><button aria-label="Diminuer la quantité" disabled={quantity <= 1} onClick={() => setQuantity(Math.max(1, quantity - 1))}>−</button><output aria-label="Quantité">{quantity}</output><button aria-label="Augmenter la quantité" disabled={quantity >= 999} onClick={() => setQuantity(Math.min(999, quantity + 1))}>+</button></div></div></div><div className="ml-builder-total" aria-live="polite" aria-atomic="true"><span>{documentType} · Total HT</span><strong>{money(total)} <small>XAF</small></strong></div><StartLink>Créer mes vrais documents</StartLink><p className="ml-demo-disclaimer">Simulation illustrative, sans enregistrement.<br />Ces prix ne sont pas ceux de la mercuriale officielle.</p></div><div className="ml-builder-caption"><span className="ml-gold-dot" /> Votre logo. Vos couleurs. Votre signature.</div></div>
                        </div>
                    </section>

                    <section className="ml-story-section" id="histoire"><div className="ml-container ml-story-grid"><div className="ml-brand-sculpture" aria-hidden="true"><div className="ml-sculpture-ring" /><div className="ml-sculpture-glass"><ApplicationLogo /></div><span className="ml-sculpture-label">UNE BASE SOLIDE.<br />UN HORIZON OUVERT.</span><i className="ml-sculpture-pearl" /></div><div className="ml-story-copy"><span className="ml-eyebrow">NOTRE CONVICTION</span><h2>Votre énergie mérite<br /><span>de meilleurs projets.</span></h2><p>Pas davantage de paperasse.</p><p>Développé par Traguer AI au Cameroun, Mercuria rapproche les références de prix du travail quotidien des professionnels. Une idée simple : vous aider à passer de l’information à l’action, avec des documents clairs et une activité mieux organisée.</p><div className="ml-story-signature"><span className="ml-status-dot" /> Pensé ici. Pour ce que vous construisez.</div></div></div></section>

                    <section className="ml-section ml-pricing-section" id="tarifs"><div className="ml-container"><div className="ml-section-heading"><span className="ml-eyebrow"><Icon name="spark" /> DE LA PLACE POUR GRANDIR</span><h2>Commencez simplement.<br /><span>Voyez plus grand ensuite.</span></h2><p>Choisissez l’espace qui correspond à votre activité.</p></div><div className="ml-pricing-grid">{[plans.free, plans.pro].filter(Boolean).map(plan => <article className={`ml-plan ${plan.code === 'pro' ? 'ml-plan-pro' : ''}`} key={plan.code}><div className="ml-plan-title"><span className="ml-icon-tile"><Icon name={plan.code === 'pro' ? 'spark' : 'file'} /></span><h3>{plan.name}</h3>{plan.code === 'pro' && <span className="ml-plan-badge">Pour aller plus loin</span>}</div><p>{plan.code === 'pro' ? 'Pour une activité qui prend de l’ampleur.' : 'Pour faire vos premiers pas, à votre rythme.'}</p><div className="ml-plan-price"><strong>{money(plan.price_monthly_xaf ?? 0)}</strong><span>FCFA <small>/ mois</small></span></div><ul><li><Icon name="check" />{plan.documents_per_month === null ? 'Documents illimités' : `${plan.documents_per_month} documents par mois`}</li><li><Icon name="check" />{plan.clients_limit === null ? 'Clients illimités' : `Jusqu’à ${plan.clients_limit} clients`}</li><li><Icon name="check" />Mercuriale, export PDF et envoi email</li><li><Icon name="check" />{plan.code === 'pro' ? 'Personnalisation complète' : 'Personnalisation de base'}</li>{plan.api_access && <li><Icon name="check" />Accès à l’API mercuriale</li>}{plan.team_members !== undefined && <li><Icon name="check" />{plan.team_members === 1 ? 'Un espace individuel' : `Jusqu’à ${plan.team_members} membres dans l’équipe`}</li>}</ul><StartLink secondary={plan.code !== 'pro'}>{plan.code === 'pro' ? 'Commencer, puis passer Pro' : 'Créer mon espace gratuit'}</StartLink></article>)}</div><p className="ml-pricing-note">Les fonctionnalités et limites affichées correspondent aux offres configurées dans Mercuria.</p></div></section>

                    <section className="ml-faq-section ml-container"><div><span className="ml-eyebrow">TOUT SIMPLEMENT</span><h2>Avant de<br /><span>vous lancer.</span></h2></div><div className="ml-faq-list">{[
                        { question: 'À quoi sert la mercuriale dans Mercuria ?', answer: `Elle fournit une base de références pour préparer vos offres. Mercuria s’appuie sur la mercuriale ${mercurialeYear} (${source}). Vous pouvez reprendre un article dans vos documents et adapter vos quantités et prix à votre proposition.` },
                        { question: 'Puis-je utiliser mon logo et mes couleurs ?', answer: 'Oui. Les paramètres de votre espace permettent de personnaliser vos documents avec votre identité : logo, en-tête, pied de page et signature. Le niveau de personnalisation dépend de votre offre.' },
                        { question: 'Est-ce que cette démonstration crée un vrai document ?', answer: 'Non. Les exemples, entreprises et montants de cette page sont illustratifs et ne sont pas enregistrés. Créez votre espace pour travailler avec vos propres clients et documents.' },
                        { question: 'Comment commencer gratuitement ?', answer: `Créez votre compte, configurez votre organisation et préparez votre premier document. L’offre ${plans.free?.name ?? 'Gratuit'} inclut ${plans.free?.documents_per_month ?? 10} documents par mois et jusqu’à ${plans.free?.clients_limit ?? 25} clients.` },
                    ].map(item => <details key={item.question}><summary>{item.question}<Icon name="plus" /></summary><p>{item.answer}</p></details>)}</div></section>

                    <section className="ml-final-section"><div className="ml-container"><div className="ml-final-card"><div className="ml-final-orbit" aria-hidden="true" /><span className="ml-eyebrow">LA SUITE COMMENCE AVEC VOUS</span><h2>Votre prochain projet<br />mérite <span>Mercuria.</span></h2><p>Une bonne référence. Une offre soignée. Et vous, prêt à avancer.</p><StartLink secondary>Créer mon espace gratuit</StartLink></div></div></section>
                </main>
                <footer className="ml-footer ml-container"><Link href={route('landing')} className="ml-brand" aria-label="Mercuria — Accueil"><ApplicationLogo /><span>Mercuria<span className="ml-brand-dot">.</span></span></Link><span>© {new Date().getFullYear()} Mercuria. Pensé au Cameroun.</span><div><a href="#histoire">Notre histoire</a><a href="https://traguer.com" target="_blank" rel="noopener noreferrer">Par Traguer AI <Icon name="arrow" /></a></div></footer>
            </div>
        </>
    );
}
