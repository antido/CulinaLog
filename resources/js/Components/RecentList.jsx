import { Link } from '@inertiajs/react';

/**
 * A titled list of recent items on a dashboard. Each item has a `title`,
 * an optional `detail` line, `when` it happened and an optional `url`.
 */
export default function RecentList({ heading, items, emptyMessage }) {
    const headingId = `recent-${heading.toLowerCase().replace(/\s+/g, '-')}`;

    return (
        <section
            aria-labelledby={headingId}
            className="rounded-2xl border border-line bg-paper p-5"
        >
            <h2 id={headingId} className="text-lg font-semibold">
                {heading}
            </h2>

            {items.length === 0 ? (
                <p className="mt-3 text-sm text-plum">{emptyMessage}</p>
            ) : (
                <ul className="mt-3 divide-y divide-line">
                    {items.map((item) => (
                        <li key={item.id} className="py-3 first:pt-0 last:pb-0">
                            {item.url ? (
                                <Link
                                    href={item.url}
                                    className="break-words font-medium hover:text-aubergine"
                                >
                                    {item.title}
                                </Link>
                            ) : (
                                <p className="break-words font-medium">
                                    {item.title}
                                </p>
                            )}
                            <p className="mt-0.5 break-words text-sm text-plum">
                                {[item.detail, item.when]
                                    .filter(Boolean)
                                    .join(', ')}
                            </p>
                        </li>
                    ))}
                </ul>
            )}
        </section>
    );
}
