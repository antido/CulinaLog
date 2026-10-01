import Avatar from '@/Components/Avatar';
import Badge from '@/Components/Badge';
import Button from '@/Components/Button';
import ConfirmDialog from '@/Components/ConfirmDialog';
import DiscussionThread from '@/Components/Discussion/DiscussionThread';
import PostActions from '@/Components/Discussion/PostActions';
import ReportButton from '@/Components/ReportButton';
import AppLayout from '@/Layouts/AppLayout';
import { useRequest } from '@/utils';
import { Head, Link, router } from '@inertiajs/react';
import { useState } from 'react';

export default function Show({ topic, replies, can }) {
    const [confirmingDelete, setConfirmingDelete] = useState(false);
    const [processing, setProcessing] = useState(false);

    const deleteTopic = () => {
        router.delete(route('forum.topics.destroy', topic.slug), {
            onStart: () => setProcessing(true),
            onFinish: () => setProcessing(false),
        });
    };

    // Moderator actions: send only the field that changes.
    const { busy, send } = useRequest();

    const moderate = (changes) => {
        send('patch', route('moderation.forum.topics.update', topic.slug), changes);
    };

    const isHidden = topic.status === 'hidden';

    return (
        <AppLayout>
            <Head title={topic.title} />

            <p className="mb-3 text-sm">
                <Link href={route('forum.index')} className="link">
                    Forum
                </Link>
                <span className="mx-2 text-plum" aria-hidden="true">
                    /
                </span>
                <Link
                    href={route('forum.categories.show', topic.category.slug)}
                    className="link"
                >
                    {topic.category.name}
                </Link>
            </p>

            <article>
                <div className="flex flex-wrap gap-2">
                    {topic.is_pinned && <Badge tone="warning">Pinned</Badge>}
                    {topic.is_locked && <Badge tone="neutral">Locked</Badge>}
                    {isHidden && (
                        <Badge tone="danger">
                            Hidden: only the author and moderators can see this
                        </Badge>
                    )}
                </div>

                <h1 className="mt-2 break-words text-3xl font-bold tracking-tight sm:text-4xl">
                    {topic.title}
                </h1>

                <Link
                    href={route('users.show', topic.author.username)}
                    className="mt-4 inline-flex min-h-11 items-center gap-3 rounded-lg"
                >
                    <Avatar user={topic.author} />
                    <span>
                        <span className="block font-medium">
                            {topic.author.name}
                        </span>
                        <span className="block text-sm text-plum">
                            Posted {topic.posted}, {topic.views}{' '}
                            {topic.views === 1 ? 'view' : 'views'}
                        </span>
                    </span>
                </Link>

                <p className="user-text mt-6 max-w-3xl text-lg">{topic.body}</p>

                {!isHidden && (
                    <div className="-ml-2 mt-2">
                        <ReportButton
                            type="forum_topic"
                            id={topic.id}
                            noun="topic"
                        />
                    </div>
                )}

                {(can.update || can.delete || can.moderate) && (
                    <div className="mt-6 flex flex-wrap gap-3">
                        {can.update && (
                            <Button
                                href={route('forum.topics.edit', topic.slug)}
                                variant="secondary"
                            >
                                Edit
                            </Button>
                        )}
                        {can.delete && (
                            <Button
                                variant="secondary"
                                onClick={() => setConfirmingDelete(true)}
                            >
                                Delete
                            </Button>
                        )}
                        {can.moderate && (
                            <>
                                <Button
                                    variant="secondary"
                                    disabled={busy}
                                    onClick={() =>
                                        moderate({ is_pinned: !topic.is_pinned })
                                    }
                                >
                                    {topic.is_pinned ? 'Unpin' : 'Pin'}
                                </Button>
                                <Button
                                    variant="secondary"
                                    disabled={busy}
                                    onClick={() =>
                                        moderate({ is_locked: !topic.is_locked })
                                    }
                                >
                                    {topic.is_locked ? 'Unlock' : 'Lock'}
                                </Button>
                                <Button
                                    variant="secondary"
                                    disabled={busy}
                                    onClick={() =>
                                        moderate({
                                            status: isHidden ? 'visible' : 'hidden',
                                        })
                                    }
                                >
                                    {isHidden ? 'Restore' : 'Hide'}
                                </Button>
                            </>
                        )}
                    </div>
                )}
            </article>

            <DiscussionThread
                heading={`Replies (${topic.replies_count})`}
                noun="reply"
                items={replies}
                storeUrl={route('forum.replies.store', topic.slug)}
                deleteRoute="forum.replies.destroy"
                canPost={can.reply}
                closedMessage={
                    topic.is_locked
                        ? 'This topic is locked. No new replies can be posted.'
                        : 'Log in to reply to this topic.'
                }
                guestNote="You are replying as a guest. Your reply is shown as Anonymous after a moderator approves it."
                emptyTitle="No replies yet"
                emptyMessage="Be the first to answer."
                extra={(reply) => (
                    <PostActions
                        item={reply}
                        noun="reply"
                        reportType="forum_reply"
                        hideRoute="moderation.forum.replies.update"
                        permission="forum.moderate"
                    />
                )}
            />

            <ConfirmDialog
                show={confirmingDelete}
                title={`Delete "${topic.title}"?`}
                message="The topic and all of its replies are removed. This cannot be undone."
                confirmLabel="Delete topic"
                processing={processing}
                onConfirm={deleteTopic}
                onClose={() => setConfirmingDelete(false)}
            />
        </AppLayout>
    );
}
