import AppLayout from '@/Layouts/AppLayout';
import DesignPicker from '@/Components/Documents/DesignPicker';
import DocumentWhatsAppShare from '@/Components/Documents/DocumentWhatsAppShare';
import DocumentPreview, { DocumentDesigns } from '@/Components/Documents/DocumentPreview';
import { Head, Link, router, useForm, usePage } from '@inertiajs/react';
import { FormEventHandler, useState } from 'react';

type Props = {
    document: any;
    types: Record<string, string>;
    tenant: any;
    designs: DocumentDesigns;
    emailSender?: { address: string; name: string; reply_to: string };
};

export default function DocumentShow({ document, types, tenant, designs, emailSender }: Props) {
    const { flash, auth } = usePage().props as { flash?: { success?: string; error?: string }; auth?: { user?: { can_write?: boolean } } };
    const designForm = useForm({ design: document.design ?? 'signature' });
    const [showSend, setShowSend] = useState(false);
    const [showTemplate, setShowTemplate] = useState(false);
    const [showWhatsApp, setShowWhatsApp] = useState(false);

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
                        Télécharger PDF
                    </a>
                    {auth?.user?.can_write && <button
                        type="button"
                        onClick={() => { setShowSend(true); setShowWhatsApp(false); }}
                        className="rounded-xl bg-emerald-800 px-4 py-2 text-sm text-white"
                    >
                        Envoyer par email
                    </button>}
                    {auth?.user?.can_write && <button
                        type="button"
                        onClick={() => { setShowWhatsApp(true); setShowSend(false); }}
                        className="rounded-xl border border-emerald-700 bg-emerald-50 px-4 py-2 text-sm font-medium text-emerald-900 hover:bg-emerald-100"
                    >
                        Partager sur WhatsApp
                    </button>}
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

            {auth?.user?.can_write && <details className="mb-6 rounded-2xl border border-slate-200 bg-white p-5">
                <summary className="cursor-pointer text-sm font-semibold text-emerald-800">Changer le design · {designs[designForm.data.design]?.name}</summary>
                <form className="mt-5" onSubmit={(event) => { event.preventDefault(); designForm.patch(route('app.documents.design', document.id), { preserveScroll: true }); }}>
                    <DesignPicker designs={designs} value={designForm.data.design} onChange={(key) => designForm.setData('design', key)} disabled={designForm.processing} />
                    {designForm.errors.design && <p role="alert" className="mt-3 text-sm text-red-700">{designForm.errors.design}</p>}
                    <div className="mt-5 flex flex-wrap items-center justify-end gap-3"><span className="text-xs text-slate-500">L’aperçu se met à jour dès votre sélection.</span><button type="submit" disabled={designForm.processing || designForm.data.design === document.design} className="rounded-lg bg-emerald-800 px-4 py-2 text-sm text-white disabled:opacity-50">{designForm.processing ? 'Enregistrement…' : 'Appliquer ce design au PDF'}</button></div>
                </form>
            </details>}

            {showWhatsApp && auth?.user?.can_write && <DocumentWhatsAppShare key={document.id} documentId={document.id} clientPhone={document.client?.phone} onClose={() => setShowWhatsApp(false)} />}

            {showSend && auth?.user?.can_write && (
                <form
                    onSubmit={send}
                    className="mb-4 space-y-3 rounded-2xl border border-emerald-200 bg-emerald-50 p-4"
                >
                    <h3 className="font-medium">Envoyer le PDF</h3>
                    {emailSender && <div className="rounded-xl bg-white/80 p-3 text-sm text-slate-600">
                        <p className="break-words"><strong>Expéditeur :</strong> {emailSender.name} &lt;{emailSender.address}&gt;</p>
                        <p className="mt-1 break-words"><strong>Les réponses arriveront à :</strong> {emailSender.reply_to}</p>
                        <Link href={route('app.settings.edit')} className="mt-2 inline-block text-xs text-emerald-800 underline underline-offset-4">Modifier l’email de l’entreprise</Link>
                    </div>}
                    <label htmlFor="document-recipient" className="block text-sm font-medium">Email destinataire</label>
                    <input
                        id="document-recipient"
                        type="email"
                        className="w-full rounded-xl border-slate-300 text-sm"
                        placeholder="Email destinataire"
                        value={sendForm.data.email}
                        onChange={(e) => sendForm.setData('email', e.target.value)}
                        required
                    />
                    {sendForm.errors.email && <p role="alert" className="text-sm text-red-700">{sendForm.errors.email}</p>}
                    <label htmlFor="document-email-message" className="block text-sm font-medium">Message (optionnel)</label>
                    <textarea
                        id="document-email-message"
                        className="w-full rounded-xl border-slate-300 text-sm"
                        rows={3}
                        placeholder="Message (optionnel)"
                        value={sendForm.data.message}
                        onChange={(e) => sendForm.setData('message', e.target.value)}
                    />
                    {sendForm.errors.message && <p role="alert" className="text-sm text-red-700">{sendForm.errors.message}</p>}
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

            <DocumentPreview document={{ ...document, design: designForm.data.design }} tenant={tenant} types={types} designs={designs} />
        </AppLayout>
    );
}
