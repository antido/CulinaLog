import { Link } from '@inertiajs/react';

/**
 * Previous / next links for a Laravel paginator.
 * Pass the paginator object that the controller sent to the page.
 */
export default function Pagination({ paginator, className = '' }) {
    if (!paginator || paginator.last_page <= 1) {
        return null;
    }

    const linkClasses =
        'inline-flex min-h-11 items-center rounded-lg border border-line bg-paper px-4 text-sm font-semibold text-ink hover:border-aubergine hover:text-aubergine';
    const disabledClasses =
        'inline-flex min-h-11 items-center rounded-lg border border-line px-4 text-sm font-semibold text-plum/50';

    return (
        <nav
            aria-label="Pagination"
            className={`flex items-center justify-between gap-3 ${className}`}
        >
            {paginator.prev_page_url ? (
                <Link
                    href={paginator.prev_page_url}
                    className={linkClasses}
                    preserveScroll={false}
                >
                    Previous
                </Link>
            ) : (
                <span className={disabledClasses} aria-disabled="true">
                    Previous
                </span>
            )}

            <p className="text-sm text-plum">
                Page {paginator.current_page} of {paginator.last_page}
            </p>

            {paginator.next_page_url ? (
                <Link href={paginator.next_page_url} className={linkClasses}>
                    Next
                </Link>
            ) : (
                <span className={disabledClasses} aria-disabled="true">
                    Next
                </span>
            )}
        </nav>
    );
}
