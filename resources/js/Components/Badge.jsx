const tones = {
    neutral: 'bg-rice text-plum border-line',
    primary: 'bg-aubergine-soft text-aubergine border-aubergine/20',
    success: 'bg-basil-soft text-basil border-basil/20',
    warning: 'bg-saffron-soft text-ink border-saffron/50',
    danger: 'bg-chili-soft text-chili border-chili/20',
};

/** A small label for a status such as "Draft" or "Pending". */
export default function Badge({ tone = 'neutral', className = '', children }) {
    return (
        <span
            className={`inline-flex items-center rounded-full border px-2.5 py-0.5 text-xs font-semibold ${tones[tone]} ${className}`}
        >
            {children}
        </span>
    );
}
