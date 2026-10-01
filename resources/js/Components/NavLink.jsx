import { Link } from '@inertiajs/react';

/** A link in the main navigation bar. `active` marks the current page. */
export default function NavLink({
    active = false,
    className = '',
    children,
    ...props
}) {
    return (
        <Link
            {...props}
            aria-current={active ? 'page' : undefined}
            className={`inline-flex min-h-11 items-center rounded-lg px-3 text-sm font-medium transition-colors ${
                active
                    ? 'bg-aubergine-soft text-aubergine'
                    : 'text-plum hover:bg-aubergine-soft hover:text-aubergine'
            } ${className}`}
        >
            {children}
        </Link>
    );
}
