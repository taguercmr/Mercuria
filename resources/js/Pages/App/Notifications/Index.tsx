import AppLayout from '@/Layouts/AppLayout';
import { Head, Link, router, usePage } from '@inertiajs/react';

type NotificationItem = {
    id: string;
    type: string;
    data: {
        title?: string;
        body?: string;
        url?: string;
        type?: string;
    };
    read_at: string | null;
    created_at: string;
};

type Props = {
    notifications: {
        data: NotificationItem[];
        links: Array<{ url: string | null; label: string; active: boolean }>;
    };
    unreadCount: number;
};

export default function NotificationsIndex({ notifications, unreadCount }: Props) {
    const { flash } = usePage().props as { flash?: { success?: string } };

    return (
        <AppLayout title="Notifications">
            <Head title="Notifications" />

            {flash?.success && (
                <div className="mb-4 rounded-xl bg-emerald-50 px-4 py-3 text-sm text-emerald-800">
                    {flash.success}
                </div>
            )}

            <div className="mb-4 flex items-center justify-between gap-3">
                <p className="text-sm text-slate-600">
                    {unreadCount > 0
                        ? `${unreadCount} non lue${unreadCount > 1 ? 's' : ''}`
                        : 'Aucune notification non lue'}
                </p>
                {unreadCount > 0 && (
                    <button
                        type="button"
                        className="rounded-xl border border-slate-300 bg-white px-3 py-1.5 text-sm"
                        onClick={() => router.post(route('app.notifications.readAll'))}
                    >
                        Tout marquer comme lu
                    </button>
                )}
            </div>

            <div className="space-y-2">
                {notifications.data.length === 0 && (
                    <div className="rounded-2xl border border-dashed border-slate-200 bg-white px-4 py-10 text-center text-sm text-slate-500">
                        Pas encore de notifications.
                    </div>
                )}
                {notifications.data.map((n) => (
                    <button
                        key={n.id}
                        type="button"
                        className={`w-full rounded-2xl border px-4 py-3 text-left transition ${
                            n.read_at
                                ? 'border-slate-200 bg-white'
                                : 'border-emerald-200 bg-emerald-50/60'
                        }`}
                        onClick={() => router.post(route('app.notifications.read', n.id))}
                    >
                        <div className="flex items-start justify-between gap-3">
                            <div>
                                <div className="font-medium text-slate-900">
                                    {n.data.title ?? 'Notification'}
                                </div>
                                <p className="mt-1 text-sm text-slate-600">{n.data.body}</p>
                            </div>
                            <span className="shrink-0 text-xs text-slate-400">
                                {new Date(n.created_at).toLocaleString('fr-FR')}
                            </span>
                        </div>
                    </button>
                ))}
            </div>

            <div className="mt-6 flex flex-wrap gap-2">
                {notifications.links.map((link, i) =>
                    link.url ? (
                        <Link
                            key={i}
                            href={link.url}
                            className={`rounded-lg px-3 py-1 text-sm ${
                                link.active ? 'bg-emerald-800 text-white' : 'bg-white border border-slate-200'
                            }`}
                            dangerouslySetInnerHTML={{ __html: link.label }}
                        />
                    ) : null,
                )}
            </div>
        </AppLayout>
    );
}
