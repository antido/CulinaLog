/**
 * A picture with a fixed shape. When there is no picture it shows a
 * "No image" placeholder of the same shape, so grids stay aligned.
 *
 * `alt` describes the picture. Pass an empty string when the title
 * next to it already says the same thing.
 */
export default function Thumbnail({
    src,
    alt = '',
    className = 'aspect-[4/3]',
    eager = false,
}) {
    if (src) {
        return (
            <img
                src={src}
                alt={alt}
                loading={eager ? 'eager' : 'lazy'}
                className={`w-full bg-rice object-cover ${className}`}
            />
        );
    }

    return (
        <div
            className={`flex w-full flex-col items-center justify-center gap-1 bg-aubergine-soft text-xs font-medium text-plum ${className}`}
        >
            {/* A plate with a fork and a spoon. */}
            <svg
                className="size-8 text-aubergine/40"
                viewBox="0 0 32 32"
                fill="none"
                stroke="currentColor"
                strokeWidth="1.6"
                strokeLinecap="round"
                aria-hidden="true"
            >
                <circle cx="16" cy="16" r="7" />
                <circle cx="16" cy="16" r="3.5" />
                <path d="M3 8v5a2 2 0 0 0 4 0V8M5 8v17" />
                <path d="M27 8v17M27 8c1.500 1.200 2 3.200 2 6.500h-2" />
            </svg>
            No image
        </div>
    );
}
