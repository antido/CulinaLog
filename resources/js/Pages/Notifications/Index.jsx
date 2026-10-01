import Button from '@/Components/Button';
import EmptyState from '@/Components/EmptyState';
import PageHeader from '@/Components/PageHeader';
import Pagination from '@/Components/Pagination';
import AppLayout from '@/Layouts/AppLayout';
import { Head, Link, router } from '@inertiajs/react';

export default function Index({ notifications }) {
    const hasUnread = notifications.data.some(
        (notification) => !notification.is_read,
    );

    return (
        <AppLayout narrow>
            <Head title="Notifications" />

            <PageHeader
                title="Notifications"
                actions={
                    hasUnread && (
                        <Button
                            variant="secondary"
                            onClick={() =>
                                router.post(route('notifications.read-all'))
                            }
                        >
                            Mark all as read
                        </Button>
                    )
                }
            />

            {notifications.data.length === 0 ? (
                <EmptyState
                    title="No notifications yet"
                    message="You will hear here when someone comments on, reacts to or replies to something of yours."
                />
            ) : (
                <ul className="divide-y divide-line rounded-2xl border border-line bg-paper">
                    {notifications.data.map((notification) => (
                        <li key={notification.id}>
                            <Link
                                href={route('notifications.show', notification.id)}
                                className="flex items-start gap-3 p-4 hover:bg-rice/60 sm:p-5"
                            >
                                <span
                                    aria-hidden="true"
                                    className={`mt-2 size-2.5 shrink-0 rounded-full ${
                                        notification.is_read
                                            ? 'bg-transparent'
                                            : 'bg-saffron'
                                    }`}
                                />
                                <span className="min-w-0">
                                    <span
                                        className={`block break-words ${
                                            notification.is_read
                                                ? 'text-plum'
                                                : 'font-semibold'
                                        }`}
                                    >
                                        {!notification.is_read && (
                                            <span className="sr-only">
                                                Unread:{' '}
                                            </span>
                                        )}
                                        {notification.message}
                                    </span>
                                    <span className="mt-1 block text-sm text-plum">
                                        {notification.received}
                                    </span>
                                </span>
                            </Link>
                        </li>
                    ))}
                </ul>
            )}

            <Pagination paginator={notifications} className="mt-6" />
        </AppLayout>
    );
}
