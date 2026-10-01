import ApplicationLogo from '@/Components/ApplicationLogo';
import { Link } from '@inertiajs/react';

/** The centred card used by the sign-in, registration and password pages. */
export default function GuestLayout({ children }) {
    return (
        <div className="flex min-h-screen flex-col items-center px-4 py-10 sm:justify-center">
            <Link
                href="/"
                className="flex items-center gap-2 rounded-lg"
                aria-label="CulinaLog home"
            >
                <ApplicationLogo className="size-10" />
                <span className="font-display text-2xl font-bold tracking-tight">
                    CulinaLog
                </span>
            </Link>

            <main className="mt-6 w-full rounded-2xl border border-line bg-paper p-6 sm:max-w-md sm:p-8">
                {children}
            </main>
        </div>
    );
}
