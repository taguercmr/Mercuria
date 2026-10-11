import AppLayout from '@/Layouts/AppLayout';
import { DesignThumbnail } from '@/Components/Documents/DesignPicker';
import { DocumentDesigns } from '@/Components/Documents/DocumentPreview';
import { Head, Link, router, usePage } from '@inertiajs/react';
import { CSSProperties } from 'react';

type Template = { id: number; name: string; type: string; title?: string; lines: unknown[]; design: string; tax_rate: string; ir_rate: string; ir_mode: string };

export default function TemplatesIndex({ templates, types, designs }: { templates: Template[]; types: Record<string, string>; designs: DocumentDesigns }) {
    const { auth } = usePage().props as { auth?: { user?: { can_write?: boolean } } };
    return <AppLayout title="Modèles"><Head title="Designs & modèles de documents" />
        <div className="mb-7"><p className="text-xs font-semibold uppercase tracking-widest text-emerald-700">La forme au service de vos idées</p><h2 className="mt-2 text-2xl font-semibold">Cinq façons de faire bonne impression.</h2><p className="mt-2 text-sm text-slate-500">Des designs prêts pour vos devis, proformas, bons de commande et factures. Votre logo et vos informations prennent place automatiquement.</p></div>
        <div className="md-design-grid">{Object.entries(designs).map(([key, design]) => <section key={key} className="rounded-xl border border-slate-200 bg-white p-4" style={{ '--md-accent': design.accent, '--md-paper': design.paper } as CSSProperties}>
            <DesignThumbnail designKey={key} /><h3 className="mt-4 font-semibold">{design.name}</h3><p className="mt-2 text-xs leading-relaxed text-slate-500">{design.description}</p>{auth?.user?.can_write && <Link href={route('app.documents.create', { design: key })} className="mt-4 inline-block rounded-lg bg-emerald-800 px-3 py-2 text-xs font-semibold text-white">Utiliser {design.name}</Link>}
        </section>)}</div>
        <section className="mt-10"><h2 className="text-lg font-semibold">Mes modèles enregistrés</h2><p className="mb-5 mt-2 text-sm text-slate-500">Retrouvez vos articles, vos taux et votre design. Enregistrez un modèle depuis la fiche d’un document.</p><div className="grid gap-4 md:grid-cols-2">
            {templates.length === 0 && <div className="rounded-2xl border border-dashed border-slate-300 p-8 text-sm text-slate-500 md:col-span-2">Vos modèles apparaîtront ici après votre première sauvegarde.</div>}
            {templates.map((template) => <div key={template.id} className="rounded-2xl border border-slate-200 bg-white p-5"><div className="flex items-start justify-between gap-3"><div><h3 className="font-semibold">{template.name}</h3><p className="mt-1 text-xs text-slate-500">{types[template.type]} · {template.lines?.length ?? 0} articles · {designs[template.design]?.name ?? 'Signature'}</p><p className="mt-2 text-xs text-slate-500">TVA {Number(template.tax_rate).toLocaleString('fr-FR')} %{template.ir_mode !== 'none' && ` · IR ${Number(template.ir_rate).toLocaleString('fr-FR')} % (${template.ir_mode === 'withholding' ? 'retenue' : 'ajout'})`}</p></div>{auth?.user?.can_write && <button type="button" className="text-xs text-red-700" onClick={() => router.delete(route('app.templates.destroy', template.id))}>Supprimer</button>}</div>{auth?.user?.can_write && <Link href={route('app.documents.create', { template: template.id })} className="mt-4 inline-block rounded-lg border border-emerald-800 px-4 py-2 text-sm text-emerald-900">Utiliser ce modèle</Link>}</div>)}
        </div></section>
    </AppLayout>;
}
