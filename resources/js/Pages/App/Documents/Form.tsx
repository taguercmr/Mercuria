import AppLayout from '@/Layouts/AppLayout';
import PrimaryButton from '@/Components/PrimaryButton';
import DesignPicker from '@/Components/Documents/DesignPicker';
import DocumentPreview, { DocumentDesigns, DocumentParty, DocumentTotals, PreviewDocument, money } from '@/Components/Documents/DocumentPreview';
import { Head, Link, useForm } from '@inertiajs/react';
import { FormEventHandler, useEffect, useMemo, useRef, useState } from 'react';

type Line = { mercuriale_article_id?: number | null; reference?: string; designation: string; unit?: string; quantity: number; unit_price: number };
type Article = { id: number; reference: string; designation: string; unit?: string; unit_price: number };
type Props = { document: any | null; type: string; types: Record<string, string>; clients: Array<{ id: number; name: string; company: string | null }>; prefillLines?: Line[]; templates?: Array<{ id: number; name: string; type: string }>; designs: DocumentDesigns; defaultDesign: string; tenant?: DocumentParty | null };
const blankLine = (): Line => ({ designation: '', quantity: 1, unit_price: 0, unit: 'PIECE', reference: '' });
const inputClass = 'mt-1 w-full rounded-lg border-slate-300 text-sm focus:border-emerald-700 focus:ring-emerald-700';

export default function DocumentForm({ document, type, types, clients, prefillLines = [], templates = [], designs, defaultDesign, tenant }: Props) {
    const initialLines: Line[] = document?.lines?.length ? document.lines : prefillLines.length ? prefillLines : [blankLine()];
    const { data, setData, post, put, processing, errors } = useForm({
        type: document?.type ?? type,
        status: document?.status === 'issued' ? 'issued' : 'draft',
        client_id: document?.client_id ?? '',
        currency: document?.currency ?? 'XAF',
        title: document?.title ?? '',
        notes: document?.notes ?? '',
        terms: document?.terms ?? '',
        issue_date: document?.issue_date?.slice(0, 10) ?? new Date().toLocaleDateString('en-CA'),
        due_date: document?.due_date?.slice(0, 10) ?? '',
        tax_rate: Number(document?.tax_rate ?? 0),
        ir_rate: Number(document?.ir_rate ?? 0),
        ir_mode: document?.ir_mode ?? 'none',
        design: document?.design ?? defaultDesign,
        lines: initialLines,
    });
    const [articleQuery, setArticleQuery] = useState('');
    const [suggestions, setSuggestions] = useState<Article[]>([]);
    const [searchError, setSearchError] = useState('');
    const [searching, setSearching] = useState(false);
    const searchController = useRef<AbortController | null>(null);
    useEffect(() => () => searchController.current?.abort(), []);
    const subtotal = useMemo(() => data.lines.reduce((sum, line) => sum + Math.round(Number(line.quantity) * Number(line.unit_price)), 0), [data.lines]);
    const taxAmount = Math.round(subtotal * data.tax_rate / 100);
    const irAmount = data.ir_mode === 'none' ? 0 : Math.round(subtotal * data.ir_rate / 100);
    const total = subtotal + taxAmount + (data.ir_mode === 'addition' ? irAmount : 0);
    const preview: PreviewDocument = {
        ...data, number: document?.number, client: clients.find((client) => client.id === Number(data.client_id)),
        subtotal, tax_amount: taxAmount, ir_amount: irAmount, total, net_total: total - (data.ir_mode === 'withholding' ? irAmount : 0),
        lines: data.lines.map((line) => ({ ...line, line_total: Math.round(Number(line.quantity) * Number(line.unit_price)) })),
    };
    const searchArticles = async (query: string) => {
        setArticleQuery(query);
        searchController.current?.abort();
        setSearchError('');
        setSuggestions([]);
        if (query.trim().length < 2) { setSearching(false); return; }
        const controller = new AbortController();
        searchController.current = controller;
        setSearching(true);
        try {
            const response = await fetch(route('app.documents.articles.search', { q: query }), { signal: controller.signal });
            if (!response.ok) throw new Error('search failed');
            setSuggestions(await response.json());
        } catch (error) {
            if (!controller.signal.aborted) setSearchError('La recherche est indisponible. Vous pouvez saisir une ligne manuellement.');
        } finally { if (!controller.signal.aborted) setSearching(false); }
    };
    const addArticle = (article: Article) => {
        const lines = data.lines.filter((line) => line.designation.trim() || Number(line.unit_price) !== 0);
        setData('lines', [...lines, { mercuriale_article_id: article.id, reference: article.reference, designation: article.designation, unit: article.unit ?? 'PIECE', quantity: 1, unit_price: article.unit_price }]);
        setSuggestions([]); setArticleQuery('');
    };
    const updateLine = (index: number, key: keyof Line, value: string | number) => setData('lines', data.lines.map((line, lineIndex) => lineIndex === index ? { ...line, [key]: value } : line));
    const submit: FormEventHandler = (event) => {
        event.preventDefault();
        if (document?.id) put(route('app.documents.update', document.id));
        else post(route('app.documents.store'));
    };
    return <AppLayout title={document?.id ? 'Modifier le document' : `Nouveau ${types[data.type]?.toLowerCase() ?? 'document'}`}>
        <Head title="Composer mon document" />
        <form onSubmit={submit} className="space-y-6" aria-busy={processing}>
            <div className="flex flex-wrap items-end justify-between gap-3"><div><p className="text-xs font-semibold uppercase tracking-widest text-emerald-700">Votre savoir-faire, bien présenté</p><h2 className="mt-2 text-2xl font-semibold text-slate-900">Un document à votre image.</h2><p className="mt-2 text-sm text-slate-500">Choisissez un design, ajoutez vos articles et laissez Mercuria faire les calculs.</p></div><Link className="text-sm text-emerald-800 underline underline-offset-4" href={route('app.documents.index')}>Mes documents</Link></div>
            {Object.keys(errors).length > 0 && <div role="alert" className="rounded-xl border border-red-200 bg-red-50 p-4 text-sm text-red-800"><strong>Vérifiez les informations suivantes :</strong><ul className="mt-2 list-inside list-disc">{Object.entries(errors).map(([key, error]) => <li key={key}>{String(error)}</li>)}</ul></div>}
            {templates.length > 0 && !document?.id && <div className="flex flex-wrap items-center gap-2 rounded-xl border border-slate-200 bg-white p-4 text-sm"><span className="text-slate-500">Mes modèles enregistrés :</span>{templates.map((template) => <Link key={template.id} href={route('app.documents.create', { template: template.id })} className="rounded-full bg-slate-100 px-3 py-1 hover:bg-emerald-50">{template.name}</Link>)}</div>}
            <section className="rounded-2xl border border-slate-200 bg-white p-5 md:p-6"><DesignPicker designs={designs} value={data.design} onChange={(key) => setData('design', key)} /></section>
            <section className="rounded-2xl border border-slate-200 bg-white p-5 md:p-6"><h3 className="mb-5 font-semibold">Les informations du document</h3><div className="grid gap-4 md:grid-cols-3">
                <label className="text-xs font-medium text-slate-600">Type<select aria-label="Type de document" className={inputClass} value={data.type} onChange={(event) => setData('type', event.target.value)}>{Object.entries(types).map(([key, label]) => <option key={key} value={key}>{label}</option>)}</select></label>
                <label className="text-xs font-medium text-slate-600">Client<select aria-label="Client" className={inputClass} value={data.client_id} onChange={(event) => setData('client_id', event.target.value)}><option value="">Sélectionner un client</option>{clients.map((client) => <option key={client.id} value={client.id}>{client.name}</option>)}</select></label>
                <label className="text-xs font-medium text-slate-600">Statut<select aria-label="Statut du document" className={inputClass} value={data.status} onChange={(event) => setData('status', event.target.value)}><option value="draft">Brouillon</option><option value="issued">Émis</option></select></label>
                <label className="text-xs font-medium text-slate-600">Objet<input className={inputClass} value={data.title} maxLength={255} placeholder="Ex. Fourniture de matériel informatique" onChange={(event) => setData('title', event.target.value)} /></label>
                <label className="text-xs font-medium text-slate-600">Date d’émission<input type="date" className={inputClass} value={data.issue_date} onChange={(event) => setData('issue_date', event.target.value)} required /></label>
                <label className="text-xs font-medium text-slate-600">Échéance<input type="date" className={inputClass} value={data.due_date} onChange={(event) => setData('due_date', event.target.value)} /></label>
            </div></section>
            <section className="rounded-2xl border border-slate-200 bg-white p-5 md:p-6"><div className="mb-4 flex flex-wrap items-center justify-between gap-3"><h3 className="font-semibold">Articles & prestations</h3><span className="text-xs text-slate-500">Prix unitaires hors taxes · {data.currency}</span></div>
                <label className="text-sm text-slate-600">Rechercher dans la mercuriale<input value={articleQuery} onChange={(event) => searchArticles(event.target.value)} placeholder="Référence ou désignation de l’article…" className={inputClass} /></label>
                {searching && <p role="status" className="mt-2 text-xs text-slate-500">Recherche en cours…</p>}{searchError && <p role="alert" className="mt-2 text-xs text-red-700">{searchError}</p>}
                {suggestions.length > 0 && <div className="mt-2 max-h-52 overflow-auto rounded-lg border border-slate-200">{suggestions.map((article) => <button type="button" key={article.id} onClick={() => addArticle(article)} className="block w-full border-b border-slate-100 px-3 py-3 text-left text-sm hover:bg-emerald-50"><span className="mr-2 font-mono text-xs text-emerald-800">{article.reference}</span>{article.designation.slice(0, 110)}<span className="ml-2 font-semibold">{money(article.unit_price, data.currency)}</span></button>)}</div>}
                <div className="mt-5 space-y-4">{data.lines.map((line, index) => <fieldset key={index} className="rounded-xl border border-slate-200 bg-slate-50/50 p-4"><legend className="px-2 text-xs font-semibold text-emerald-800">Article {index + 1}</legend><div className="grid gap-3 md:grid-cols-12">
                    <label className="text-xs text-slate-600 md:col-span-3">Référence<input className={inputClass} value={line.reference ?? ''} maxLength={20} onChange={(event) => updateLine(index, 'reference', event.target.value)} /></label>
                    <label className="text-xs text-slate-600 md:col-span-9">Désignation<textarea className={inputClass} rows={2} value={line.designation} maxLength={5000} onChange={(event) => updateLine(index, 'designation', event.target.value)} required /></label>
                    <label className="text-xs text-slate-600 md:col-span-2">Quantité<input type="number" min="0.001" step="0.001" className={inputClass} value={line.quantity} onChange={(event) => updateLine(index, 'quantity', Number(event.target.value))} required /></label>
                    <label className="text-xs text-slate-600 md:col-span-2">Unité<input className={inputClass} value={line.unit ?? ''} maxLength={64} onChange={(event) => updateLine(index, 'unit', event.target.value)} /></label>
                    <label className="text-xs text-slate-600 md:col-span-3">Prix unitaire HT<input type="number" min="0" step="1" className={inputClass} value={line.unit_price} onChange={(event) => updateLine(index, 'unit_price', Number(event.target.value))} required /></label>
                    <div className="flex items-center justify-between gap-3 md:col-span-5"><p className="text-sm font-semibold">{money(Math.round(Number(line.quantity) * Number(line.unit_price)), data.currency)}</p><button type="button" onClick={() => setData('lines', data.lines.filter((_, lineIndex) => lineIndex !== index))} className="text-xs text-red-700 underline underline-offset-4" aria-label={`Retirer l’article ${index + 1}`}>Retirer</button></div>
                </div></fieldset>)}</div>
                <button type="button" onClick={() => setData('lines', [...data.lines, blankLine()])} className="mt-4 rounded-lg border border-emerald-700 px-4 py-2 text-sm font-medium text-emerald-800 hover:bg-emerald-50">+ Ajouter une ligne libre</button>
            </section>
            <section className="rounded-2xl border border-slate-200 bg-white p-5 md:p-6"><h3 className="font-semibold">Taxes & total</h3><p className="mt-1 text-xs text-slate-500">Les taux s’appliquent au total HT. Les montants sont arrondis à l’unité de {data.currency}.</p><div className="mt-5 grid items-start gap-6 lg:grid-cols-2"><div className="space-y-5">
                <div className="rounded-xl border border-slate-200 p-4"><label className="flex items-center gap-3 text-sm font-medium"><input type="checkbox" className="rounded-sm border-slate-300 text-emerald-700 focus:ring-emerald-700" checked={data.tax_rate > 0} onChange={(event) => setData('tax_rate', event.target.checked ? 19.25 : 0)} />Appliquer la TVA (19,25 %)</label>{data.tax_rate > 0 && <label className="mt-3 block text-xs text-slate-600">Taux TVA (%)<input type="number" min="0" max="100" step="0.01" className={inputClass} value={data.tax_rate} onChange={(event) => setData('tax_rate', Number(event.target.value))} /></label>}</div>
                <div className="rounded-xl border border-slate-200 p-4"><label className="text-sm font-medium">Traitement de l’IR<select aria-label="Traitement de l’IR" className={inputClass} value={data.ir_mode} onChange={(event) => setData((current) => ({ ...current, ir_mode: event.target.value, ir_rate: event.target.value === 'none' ? 0 : current.ir_rate || 5.5 }))}><option value="none">Ne pas appliquer l’IR</option><option value="withholding">Retenue IR : déduire du TTC</option><option value="addition">IR ajouté au montant HT</option></select></label>{data.ir_mode !== 'none' && <><label className="mt-3 block text-xs text-slate-600">Taux IR (%)<input type="number" min="0" max="100" step="0.01" className={inputClass} value={data.ir_rate} onChange={(event) => setData('ir_rate', Number(event.target.value))} /></label><p className="mt-3 text-xs leading-relaxed text-slate-500">{data.ir_mode === 'withholding' ? 'IR = HT × taux IR. Net à payer = TTC − IR.' : 'IR = HT × taux IR. Total TTC = HT + TVA + IR.'}</p></>}</div>
            </div><div className="md-editor-summary"><DocumentTotals document={preview} /></div></div></section>
            <section className="grid gap-5 rounded-2xl border border-slate-200 bg-white p-5 md:grid-cols-2 md:p-6"><label className="text-sm font-medium">Notes<textarea className={inputClass} rows={3} maxLength={5000} value={data.notes} onChange={(event) => setData('notes', event.target.value)} placeholder="Informations utiles pour votre client…" /></label><label className="text-sm font-medium">Conditions<textarea className={inputClass} rows={3} maxLength={5000} value={data.terms} onChange={(event) => setData('terms', event.target.value)} placeholder="Délais, modalités de règlement…" /></label></section>
            <details className="rounded-2xl border border-slate-200 bg-white p-4 md:p-6"><summary className="cursor-pointer text-sm font-semibold text-emerald-800">Voir l’aperçu de mon document</summary><div className="mt-5"><DocumentPreview document={preview} tenant={tenant} types={types} designs={designs} /></div></details>
            <div className="flex flex-wrap items-center justify-end gap-4"><span className="text-sm text-slate-500">Design {designs[data.design]?.name} · {money(preview.net_total ?? total, data.currency)}</span><PrimaryButton disabled={processing || data.lines.length === 0}>{processing ? 'Enregistrement…' : 'Enregistrer mon document'}</PrimaryButton></div>
        </form>
    </AppLayout>;
}
