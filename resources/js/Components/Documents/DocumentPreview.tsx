import { CSSProperties } from 'react';
import '../../../css/documents.css';

export type DocumentDesigns = Record<string, { name: string; description: string; accent: string; paper: string }>;
export type DocumentParty = { name: string; company?: string | null; email?: string | null; phone?: string | null; address?: string | null; tax_id?: string | null; logo_path?: string | null; letterhead?: string | null; footer_text?: string | null; signature_path?: string | null };
export type PreviewDocument = {
    design: string; type: string; currency: string; status?: string; number?: string | null; title?: string | null;
    issue_date?: string | null; due_date?: string | null; client?: DocumentParty | null;
    subtotal: number; tax_rate: number | string; tax_amount: number; ir_rate?: number | string;
    ir_amount?: number; ir_mode?: string; total: number; net_total?: number;
    notes?: string | null; terms?: string | null;
    lines: Array<{ id?: number; reference?: string | null; designation: string; quantity: number | string; unit?: string | null; unit_price: number; line_total: number }>;
};
export const money = (amount: number, currency = 'XAF') => `${new Intl.NumberFormat('fr-FR', { maximumFractionDigits: 0 }).format(amount)} ${currency}`;
const rate = (value: number | string = 0) => new Intl.NumberFormat('fr-FR', { maximumFractionDigits: 2 }).format(Number(value));
const dateLabel = (value?: string | null) => value ? value.slice(0, 10).split('-').reverse().join('/') : '—';
const statusLabels: Record<string, string> = { draft: 'Brouillon', issued: 'Émis', sent: 'Envoyé', converted: 'Converti', cancelled: 'Annulé', paid: 'Payé' };

export function DocumentTotals({ document }: { document: PreviewDocument }) {
    const withholding = document.ir_mode === 'withholding' && Number(document.ir_rate) > 0;
    const addition = document.ir_mode === 'addition' && Number(document.ir_rate) > 0;
    return <dl className="md-totals">
        <div><dt>Total HT</dt><dd>{money(document.subtotal, document.currency)}</dd></div>
        <div><dt>TVA ({rate(document.tax_rate)} %)</dt><dd>{money(document.tax_amount, document.currency)}</dd></div>
        {addition && <div><dt>IR ajouté ({rate(document.ir_rate)} % du HT)</dt><dd>{money(document.ir_amount ?? 0, document.currency)}</dd></div>}
        <div className="md-total-ttc"><dt>Total TTC</dt><dd>{money(document.total, document.currency)}</dd></div>
        {withholding && <>
            <div><dt>Retenue IR ({rate(document.ir_rate)} % du HT)</dt><dd>− {money(document.ir_amount ?? 0, document.currency)}</dd></div>
            <div className="md-net-total"><dt>Net à payer</dt><dd>{money(document.net_total ?? document.total - (document.ir_amount ?? 0), document.currency)}</dd></div>
        </>}
    </dl>;
}

export default function DocumentPreview({ document, tenant, types, designs }: { document: PreviewDocument; tenant?: DocumentParty | null; types: Record<string, string>; designs: DocumentDesigns }) {
    const key = designs[document.design] ? document.design : 'signature';
    const design = designs[key];
    const initials = (tenant?.name ?? 'Mercuria').split(/\s+/).slice(0, 2).map((word) => word[0]).join('').toUpperCase();
    return <article className={`md-sheet md-${key}`} style={{ '--md-accent': design.accent, '--md-paper': design.paper } as CSSProperties} aria-label={`Aperçu du document — ${design.name}`}>
        <header className="md-sheet-header">
            <div className="md-issuer">
                {tenant?.logo_path ? <img className="md-logo" src={`/storage/${tenant.logo_path}`} alt={`Logo ${tenant.name}`} /> : <span className="md-monogram">{initials}</span>}
                <div><h2>{tenant?.name ?? 'Votre entreprise'}</h2><p>{[tenant?.email, tenant?.phone].filter(Boolean).join(' · ')}</p></div>
            </div>
            <div className="md-document-heading"><span className="md-kicker">DOCUMENT COMMERCIAL</span><h3>{types[document.type] ?? 'Document'}</h3><p>{document.number ?? 'Sans numéro'} <span className="md-status">{statusLabels[document.status ?? 'draft'] ?? document.status}</span></p></div>
        </header>
        <div className="md-sheet-body">
            {tenant?.letterhead && <p className="md-letterhead">{tenant.letterhead}</p>}
            <div className="md-details">
                <section><span className="md-kicker">PRÉPARÉ POUR</span><h4>{document.client?.name ?? 'Client à renseigner'}</h4>{document.client?.company && <p>{document.client.company}</p>}{document.client?.address && <p>{document.client.address}</p>}{document.client?.email && <p>{document.client.email}</p>}{document.client?.tax_id && <p>NIU : {document.client.tax_id}</p>}</section>
                <dl className="md-dates"><div><dt>Date d’émission</dt><dd>{dateLabel(document.issue_date)}</dd></div>{document.due_date && <div><dt>Échéance</dt><dd>{dateLabel(document.due_date)}</dd></div>}<div><dt>Devise</dt><dd>{document.currency}</dd></div></dl>
            </div>
            {document.title && <h4 className="md-subject">{document.title}</h4>}
            <div className="md-table-scroll" role="region" aria-label="Articles du document" tabIndex={0}>
                <table className="md-lines">
                    <thead><tr><th>Référence / Désignation</th><th>Quantité</th><th>Prix unitaire HT</th><th>Montant HT</th></tr></thead>
                    <tbody>{document.lines.map((line, index) => <tr key={line.id ?? index}><td>{line.reference && <span className="md-reference">{line.reference}</span>}<span className="md-designation">{line.designation || 'Article à renseigner'}</span></td><td>{new Intl.NumberFormat('fr-FR', { maximumFractionDigits: 3 }).format(Number(line.quantity))}<small>{line.unit}</small></td><td>{money(line.unit_price, document.currency)}</td><td>{money(line.line_total, document.currency)}</td></tr>)}</tbody>
                </table>
            </div>
            <div className="md-summary"><div className="md-note-block">{document.notes && <><h5>À noter</h5><p>{document.notes}</p></>}{document.terms && <><h5>Conditions</h5><p>{document.terms}</p></>}</div><DocumentTotals document={document} /></div>
            {tenant?.signature_path && <div className="md-signature-block"><span>Signature</span><img src={`/storage/${tenant.signature_path}`} alt="Signature de l’émetteur" /></div>}
            <footer className="md-sheet-footer"><p>{tenant?.footer_text || 'Merci pour votre confiance.'}</p><span>Créé avec Mercuria</span></footer>
        </div>
    </article>;
}
