import Badge from '@/Components/Badge';
import { Link } from '@inertiajs/react';

/**
 * A list of forum topics with their category, author, reply count
 * and latest activity. Pinned and locked topics carry a badge.
 */
export default function TopicList({ topics, showCategory = true }) {
    return (
        <ul className="divide-y divide-line rounded-2xl border border-line bg-paper">
            {topics.map((topic) => (
                <li key={topic.id} className="relative p-4 hover:bg-rice/60 sm:p-5">
                    <div className="flex flex-wrap items-center gap-2">
                        {topic.is_pinned && <Badge tone="warning">Pinned</Badge>}
                        {topic.is_locked && <Badge tone="neutral">Locked</Badge>}
                        {topic.status === 'hidden' && (
                            <Badge tone="danger">Hidden</Badge>
                        )}
                        {showCategory && (
                            <span className="text-sm font-medium text-aubergine">
                                {topic.category.name}
                            </span>
                        )}
                    </div>

                    <h3 className="mt-1 break-words text-lg font-semibold leading-snug">
                        {/* The ::after layer makes the whole row one link. */}
                        <Link
                            href={route('forum.topics.show', topic.slug)}
                            className="after:absolute after:inset-0 hover:text-aubergine"
                        >
                            {topic.title}
                        </Link>
                    </h3>

                    <ul className="mt-2 flex flex-wrap gap-x-4 gap-y-1 text-sm text-plum">
                        <li>By {topic.author.name}</li>
                        <li>
                            {topic.replies_count}{' '}
                            {topic.replies_count === 1 ? 'reply' : 'replies'}
                        </li>
                        <li>
                            {topic.views} {topic.views === 1 ? 'view' : 'views'}
                        </li>
                        {topic.last_activity && (
                            <li>Active {topic.last_activity}</li>
                        )}
                    </ul>
                </li>
            ))}
        </ul>
    );
}
