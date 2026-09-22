import AppLayout from '@/Layouts/AppLayout';
import { Head, Link, router, useForm, usePage } from '@inertiajs/react';
import { FormEventHandler, useState } from 'react';

type Props = {
    document: any;
    types: Record<string, string>;
    tenant: any;
};

function money(n: number, currency = 'XAF') {
    return `${new Intl.NumberFormat('fr-FR').format(n)} ${currency}`;
}

export default function DocumentShow({ document, types, tenant }: Props) {
    const { flash } = usePage().props as { flash?: { success?: string; error?: string } };
    const [showSend, setShowSend] = useState(false);
    const [showTemplate, setShowTemplate] = useState(false);

    const sendForm = useForm({
        email: document.client?.email ?? '',
        message: '',
    });
    const templateForm = useForm({
        name: `${types[document.type] ?? 'Document'} type`,
    });

    const convertTo = (to: string) => {
        router.post(route('app.documents.convert', document.id), { to_type: to });
    };

    const send: FormEventHandler = (e) => {
        e.preventDefault();
        sendForm.post(route('app.documents.send', document.id), {
            onSuccess: () => setShowSend(false),
        });
    };

    const saveTemplate: FormEventHandler = (e) => {
        e.preventDefault();
        templateForm.post(route('app.documents.template', document.id), {
            onSuccess: () => setShowTemplate(false),
        });
    };

    return (
        <AppLayout title={types[document.type] ?? 'Document'}>
            <Head title={document.number ?? 'Document'} />
            {(flash?.success || flash?.error) && (
                <div
                    className={`mb-4 rounded-xl px-4 py-3 text-sm ${flash.error ? 'bg-red-50 text-red-700' : 'bg-emerald-50 text-emerald-800'}`}
                >
                    {flash.error || flash.success}
                </div>
            )}

            <div className="mb-4 flex flex-wrap items-center justify-between gap-3">
                <div>
                    <div className="text-sm text-slate-500">{types[document.type]}</div>
                    <h2 className="text-2xl font-semibold">{document.number ?? 'Brouillon'}</h2>
                </div>
                <div className="flex flex-wrap gap-2">
                    <Link
                        href={route('app.documents.edit', document.id)}
                        className="rounded-xl border border-slate-300 bg-white px-4 py-2 text-sm"
                    >
                        Modifier
                    </Link>
                    <button
                        type="button"
                        onClick={() => router.post(route('app.documents.duplicate', document.id))}
                        className="rounded-xl border border-slate-300 bg-white px-4 py-2 text-sm"
                    >
                        Dupliquer
                    </button>
                    <a
                        href={route('app.documents.pdf', document.id)}
                        className="rounded-xl bg-slate-900 px-4 py-2 text-sm text-white"
                    >
                        PDF
                    </a>
                    <button
                        type="button"
                        onClick={() => setShowSend(true)}
                        className="rounded-xl bg-emerald-800 px-4 py-2 text-sm text-white"
                    >
                        Envoyer par email
                    </button>
                    <button
                        type="button"
                        onClick={() => setShowTemplate(true)}
                        className="rounded-xl border border-emerald-800 px-4 py-2 text-sm text-emerald-900"
                    >
                        Sauver comme modele
                    </button>
                    {document.type === 'quote' && (
                        <>
                            <button
                                onClick={() => convertTo('proforma')}
                                className="rounded-xl bg-slate-800 px-4 py-2 text-sm text-white"
                            >
                                → Proforma
                            </button>
                            <button
                                onClick={() => convertTo('invoice')}
                                className="rounded-xl bg-slate-800 px-4 py-2 text-sm text-white"
                            >
                                → Facture
                            </button>
                        </>
                    )}
                    {document.type === 'proforma' && (
                        <button
                            onClick={() => convertTo('purchase_order')}
                            className="rounded-xl bg-slate-800 px-4 py-2 text-sm text-white"
                        >
                            → BC
                        </button>
                    )}
                    {document.type === 'purchase_order' && (
                        <button
                            onClick={() => convertTo('invoice')}
                            className="rounded-xl bg-slate-800 px-4 py-2 text-sm text-white"
                        >
                            → Facture
                        </button>
                    )}
                </div>
            </div>

            {showSend && (
                <form
                    onSubmit={send}
                    className="mb-4 space-y-3 rounded-2xl border border-emerald-200 bg-emerald-50 p-4"
                >
                    <h3 className="font-medium">Envoyer le PDF</h3>
                    <input
                        type="email"
                        className="w-full rounded-xl border-slate-300 text-sm"
                        placeholder="Email destinataire"
                        value={sendForm.data.email}
                        onChange={(e) => sendForm.setData('email', e.target.value)}
                        required
                    />
                    <textarea
                        className="w-full rounded-xl border-slate-300 text-sm"
                        rows={3}
                        placeholder="Message (optionnel)"
                        value={sendForm.data.message}
                        onChange={(e) => sendForm.setData('message', e.target.value)}
                    />
                    <div className="flex gap-2">
                        <button
                            disabled={sendForm.processing}
                            className="rounded-xl bg-emerald-800 px-4 py-2 text-sm text-white"
                        >
                            Envoyer
                        </button>
                        <button
                            type="button"
                            onClick={() => setShowSend(false)}
                            className="rounded-xl px-4 py-2 text-sm text-slate-600"
                        >
                            Annuler
                        </button>
                    </div>
                </form>
            )}

            {showTemplate && (
                <form
                    onSubmit={saveTemplate}
                    className="mb-4 flex flex-wrap items-end gap-3 rounded-2xl border border-slate-200 bg-white p-4"
                >
                    <div className="flex-1">
                        <label className="text-xs text-slate-500">Nom du modele</label>
                        <input
                            className="mt-1 w-full rounded-xl border-slate-300 text-sm"
                            value={templateForm.data.name}
                            onChange={(e) => templateForm.setData('name', e.target.value)}
                            required
                        />
                    </div>
                    <button
                        disabled={templateForm.processing}
                        className="rounded-xl bg-emerald-800 px-4 py-2 text-sm text-white"
                    >
                        Enregistrer
                    </button>
                    <button
                        type="button"
                        onClick={() => setShowTemplate(false)}
                        className="rounded-xl px-4 py-2 text-sm text-slate-600"
                    >
                        Annuler
                    </button>
                </form>
            )}

            <div className="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
                <div className="mb-6 grid gap-4 md:grid-cols-2">
                    <div>
                        <div className="text-lg font-semibold text-emerald-800">
                            {tenant?.name ?? 'Mercuria'}
                        </div>
                        {tenant?.letterhead && (
                            <p className="mt-2 whitespace-pre-wrap text-sm text-slate-600">
                                {tenant.letterhead}
                            </p>
                        )}
                    </div>
                    <div className="text-sm text-slate-600 md:text-right">
                        <div>Client : {document.client?.name ?? '—'}</div>
                        <div>Statut : {document.status}</div>
                        <div>Date : {document.issue_date ?? '—'}</div>
                    </div>
                </div>

                <table className="min-w-full text-sm">
                    <thead className="border-y border-slate-200 text-left text-xs uppercase text-slate-500">
                        <tr>
                            <th className="py-2">Ref</th>
                            <th className="py-2">Designation</th>
                            <th className="py-2">Qte</th>
                            <th className="py-2">P.U.</th>
                            <th className="py-2 text-right">Total</th>
                        </tr>
                    </thead>
                    <tbody>
                        {document.lines?.map((l: any) => (
                            <tr key={l.id} className="border-b border-slate-100">
                                <td className="py-2 font-mono text-xs">{l.reference}</td>
                                <td className="py-2">{l.designation}</td>
                                <td className="py-2">
                                    {l.quantity} {l.unit}
                                </td>
                                <td className="py-2">{money(l.unit_price, document.currency)}</td>
                                <td className="py-2 text-right">
                                    {money(l.line_total, document.currency)}
                                </td>
                            </tr>
                        ))}
                    </tbody>
                </table>

                <div className="mt-6 text-right">
                    <div className="text-sm text-slate-500">
                        Sous-total : {money(document.subtotal, document.currency)}
                    </div>
                    <div className="text-xl font-semibold">
                        Total : {money(document.total, document.currency)}
                    </div>
                </div>

                {tenant?.footer_text && (
                    <p className="mt-8 border-t border-slate-100 pt-4 text-xs text-slate-500">
                        {tenant.footer_text}
                    </p>
                )}
            </div>
        </AppLayout>
    );
}
