import axios from 'axios';
import { FormEventHandler, useState } from 'react';

type ShareResult = {
    whatsapp_url: string;
    message: string;
    pdf_url: string | null;
    expires_at: string | null;
    requires_pdf_attachment: boolean;
};

type Props = { documentId: number; clientPhone?: string | null; onClose: () => void };

export default function DocumentWhatsAppShare({ documentId, clientPhone, onClose }: Props) {
    const [phone, setPhone] = useState(clientPhone ?? '');
    const [message, setMessage] = useState('');
    const [processing, setProcessing] = useState(false);
    const [errors, setErrors] = useState<Record<string, string>>({});
    const [share, setShare] = useState<ShareResult | null>(null);

    const prepare: FormEventHandler = async (event) => {
        event.preventDefault();
        setProcessing(true);
        setErrors({});
        setShare(null);

        try {
            const response = await axios.post<ShareResult>(route('app.documents.whatsapp', documentId), { phone, message });
            setShare(response.data);
        } catch (error) {
            if (axios.isAxiosError(error) && error.response?.status === 422) {
                const validation = error.response.data.errors as Record<string, string[]>;
                setErrors(Object.fromEntries(Object.entries(validation).map(([field, messages]) => [field, messages[0]])));
            } else {
                setErrors({ share: 'Le partage est indisponible. Vérifiez votre connexion et réessayez.' });
            }
        } finally {
            setProcessing(false);
        }
    };

    return (
        <section className="mb-6 rounded-2xl border border-emerald-200 bg-white p-5" aria-labelledby="whatsapp-title">
            <div className="mb-5 flex items-start justify-between gap-4">
                <div>
                    <h3 id="whatsapp-title" className="font-semibold text-emerald-900">Partager sur WhatsApp</h3>
                    <p className="mt-1 text-sm text-slate-500">Préparez votre message, puis confirmez son envoi dans WhatsApp.</p>
                </div>
                <button type="button" onClick={onClose} className="rounded-lg px-2 py-1 text-sm text-slate-500 hover:bg-slate-100" aria-label="Fermer le partage WhatsApp">Fermer</button>
            </div>
            <form onSubmit={prepare} className="space-y-4" aria-busy={processing}>
                <div>
                    <label htmlFor="whatsapp-phone" className="text-sm font-medium">Numéro WhatsApp du client (facultatif)</label>
                    <input id="whatsapp-phone" type="tel" value={phone} maxLength={40} disabled={processing}
                        onChange={(event) => { setPhone(event.target.value); setShare(null); }}
                        placeholder="+237 6 12 34 56 78" aria-invalid={!!errors.phone} aria-describedby="whatsapp-phone-hint"
                        className="mt-1 w-full rounded-xl border-slate-300 text-sm focus:border-emerald-700 focus:ring-emerald-700" />
                    <p id="whatsapp-phone-hint" className="mt-1 text-xs text-slate-500">Avec l’indicatif du pays. Laissez vide pour choisir le contact dans WhatsApp.</p>
                    {errors.phone && <p role="alert" className="mt-1 text-sm text-red-700">{errors.phone}</p>}
                </div>
                <div>
                    <label htmlFor="whatsapp-message" className="text-sm font-medium">Un mot pour votre client (facultatif)</label>
                    <textarea id="whatsapp-message" value={message} maxLength={2000} rows={3} disabled={processing}
                        onChange={(event) => { setMessage(event.target.value); setShare(null); }}
                        placeholder="Je reste disponible pour toute précision."
                        className="mt-1 w-full rounded-xl border-slate-300 text-sm focus:border-emerald-700 focus:ring-emerald-700" />
                    {errors.message && <p role="alert" className="mt-1 text-sm text-red-700">{errors.message}</p>}
                </div>
                {errors.share && <p role="alert" className="text-sm text-red-700">{errors.share}</p>}
                {!share && <button type="submit" disabled={processing} className="rounded-xl bg-emerald-800 px-4 py-2 text-sm font-medium text-white disabled:opacity-50">{processing ? 'Préparation…' : 'Préparer le partage'}</button>}
            </form>
            {share && (
                <div className="mt-5 space-y-4 border-t border-emerald-100 pt-5" role="region" aria-label="Message WhatsApp prêt">
                    <p className="rounded-xl bg-emerald-50 p-3 text-sm text-emerald-900" role="status">
                        {share.requires_pdf_attachment
                            ? 'Mercuria est ouvert sur une adresse locale. Téléchargez le PDF, puis joignez-le à votre conversation WhatsApp.'
                            : 'Le message contient un lien vers le PDF, valable 7 jours. Toute personne disposant de ce lien peut ouvrir le document.'}
                    </p>
                    <details>
                        <summary className="cursor-pointer text-sm font-medium text-emerald-800">Voir le message préparé</summary>
                        <p className="mt-3 whitespace-pre-wrap break-words rounded-xl bg-slate-50 p-4 text-sm text-slate-600">{share.message}</p>
                    </details>
                    <div className="flex flex-wrap gap-3">
                        {share.requires_pdf_attachment && <a href={route('app.documents.pdf', documentId)} className="rounded-xl border border-emerald-800 px-4 py-2 text-sm font-medium text-emerald-900">1. Télécharger le PDF</a>}
                        <a href={share.whatsapp_url} target="_blank" rel="noopener noreferrer" className="rounded-xl bg-emerald-800 px-4 py-2 text-sm font-medium text-white">
                            {share.requires_pdf_attachment ? '2. Ouvrir WhatsApp' : 'Ouvrir WhatsApp'}
                        </a>
                    </div>
                    <p className="text-xs text-slate-500">Vous pourrez modifier le message avant de l’envoyer. La préparation ne marque pas le document comme envoyé.</p>
                </div>
            )}
        </section>
    );
}
