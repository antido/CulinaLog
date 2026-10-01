import Card from '@/Components/Card';
import ConfirmDialog from '@/Components/ConfirmDialog';
import EmptyState from '@/Components/EmptyState';
import Pagination from '@/Components/Pagination';
import { Link, router, usePage } from '@inertiajs/react';
import { useState } from 'react';
import DiscussionForm from './DiscussionForm';
import DiscussionItem from './DiscussionItem';

/**
 * A whole discussion: the heading, the form for a new post, the nested
 * posts, pagination, and the confirmation before a post is deleted.
 * Recipe comments and forum replies both use it.
 *
 * - `heading`: for example "Comments (4)".
 * - `noun`: what a post is called, "comment" or "reply".
 * - `items`: the paginator of top-level posts from the server.
 * - `storeUrl`: where new posts are sent.
 * - `deleteRoute`: the route name that deletes one post by id.
 * - `canPost`: whether this viewer may post. `closedMessage` is shown if not.
 * - `guestNote`: a sentence shown to guests above the form.
 * - `extra`: optional function returning more action buttons for a post.
 */
export default function DiscussionThread({
    heading,
    noun,
    items,
    storeUrl,
    deleteRoute,
    canPost,
    closedMessage,
    guestNote,
    allowGuestName = false,
    emptyTitle,
    emptyMessage,
    extra,
}) {
    const { auth } = usePage().props;
    const [itemToDelete, setItemToDelete] = useState(null);
    const [processing, setProcessing] = useState(false);

    const formProps = { storeUrl, noun, allowGuestName };

    const deleteItem = () => {
        router.delete(route(deleteRoute, itemToDelete.id), {
            preserveScroll: true,
            onStart: () => setProcessing(true),
            onFinish: () => {
                setProcessing(false);
                setItemToDelete(null);
            },
        });
    };

    return (
        <section aria-labelledby="discussion-heading" className="mt-14">
            <h2 id="discussion-heading" className="text-2xl font-bold">
                {heading}
            </h2>

            {canPost ? (
                <Card className="mt-5">
                    {!auth.user && (
                        <p className="mb-4 text-sm text-plum">
                            {guestNote}{' '}
                            <Link href={route('login')} className="link">
                                Log in
                            </Link>{' '}
                            to post under your account.
                        </p>
                    )}
                    <DiscussionForm {...formProps} />
                </Card>
            ) : (
                <p className="mt-3 text-plum">{closedMessage}</p>
            )}

            <div className="mt-8">
                {items.data.length === 0 ? (
                    <EmptyState title={emptyTitle} message={emptyMessage} />
                ) : (
                    <ul className="space-y-7">
                        {items.data.map((item) => (
                            <DiscussionItem
                                key={item.id}
                                item={item}
                                canPost={canPost}
                                formProps={formProps}
                                onDelete={setItemToDelete}
                                extra={extra}
                            />
                        ))}
                    </ul>
                )}
            </div>

            <Pagination paginator={items} className="mt-8" />

            <ConfirmDialog
                show={itemToDelete !== null}
                title={`Delete this ${noun}?`}
                message="Answers to it are deleted too. This cannot be undone."
                confirmLabel={`Delete ${noun}`}
                processing={processing}
                onConfirm={deleteItem}
                onClose={() => setItemToDelete(null)}
            />
        </section>
    );
}
