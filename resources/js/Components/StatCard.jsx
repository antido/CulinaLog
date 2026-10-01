import { Link } from '@inertiajs/react';

/**
 * One number on a dashboard, with what it counts. Use inside a <dl>.
 * With `href`, the whole card links to the page behind the number.
 */
export default function StatCard({ label, value, href }) {
    return (
        <div
            className={`relative rounded-2xl border border-line bg-paper p-5 ${
                href ? 'hover:border-aubergine' : ''
            }`}
        >
            <dt className="text-sm font-medium text-plum">{label}</dt>
            <dd className="mt-1 font-display text-3xl font-bold tabular-nums">
                {href ? (
                    // The ::after layer stretches the link over the whole card.
                    <Link
                        href={href}
                        className="after:absolute after:inset-0 after:rounded-2xl"
                    >
                        <span className="sr-only">{label}: </span>
                        {value}
                    </Link>
                ) : (
                    value
                )}
            </dd>
        </div>
    );
}
