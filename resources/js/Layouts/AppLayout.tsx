import ApplicationLogo from '@/Components/ApplicationLogo';
import Dropdown from '@/Components/Dropdown';
import { Link, router, usePage } from '@inertiajs/react';
import { PropsWithChildren, useState } from 'react';

const allNav = [
    { href: '/app/dashboard', label: 'Tableau de bord' },
    { href: '/app/guide', label: "Guide d'utilisation" },
    { href: '/app/catalogue', label: 'Mercuriale' },
    { href: '/app/documents/create?type=quote', label: 'Nouveau devis', write: true },
    { href: '/app/documents', label: 'Documents' },
    { href: '/app/templates', label: 'Modeles' },
    { href: '/app/clients', label: 'Clients' },
    { href: '/app/team', label: 'Equipe' },
    { href: '/app/notifications', label: 'Notifications' },
    { href: '/app/subscription', label: 'Abonnement' },
    { href: '/app/developers', label: 'API Developpeurs' },
    { href: '/app/tickets', label: 'Support' },
    { href: '/app/settings', label: 'Personnalisation' },
];

type TenantOption = {
    id: number;
    name: string;
    slug: string;
    plan: string;
    role: string;
};

function NavLinks({
    onNavigate,
    canWrite,
}: {
    onNavigate?: () => void;
    canWrite: boolean;
}) {
    const items = allNav.filter((item) => canWrite || !item.write);

    return (
        <>
            {items.map((item) => (
                <Link
                    key={item.href}
                    href={item.href}
                    onClick={onNavigate}
                    className="rounded-lg px-3 py-2 text-sm text-slate-700 hover:bg-emerald-50 hover:text-[#0b6e4f]"
                >
                    {item.label}
                </Link>
            ))}
        </>
    );
}

function LogoutButton({ className = '' }: { className?: string }) {
    return (
        <button
            type="button"
            className={className}
            onClick={() => router.post(route('logout'))}
        >
            Déconnexion
        </button>
    );
}

export default function AppLayout({
    children,
    title,
}: PropsWithChildren<{ title?: string }>) {
    const [mobileOpen, setMobileOpen] = useState(false);
    const { tenant, tenants, auth, notifications } = usePage().props as {
        tenant?: { id: number; name: string; plan: string } | null;
        tenants?: TenantOption[];
        auth: {
            user: {
                name: string;
                email?: string;
                is_platform_admin?: boolean;
                can_write?: boolean;
                tenant_role?: string | null;
            } | null;
        };
        notifications?: { unread_count?: number };
    };

    const canWrite = Boolean(auth.user?.can_write);
    const unread = notifications?.unread_count ?? 0;

    const switchTenant = (id: number) => {
        if (!tenant || id === tenant.id) return;
        router.post(route('app.tenant.switch', id));
    };

    return (
        <div className="min-h-screen bg-[#f3f6f4] text-slate-900">
            <div className="mx-auto flex min-h-screen max-w-[1400px]">
                <aside className="hidden w-64 shrink-0 border-r border-slate-200 bg-white md:flex md:flex-col">
                    <div className="flex items-center gap-2 border-b border-slate-100 px-5 py-5">
                        <ApplicationLogo className="h-9 w-10 shrink-0" />
                        <div>
                            <div className="font-semibold text-[#0b6e4f]">Mercuria</div>
                            <div className="text-xs text-slate-500">
                                {tenant?.name ?? 'Mon espace'}
                            </div>
                        </div>
                    </div>
                    {(tenants?.length ?? 0) > 1 && (
                        <div className="border-b border-slate-100 px-3 py-3">
                            <label className="mb-1 block text-[10px] uppercase tracking-wide text-slate-400">
                                Organisation
                            </label>
                            <select
                                className="w-full rounded-lg border-slate-300 text-sm"
                                value={tenant?.id ?? ''}
                                onChange={(e) => switchTenant(Number(e.target.value))}
                            >
                                {(tenants ?? []).map((t) => (
                                    <option key={t.id} value={t.id}>
                                        {t.name}
                                    </option>
                                ))}
                            </select>
                        </div>
                    )}
                    <nav className="flex flex-1 flex-col gap-1 p-3">
                        <NavLinks canWrite={canWrite} />
                    </nav>
                    <div className="space-y-3 border-t border-slate-100 p-4 text-xs text-slate-500">
                        <div>
                            Plan {tenant?.plan ?? 'free'}
                            {auth.user?.tenant_role && (
                                <span className="ml-1">· {auth.user.tenant_role}</span>
                            )}
                        </div>
                        {auth.user?.is_platform_admin && (
                            <Link href="/admin" className="block text-[#0b6e4f] hover:underline">
                                Console Admin
                            </Link>
                        )}
                        <Link href={route('profile.edit')} className="block hover:text-[#0b6e4f]">
                            Mon compte
                        </Link>
                        <LogoutButton className="w-full rounded-lg border border-rose-200 bg-rose-50 px-3 py-2 text-left text-sm font-medium text-rose-700 hover:bg-rose-100" />
                    </div>
                </aside>

                <div className="flex min-w-0 flex-1 flex-col">
                    <header className="flex items-center justify-between border-b border-slate-200 bg-white px-4 py-3 md:px-6 md:py-4">
                        <div className="flex items-center gap-3">
                            <button
                                type="button"
                                className="rounded-lg border border-slate-200 p-2 text-slate-700 md:hidden"
                                aria-label="Menu"
                                onClick={() => setMobileOpen(true)}
                            >
                                <svg className="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M4 6h16M4 12h16M4 18h16" />
                                </svg>
                            </button>
                            <h1 className="text-lg font-semibold tracking-tight">
                                {title ?? 'Tableau de bord'}
                            </h1>
                        </div>
                        <div className="flex items-center gap-2 sm:gap-3">
                            <Link
                                href={route('app.notifications.index')}
                                className="relative rounded-lg border border-slate-200 p-2 text-slate-700 hover:bg-slate-50"
                                title="Notifications"
                                aria-label="Notifications"
                            >
                                <svg className="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path
                                        strokeLinecap="round"
                                        strokeLinejoin="round"
                                        strokeWidth={2}
                                        d="M15 17h5l-1.4-1.4A2 2 0 0118 14.2V11a6 6 0 10-12 0v3.2c0 .5-.2 1-.6 1.4L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"
                                    />
                                </svg>
                                {unread > 0 && (
                                    <span className="absolute -right-1 -top-1 flex h-4 min-w-4 items-center justify-center rounded-full bg-emerald-700 px-1 text-[10px] font-semibold text-white">
                                        {unread > 9 ? '9+' : unread}
                                    </span>
                                )}
                            </Link>

                            <LogoutButton className="hidden rounded-lg border border-rose-200 px-3 py-1.5 text-sm font-medium text-rose-700 hover:bg-rose-50 sm:inline-flex" />

                            <div className="relative">
                                <Dropdown>
                                    <Dropdown.Trigger>
                                        <button
                                            type="button"
                                            className="inline-flex items-center gap-2 rounded-lg border border-slate-200 px-3 py-1.5 text-sm text-slate-700 hover:bg-slate-50"
                                            aria-label="Menu compte"
                                        >
                                            <span className="max-w-[72px] truncate font-medium sm:max-w-[140px]">
                                                {auth.user?.name}
                                            </span>
                                            <svg
                                                className="h-4 w-4 text-slate-400"
                                                fill="currentColor"
                                                viewBox="0 0 20 20"
                                            >
                                                <path
                                                    fillRule="evenodd"
                                                    d="M5.23 7.21a.75.75 0 011.06.02L10 11.17l3.71-3.94a.75.75 0 111.08 1.04l-4.25 4.5a.75.75 0 01-1.08 0l-4.25-4.5a.75.75 0 01.02-1.06z"
                                                    clipRule="evenodd"
                                                />
                                            </svg>
                                        </button>
                                    </Dropdown.Trigger>
                                    <Dropdown.Content align="right" width="48">
                                        <div className="border-b border-slate-100 px-4 py-2 text-xs text-slate-500">
                                            {auth.user?.email}
                                        </div>
                                        <Dropdown.Link href={route('profile.edit')}>
                                            Mon compte
                                        </Dropdown.Link>
                                        <Dropdown.Link href={route('app.settings.edit')}>
                                            Personnalisation
                                        </Dropdown.Link>
                                        {auth.user?.is_platform_admin && (
                                            <Dropdown.Link href="/admin">
                                                Console Admin
                                            </Dropdown.Link>
                                        )}
                                        <Dropdown.Link
                                            href={route('logout')}
                                            method="post"
                                            as="button"
                                            className="text-rose-700 hover:bg-rose-50 focus:bg-rose-50 sm:hidden"
                                        >
                                            Déconnexion
                                        </Dropdown.Link>
                                    </Dropdown.Content>
                                </Dropdown>
                            </div>
                        </div>
                    </header>
                    <main className="flex-1 p-4 md:p-6">{children}</main>
                </div>
            </div>

            {mobileOpen && (
                <div className="fixed inset-0 z-50 md:hidden">
                    <button
                        type="button"
                        className="absolute inset-0 bg-slate-900/40"
                        aria-label="Fermer"
                        onClick={() => setMobileOpen(false)}
                    />
                    <div className="absolute inset-y-0 left-0 flex w-72 flex-col bg-white shadow-xl">
                        <div className="flex items-center justify-between border-b border-slate-100 px-4 py-4">
                            <div className="flex items-center gap-2">
                                <ApplicationLogo className="h-8 w-9 shrink-0" />
                                <span className="font-semibold text-[#0b6e4f]">Mercuria</span>
                            </div>
                            <button
                                type="button"
                                className="rounded-lg p-2 text-slate-500"
                                onClick={() => setMobileOpen(false)}
                            >
                                ✕
                            </button>
                        </div>
                        {(tenants?.length ?? 0) > 1 && (
                            <div className="border-b border-slate-100 px-3 py-3">
                                <select
                                    className="w-full rounded-lg border-slate-300 text-sm"
                                    value={tenant?.id ?? ''}
                                    onChange={(e) => {
                                        switchTenant(Number(e.target.value));
                                        setMobileOpen(false);
                                    }}
                                >
                                    {(tenants ?? []).map((t) => (
                                        <option key={t.id} value={t.id}>
                                            {t.name}
                                        </option>
                                    ))}
                                </select>
                            </div>
                        )}
                        <nav className="flex flex-1 flex-col gap-1 overflow-y-auto p-3">
                            <NavLinks canWrite={canWrite} onNavigate={() => setMobileOpen(false)} />
                        </nav>
                        <div className="space-y-2 border-t border-slate-100 p-4 text-sm">
                            <div className="text-xs text-slate-500">
                                {auth.user?.name}
                                {auth.user?.email && (
                                    <div className="truncate">{auth.user.email}</div>
                                )}
                            </div>
                            <Link
                                href={route('profile.edit')}
                                className="block rounded-lg px-3 py-2 hover:bg-emerald-50"
                                onClick={() => setMobileOpen(false)}
                            >
                                Mon compte
                            </Link>
                            <LogoutButton className="w-full rounded-lg border border-rose-200 bg-rose-50 px-3 py-2 text-left font-medium text-rose-700 hover:bg-rose-100" />
                        </div>
                    </div>
                </div>
            )}
        </div>
    );
}
