import Avatar from '@/Components/Avatar';
import { Link } from '@inertiajs/react';
import { useState } from 'react';
import DiscussionForm from './DiscussionForm';

const actionClasses =
    'inline-flex min-h-11 items-center rounded-lg px-2 text-sm font-semibold text-aubergine hover:bg-aubergine-soft';

/**
 * One post in a discussion with its answers underneath. The component
 * renders itself again for each answer, which is how the thread nests.
 *
 * The text is plain text. React escapes it, so anything a user types
 * (including HTML) is shown as written and never run.
 *
 * `formProps` are passed on to the answer form (storeUrl, noun, ...).
 * `extra` may return more action buttons for a post, such as "Report".
 */
export default function DiscussionItem({
    item,
    canPost,
    formProps,
    onDelete,
    extra,
}) {
    const [answering, setAnswering] = useState(false);

    return (
        <li>
            <article className="flex gap-3">
                <Avatar user={item.author} size="sm" className="mt-1" />

                <div className="min-w-0 flex-1">
                    <p className="flex flex-wrap items-baseline gap-x-2 text-sm">
                        {item.author ? (
                            <Link
                                href={route('users.show', item.author.username)}
                                className="inline-block py-1 font-semibold hover:text-aubergine"
                            >
                                {item.author_name}
                            </Link>
                        ) : (
                            <span className="font-semibold">
                                {item.author_name}
                            </span>
                        )}
                        {item.is_guest && (
                            <span className="text-plum">(guest)</span>
                        )}
                        <time dateTime={item.posted_at} className="text-plum">
                            {item.posted}
                        </time>
                    </p>

                    <p className="user-text mt-1">{item.body}</p>

                    <div className="-ml-2 mt-1 flex flex-wrap gap-1">
                        {canPost && (
                            <button
                                type="button"
                                className={actionClasses}
                                onClick={() => setAnswering(!answering)}
                                aria-expanded={answering}
                            >
                                Reply
                            </button>
                        )}
                        {item.can_delete && (
                            <button
                                type="button"
                                className={actionClasses}
                                onClick={() => onDelete(item)}
                            >
                                Delete
                            </button>
                        )}
                        {extra?.(item)}
                    </div>

                    {answering && (
                        <div className="mt-3 rounded-xl border border-line bg-paper p-4">
                            <DiscussionForm
                                {...formProps}
                                parentId={item.id}
                                onDone={() => setAnswering(false)}
                            />
                        </div>
                    )}
                </div>
            </article>

            {item.replies.length > 0 && (
                <ul className="ml-4 mt-5 space-y-5 border-l-2 border-line pl-4 sm:ml-5 sm:pl-6">
                    {item.replies.map((reply) => (
                        <DiscussionItem
                            key={reply.id}
                            item={reply}
                            canPost={canPost}
                            formProps={formProps}
                            onDelete={onDelete}
                            extra={extra}
                        />
                    ))}
                </ul>
            )}
        </li>
    );
}
