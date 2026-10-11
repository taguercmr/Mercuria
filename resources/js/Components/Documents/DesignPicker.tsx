import { CSSProperties } from 'react';
import { DocumentDesigns } from './DocumentPreview';
import '../../../css/documents.css';

export function DesignThumbnail({ designKey }: { designKey: string }) {
    return <div className={`md-mini md-mini-${designKey}`} aria-hidden="true"><div className="md-mini-heading"><b /><span /></div><div className="md-mini-details"><i /><i /></div><div className="md-mini-table"><b /><i /><i /><i /></div><div className="md-mini-total" /><div className="md-mini-footer" /></div>;
}

export default function DesignPicker({ designs, value, onChange, disabled = false }: { designs: DocumentDesigns; value: string; onChange: (key: string) => void; disabled?: boolean }) {
    return <fieldset disabled={disabled} className="md-design-picker"><legend>Choisir le design du document</legend><p>Le même style vous accompagne à l’écran, en PDF et par email.</p><div className="md-design-grid">{Object.entries(designs).map(([key, design]) => <label key={key} className={`md-design-option ${value === key ? 'is-selected' : ''}`} style={{ '--md-accent': design.accent, '--md-paper': design.paper } as CSSProperties}>
        <input type="radio" name="document-design" value={key} checked={value === key} onChange={() => onChange(key)} />
        <DesignThumbnail designKey={key} /><div className="md-design-name"><strong>{design.name}</strong><span>{value === key ? '✓' : ''}</span></div><p>{design.description}</p>
    </label>)}</div></fieldset>;
}
