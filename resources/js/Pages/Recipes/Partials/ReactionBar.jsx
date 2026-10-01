import { router } from '@inertiajs/react';
import { useState } from 'react';

// The three reactions, each with a small icon drawn as an SVG path.
const reactions = [
    {
        type: 'like',
        label: 'Like',
        icon: 'M7 11v9H4a1 1 0 0 1-1-1v-7a1 1 0 0 1 1-1h3Zm0 0 4-8a2.5 2.5 0 0 1 2.5 2.5V9h4.6a2 2 0 0 1 2 2.4l-1.3 7a2 2 0 0 1-2 1.6H7',
    },
    {
        type: 'love',
        label: 'Love',
        icon: 'M12 20.5s-7.5-4.6-7.5-10.2A4.3 4.3 0 0 1 12 7.6a4.3 4.3 0 0 1 7.5 2.7c0 5.6-7.5 10.2-7.5 10.2Z',
    },
    {
        type: 'wow',
        label: 'Wow',
        icon: 'm12 3 2.5 6.2L21 10l-5 4.3 1.6 6.5L12 17.3l-5.6 3.5L8 14.3 3 10l6.5-.8L12 3Z',
    },
];

/**
 * The like / love / wow buttons under a recipe.
 * A person has one reaction per recipe: pressing the active one removes it,
 * pressing another switches to it. The server enforces this.
 */
export default function ReactionBar({ recipeSlug, counts, mine, canReact }) {
    const [processing, setProcessing] = useState(false);

    const react = (type) => {
        router.post(
            route('reactions.store', recipeSlug),
            { type },
            {
                preserveScroll: true,
                onStart: () => setProcessing(true),
                onFinish: () => setProcessing(false),
            },
        );
    };

    return (
        <div
            role="group"
            aria-label="React to this recipe"
            className="flex flex-wrap gap-2"
        >
            {reactions.map((reaction) => {
                const active = mine === reaction.type;

                return (
                    <button
                        key={reaction.type}
                        type="button"
                        onClick={() => react(reaction.type)}
                        disabled={!canReact || processing}
                        aria-pressed={active}
                        className={`inline-flex min-h-11 items-center gap-2 rounded-full border px-4 text-sm font-semibold transition-colors disabled:cursor-not-allowed disabled:opacity-60 ${
                            active
                                ? 'border-aubergine bg-aubergine text-white'
                                : 'border-line bg-paper text-ink hover:border-aubergine hover:text-aubergine'
                        }`}
                    >
                        <svg
                            className="size-5"
                            viewBox="0 0 24 24"
                            fill={active ? 'currentColor' : 'none'}
                            stroke="currentColor"
                            strokeWidth="1.8"
                            strokeLinejoin="round"
                            strokeLinecap="round"
                            aria-hidden="true"
                        >
                            <path d={reaction.icon} />
                        </svg>
                        {reaction.label}
                        <span className="tabular-nums">
                            {counts[reaction.type]}
                        </span>
                    </button>
                );
            })}
        </div>
    );
}
