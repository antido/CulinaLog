import { Link } from '@inertiajs/react';

const base =
    'inline-flex min-h-11 items-center justify-center gap-2 rounded-lg px-4 py-2 text-sm font-semibold transition-colors disabled:cursor-not-allowed disabled:opacity-50';

const variants = {
    primary: 'bg-aubergine text-white hover:bg-aubergine-deep',
    secondary:
        'border border-line bg-paper text-ink hover:border-aubergine hover:text-aubergine',
    danger: 'bg-chili text-white hover:bg-chili/90',
    ghost: 'text-aubergine hover:bg-aubergine-soft',
};

/**
 * The one button used across the app.
 *
 * - Pass `href` to render it as a link that looks like a button.
 * - `variant` is one of: primary, secondary, danger, ghost.
 * - `small` makes a more compact button for table rows and toolbars.
 */
export default function Button({
    variant = 'primary',
    small = false,
    href,
    type = 'button',
    className = '',
    children,
    ...props
}) {
    const classes = `${base} ${variants[variant]} ${
        small ? 'min-h-9 px-3 py-1.5' : ''
    } ${className}`;

    if (href) {
        return (
            <Link href={href} className={classes} {...props}>
                {children}
            </Link>
        );
    }

    return (
        <button type={type} className={classes} {...props}>
            {children}
        </button>
    );
}
