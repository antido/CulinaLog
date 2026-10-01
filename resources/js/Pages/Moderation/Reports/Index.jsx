import Badge from '@/Components/Badge';
import Button from '@/Components/Button';
import EmptyState from '@/Components/EmptyState';
import ManageNav from '@/Components/ManageNav';
import PageHeader from '@/Components/PageHeader';
import Pagination from '@/Components/Pagination';
import AppLayout from '@/Layouts/AppLayout';
import { useRequest } from '@/utils';
import { Head, Link } from '@inertiajs/react';

export default function Index({ reports, status, statuses }) {
    // `hideContent` hides the reported item in the same step as resolving.
    const { busy, send } = useRequest();

    const review = (report, newStatus, hideContent = false) => {
        send('patch', route('moderation.reports.update', report.id), {
            status: newStatus,
            hide_content: hideContent,
        });
    };

    return (
        <AppLayout>
            <Head title="Reports" />

            <ManageNav />
            <PageHeader
                title="Reports"
                intro="Content that members and guests have flagged. Resolve a report when you act on it; dismiss it when nothing is wrong."
            />

            <nav aria-label="Report status" className="mb-6">
                <ul className="flex flex-wrap gap-2">
                    {statuses.map((option) => (
                        <li key={option.value}>
                            <Link
                                href={route('moderation.reports.index', {
                                    status: option.value,
                                })}
                                aria-current={
                                    option.value === status ? 'page' : undefined
                                }
                                className={`inline-flex min-h-11 items-center rounded-full border px-4 text-sm font-semibold ${
                                    option.value === status
                                        ? 'border-aubergine bg-aubergine text-white'
                                        : 'border-line bg-paper hover:border-aubergine hover:text-aubergine'
                                }`}
                            >
                                {option.label}
                            </Link>
                        </li>
                    ))}
                </ul>
            </nav>

            {reports.data.length === 0 ? (
                <EmptyState
                    title={`No ${status} reports`}
                    message={
                        status === 'pending'
                            ? 'Nothing is waiting for review.'
                            : 'Reports appear here after a moderator reviews them.'
                    }
                />
            ) : (
                <ul className="space-y-4">
                    {reports.data.map((report) => (
                        <li
                            key={report.id}
                            className="rounded-2xl border border-line bg-paper p-5"
                        >
                            <div className="flex flex-wrap items-center gap-2">
                                <Badge tone="warning">{report.reason}</Badge>
                                {report.target?.is_hidden && (
                                    <Badge tone="danger">Content is hidden</Badge>
                                )}
                                <span className="text-sm text-plum">
                                    Reported by {report.reporter},{' '}
                                    {report.reported}
                                </span>
                            </div>

                            {report.target ? (
                                <p className="mt-3">
                                    <span className="font-semibold">
                                        {report.target.type}:
                                    </span>{' '}
                                    <span className="user-text">
                                        {report.target.excerpt}
                                    </span>{' '}
                                    <Link href={report.target.url} className="link">
                                        Open the page
                                    </Link>
                                </p>
                            ) : (
                                <p className="mt-3 text-plum">
                                    The reported content has been deleted.
                                </p>
                            )}

                            {report.description && (
                                <p className="user-text mt-3 rounded-lg bg-rice px-3 py-2 text-sm">
                                    <span className="font-semibold">
                                        Reporter's note:
                                    </span>{' '}
                                    {report.description}
                                </p>
                            )}

                            {report.status === 'pending' ? (
                                <div className="mt-4 flex flex-wrap gap-3">
                                    {report.target && !report.target.is_hidden && (
                                        <Button
                                            variant="danger"
                                            disabled={busy}
                                            onClick={() =>
                                                review(report, 'resolved', true)
                                            }
                                        >
                                            Hide content and resolve
                                        </Button>
                                    )}
                                    <Button
                                        disabled={busy}
                                        onClick={() => review(report, 'resolved')}
                                    >
                                        Resolve
                                    </Button>
                                    <Button
                                        variant="secondary"
                                        disabled={busy}
                                        onClick={() => review(report, 'dismissed')}
                                    >
                                        Dismiss
                                    </Button>
                                </div>
                            ) : (
                                <p className="mt-3 text-sm text-plum">
                                    Reviewed by {report.reviewer ?? 'a former moderator'}.
                                </p>
                            )}
                        </li>
                    ))}
                </ul>
            )}

            <Pagination paginator={reports} className="mt-6" />
        </AppLayout>
    );
}
