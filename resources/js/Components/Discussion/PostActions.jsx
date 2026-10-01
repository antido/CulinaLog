import ReportButton from '@/Components/ReportButton';
import { usePermissions, useRequest } from '@/utils';

/**
 * The extra actions under a post in a discussion: "Report" for everyone,
 * and "Hide" for moderators.
 *
 * - `reportType` is "comment" or "forum_reply".
 * - `hideRoute` is the moderation route that changes the post's status.
 * - `permission` is the permission a moderator needs to see "Hide".
 */
export default function PostActions({
    item,
    noun,
    reportType,
    hideRoute,
    permission,
}) {
    const { can } = usePermissions();

    const { busy, send } = useRequest();

    const hide = () => {
        send('patch', route(hideRoute, item.id), { status: 'hidden' });
    };

    return (
        <>
            <ReportButton type={reportType} id={item.id} noun={noun} />
            {can(permission) && (
                <button
                    type="button"
                    onClick={hide}
                    disabled={busy}
                    className="disabled:opacity-50inline-flex min-h-11 items-center rounded-lg px-2 text-sm font-semibold text-plum hover:bg-chili-soft hover:text-chili"
                >
                    Hide (moderator)
                </button>
            )}
        </>
    );
}
