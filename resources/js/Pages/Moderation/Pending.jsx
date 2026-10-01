import Button from '@/Components/Button';
import EmptyState from '@/Components/EmptyState';
import ManageNav from '@/Components/ManageNav';
import PageHeader from '@/Components/PageHeader';
import AppLayout from '@/Layouts/AppLayout';
import { useRequest } from '@/utils';
import { Head, Link } from '@inertiajs/react';

/** One list of posts waiting for approval, with Approve and Hide buttons. */
function PendingList({ heading, items, updateRoute, noun }) {
    const { busy, send } = useRequest();

    const setStatus = (item, status) => {
        send('patch', route(updateRoute, item.id), { status });
    };

    return (
        <section className="mb-10">
            <h2 className="mb-4 text-2xl font-bold">
                {heading} ({items.length})
            </h2>

            {items.length === 0 ? (
                <EmptyState title={`No ${noun} waiting`} />
            ) : (
                <ul className="space-y-4">
                    {items.map((item) => (
                        <li
                            key={item.id}
                            className="rounded-2xl border border-line bg-paper p-5"
                        >
                            <p className="text-sm text-plum">
                                {item.author_name} (guest), {item.posted}, on{' '}
                                <Link href={item.url} className="link">
                                    {item.where}
                                </Link>
                            </p>
                            <p className="user-text mt-2">{item.body}</p>
                            <div className="mt-4 flex flex-wrap gap-3">
                                <Button
                                    disabled={busy}
                                    onClick={() => setStatus(item, 'visible')}
                                >
                                    Approve
                                </Button>
                                <Button
                                    variant="secondary"
                                    disabled={busy}
                                    onClick={() => setStatus(item, 'hidden')}
                                >
                                    Hide
                                </Button>
                            </div>
                        </li>
                    ))}
                </ul>
            )}
        </section>
    );
}

export default function Pending({ comments, replies }) {
    return (
        <AppLayout>
            <Head title="Waiting for approval" />

            <ManageNav />
            <PageHeader
                title="Waiting for approval"
                intro="Guest comments with links and all guest forum replies are held here until a moderator approves them."
            />

            <PendingList
                heading="Recipe comments"
                noun="comments"
                items={comments}
                updateRoute="moderation.comments.update"
            />
            <PendingList
                heading="Forum replies"
                noun="replies"
                items={replies}
                updateRoute="moderation.forum.replies.update"
            />
        </AppLayout>
    );
}
