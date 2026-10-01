import ApplicationLogo from '@/Components/ApplicationLogo';
import { Head } from '@inertiajs/react';

const messages = {
    403: {
        title: 'You do not have access to this page',
        text: 'Your account is not allowed to open it. If you think that is a mistake, ask an admin.',
    },
    404: {
        title: 'Page not found',
        text: 'The address may be wrong, or the page may have been removed or made private.',
    },
    429: {
        title: 'Too many requests',
        text: 'You are doing that too often. Wait a minute, then try again.',
    },
    500: {
        title: 'Something went wrong on our side',
        text: 'The problem has been recorded. Please try again in a moment.',
    },
    503: {
        title: 'Down for maintenance',
        text: 'CulinaLog is being updated and will be back shortly.',
    },
};

/**
 * The page shown for errors. It is deliberately self-contained: it does
 * not use the main layout or any shared data, because some errors happen
 * before that data is available. It never shows technical details.
 */
export default function Error({ status }) {
    const { title, text } = messages[status] ?? messages[500];

    return (
        <div className="flex min-h-screen flex-col items-center justify-center px-4 py-10 text-center">
            <Head title={title} />

            <a
                href="/"
                className="flex items-center gap-2 rounded-lg"
                aria-label="CulinaLog home"
            >
                <ApplicationLogo className="size-10" />
                <span className="font-display text-2xl font-bold tracking-tight">
                    CulinaLog
                </span>
            </a>

            <main className="mt-8 max-w-md">
                <p className="text-sm font-semibold text-plum">Error {status}</p>
                <h1 className="mt-1 text-3xl font-bold tracking-tight">
                    {title}
                </h1>
                <p className="mt-3 text-plum">{text}</p>

                {/* Plain links: a full page load is the safest way out of an error. */}
                <div className="mt-8 flex flex-wrap justify-center gap-3">
                    <a
                        href="/"
                        className="inline-flex min-h-11 items-center rounded-lg bg-aubergine px-4 text-sm font-semibold text-white hover:bg-aubergine-deep"
                    >
                        Go to the home page
                    </a>
                    <a
                        href="/recipes"
                        className="inline-flex min-h-11 items-center rounded-lg border border-line bg-paper px-4 text-sm font-semibold hover:border-aubergine hover:text-aubergine"
                    >
                        Browse recipes
                    </a>
                </div>
            </main>
        </div>
    );
}
