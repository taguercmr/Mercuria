import { Head } from '@inertiajs/react';

export default function Maintenance({ message }: { message: string }) {
    return (
        <>
            <Head title="Maintenance" />
            <div className="flex min-h-screen items-center justify-center bg-slate-950 px-6 text-white">
                <div className="max-w-lg text-center">
                    <p className="text-sm uppercase tracking-[0.2em] text-emerald-300">
                        Mercuria
                    </p>
                    <h1 className="mt-4 text-3xl font-semibold">Maintenance en cours</h1>
                    <p className="mt-4 text-slate-300">{message}</p>
                </div>
            </div>
        </>
    );
}
