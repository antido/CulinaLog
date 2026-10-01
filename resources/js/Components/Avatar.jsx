const sizes = {
    sm: 'size-8 text-xs',
    md: 'size-10 text-sm',
    lg: 'size-24 text-3xl',
};

/**
 * A user's profile image, or their initial when they have not uploaded one.
 * Pass `user={null}` for an anonymous guest.
 */
export default function Avatar({ user, size = 'md', className = '' }) {
    const classes = `${sizes[size]} shrink-0 rounded-full ${className}`;

    if (user?.avatar_url) {
        return (
            <img
                src={user.avatar_url}
                alt=""
                className={`${classes} object-cover`}
                loading="lazy"
            />
        );
    }

    return (
        <span
            aria-hidden="true"
            className={`${classes} inline-flex items-center justify-center font-semibold ${
                user ? 'bg-aubergine-soft text-aubergine' : 'bg-rice text-plum'
            }`}
        >
            {user ? user.name.charAt(0).toUpperCase() : '?'}
        </span>
    );
}
