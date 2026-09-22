import AppLayout from '@/Layouts/AppLayout';
import PrimaryButton from '@/Components/PrimaryButton';
import { Head, useForm } from '@inertiajs/react';
import { FormEventHandler, useMemo, useState } from 'react';

type Line = {
    mercuriale_article_id?: number | null;
    reference?: string;
    designation: string;
    unit?: string;
    quantity: number;
    unit_price: number;
};

type Props = {
    document: any | null;
    type: string;
    types: Record<string, string>;
    clients: Array<{ id: number; name: string; company: string | null }>;
    prefillLines?: Line[];
    templates?: Array<{ id: number; name: string; type: string }>;
};

export default function DocumentForm({
    document,
    type,
    types,
    clients,
    prefillLines = [],
    templates = [],
}: Props) {
    const initialLines =
        (document?.lines as Line[])?.length > 0
            ? (document.lines as Line[])
            : prefillLines.length > 0
              ? prefillLines
              : [{ designation: '', quantity: 1, unit_price: 0, unit: 'PIECE', reference: '' }];

    const { data, setData, post, put, processing, errors } = useForm({
        type: document?.type ?? type,
        status: document?.status === 'issued' ? 'issued' : 'draft',
        client_id: document?.client_id ?? '',
        currency: document?.currency ?? 'XAF',
        title: document?.title ?? '',
        notes: document?.notes ?? '',
        terms: document?.terms ?? '',
        issue_date: document?.issue_date ?? new Date().toISOString().slice(0, 10),
        due_date: document?.due_date ?? '',
        tax_rate: document?.tax_rate ?? 0,
        lines: initialLines,
    });

    const [articleQuery, setArticleQuery] = useState('');
    const [suggestions, setSuggestions] = useState<any[]>([]);

    const subtotal = useMemo(
        () =>
            data.lines.reduce(
                (sum, l) => sum + Math.round(Number(l.quantity) * Number(l.unit_price)),
                0,
            ),
        [data.lines],
    );

    const searchArticles = async (q: string) => {
        setArticleQuery(q);
        if (q.length < 2) {
            setSuggestions([]);
            return;
        }
        const res = await fetch(route('app.documents.articles.search', { q }));
        setSuggestions(await res.json());
    };

    const addArticle = (article: any) => {
        setData('lines', [
            ...data.lines,
            {
                mercuriale_article_id: article.id,
                reference: article.reference,
                designation: article.designation,
                unit: article.unit ?? 'PIECE',
                quantity: 1,
                unit_price: article.unit_price,
            },
        ]);
        setSuggestions([]);
        setArticleQuery('');
    };

    const updateLine = (index: number, key: keyof Line, value: string | number) => {
        const lines = [...data.lines];
        lines[index] = { ...lines[index], [key]: value };
        setData('lines', lines);
    };

    const removeLine = (index: number) => {
        setData(
            'lines',
            data.lines.filter((_, i) => i !== index),
        );
    };

    const submit: FormEventHandler = (e) => {
        e.preventDefault();
        if (document?.id) {
            put(route('app.documents.update', document.id));
        } else {
            post(route('app.documents.store'));
        }
    };

    return (
        <AppLayout title={document ? 'Modifier document' : `Nouveau ${types[data.type] ?? 'document'}`}>
            <Head title="Document" />
            <form onSubmit={submit} className="space-y-6">
                {templates.length > 0 && !document?.id && (
                    <div className="rounded-2xl border border-slate-200 bg-white p-4 text-sm">
                        <span className="mr-2 text-slate-500">Partir d'un modele :</span>
                        {templates.map((t) => (
                            <a
                                key={t.id}
                                href={route('app.documents.create', { template: t.id })}
                                className="mr-2 inline-block rounded-full bg-slate-100 px-3 py-1 hover:bg-emerald-50"
                            >
                                {t.name}
                            </a>
                        ))}
                    </div>
                )}
                <div className="grid gap-4 rounded-2xl border border-slate-200 bg-white p-5 md:grid-cols-3">
                    <div>
                        <label className="text-xs font-medium text-slate-500">Type</label>
                        <select
                            className="mt-1 w-full rounded-xl border-slate-300 text-sm"
                            value={data.type}
                            onChange={(e) => setData('type', e.target.value)}
                        >
                            {Object.entries(types).map(([k, v]) => (
                                <option key={k} value={k}>
                                    {v}
                                </option>
                            ))}
                        </select>
                    </div>
                    <div>
                        <label className="text-xs font-medium text-slate-500">Client</label>
                        <select
                            className="mt-1 w-full rounded-xl border-slate-300 text-sm"
                            value={data.client_id}
                            onChange={(e) => setData('client_id', e.target.value)}
                        >
                            <option value="">—</option>
                            {clients.map((c) => (
                                <option key={c.id} value={c.id}>
                                    {c.name}
                                </option>
                            ))}
                        </select>
                    </div>
                    <div>
                        <label className="text-xs font-medium text-slate-500">Statut</label>
                        <select
                            className="mt-1 w-full rounded-xl border-slate-300 text-sm"
                            value={data.status}
                            onChange={(e) => setData('status', e.target.value)}
                        >
                            <option value="draft">Brouillon</option>
                            <option value="issued">Emis</option>
                        </select>
                    </div>
                </div>

                <div className="rounded-2xl border border-slate-200 bg-white p-5">
                    <label className="text-sm font-medium">Ajouter depuis la mercuriale</label>
                    <input
                        value={articleQuery}
                        onChange={(e) => searchArticles(e.target.value)}
                        placeholder="Tapez une reference ou designation..."
                        className="mt-2 w-full rounded-xl border border-slate-300 px-3 py-2 text-sm"
                    />
                    {suggestions.length > 0 && (
                        <div className="mt-2 max-h-48 overflow-auto rounded-xl border border-slate-200">
                            {suggestions.map((s) => (
                                <button
                                    type="button"
                                    key={s.id}
                                    onClick={() => addArticle(s)}
                                    className="block w-full border-b border-slate-100 px-3 py-2 text-left text-sm hover:bg-emerald-50"
                                >
                                    <span className="font-mono text-xs">{s.reference}</span> —{' '}
                                    {s.designation.slice(0, 80)}
                                </button>
                            ))}
                        </div>
                    )}

                    <div className="mt-4 space-y-3">
                        {data.lines.map((line, index) => (
                            <div
                                key={index}
                                className="grid gap-2 rounded-xl border border-slate-100 p-3 md:grid-cols-12"
                            >
                                <input
                                    className="md:col-span-2 rounded border-slate-300 text-xs"
                                    placeholder="Ref"
                                    value={line.reference ?? ''}
                                    onChange={(e) => updateLine(index, 'reference', e.target.value)}
                                />
                                <input
                                    className="md:col-span-5 rounded border-slate-300 text-sm"
                                    placeholder="Designation"
                                    value={line.designation}
                                    onChange={(e) => updateLine(index, 'designation', e.target.value)}
                                    required
                                />
                                <input
                                    type="number"
                                    step="0.001"
                                    className="md:col-span-1 rounded border-slate-300 text-sm"
                                    value={line.quantity}
                                    onChange={(e) => updateLine(index, 'quantity', Number(e.target.value))}
                                />
                                <input
                                    className="md:col-span-1 rounded border-slate-300 text-sm"
                                    value={line.unit ?? ''}
                                    onChange={(e) => updateLine(index, 'unit', e.target.value)}
                                />
                                <input
                                    type="number"
                                    className="md:col-span-2 rounded border-slate-300 text-sm"
                                    value={line.unit_price}
                                    onChange={(e) =>
                                        updateLine(index, 'unit_price', Number(e.target.value))
                                    }
                                />
                                <button
                                    type="button"
                                    onClick={() => removeLine(index)}
                                    className="md:col-span-1 text-xs text-red-600"
                                >
                                    Retirer
                                </button>
                            </div>
                        ))}
                    </div>
                    {errors.lines && <p className="mt-2 text-sm text-red-600">{String(errors.lines)}</p>}
                    <div className="mt-4 text-right text-sm font-semibold">
                        Sous-total : {new Intl.NumberFormat('fr-FR').format(subtotal)} XAF
                    </div>
                </div>

                <div className="flex justify-end gap-3">
                    <PrimaryButton disabled={processing}>Enregistrer</PrimaryButton>
                </div>
            </form>
        </AppLayout>
    );
}
