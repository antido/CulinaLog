import { usePermissions } from '@/utils';
import { Link } from '@inertiajs/react';

// Each link is only shown to users whose role has the permission.
// `match` is the route-name pattern that marks the link as current.
const groups = [
    {
        label: 'Admin',
        links: [
            { label: 'Overview', route: 'admin.dashboard', match: 'admin.dashboard', permission: 'dashboard.admin' },
            { label: 'Users', route: 'admin.users.index', match: 'admin.users.*', permission: 'users.view' },
            { label: 'Roles', route: 'admin.roles.index', match: 'admin.roles.*', permission: 'roles.manage' },
            { label: 'Permissions', route: 'admin.permissions.index', match: 'admin.permissions.*', permission: 'permissions.view' },
            { label: 'Recipes', route: 'admin.recipes.index', match: 'admin.recipes.*', permission: 'recipes.manage' },
            { label: 'Categories', route: 'admin.categories.index', match: 'admin.categories.*', permission: 'categories.manage' },
            { label: 'Forum categories', route: 'admin.forum-categories.index', match: 'admin.forum-categories.*', permission: 'forum.manage' },
        ],
    },
    {
        label: 'Moderation',
        links: [
            { label: 'Overview', route: 'moderation.dashboard', match: 'moderation.dashboard', permission: 'dashboard.moderator' },
            { label: 'Reports', route: 'moderation.reports.index', match: 'moderation.reports.*', permission: 'reports.review' },
            { label: 'Waiting for approval', route: 'moderation.pending', match: 'moderation.pending', permission: 'dashboard.moderator' },
        ],
    },
];

/**
 * The section links at the top of every admin and moderation page,
 * in two groups. A group with no links the user may open is left out.
 */
export default function ManageNav() {
    const { can } = usePermissions();

    return (
        <div className="mb-8 space-y-3">
            {groups.map((group) => {
                const links = group.links.filter((link) => can(link.permission));

                if (links.length === 0) {
                    return null;
                }

                return (
                    <nav
                        key={group.label}
                        aria-label={group.label}
                        className="flex flex-col gap-2 sm:flex-row sm:items-center"
                    >
                        <p className="w-24 shrink-0 text-sm font-semibold text-plum">
                            {group.label}
                        </p>
                        <ul className="flex flex-wrap gap-1">
                            {links.map((link) => {
                                const active = route().current(link.match);

                                return (
                                    <li key={link.route}>
                                        <Link
                                            href={route(link.route)}
                                            aria-current={
                                                active ? 'page' : undefined
                                            }
                                            className={`inline-flex min-h-10 items-center rounded-lg border px-3 text-sm font-medium ${
                                                active
                                                    ? 'border-aubergine bg-aubergine text-white'
                                                    : 'border-line bg-paper text-ink hover:border-aubergine hover:text-aubergine'
                                            }`}
                                        >
                                            {link.label}
                                        </Link>
                                    </li>
                                );
                            })}
                        </ul>
                    </nav>
                );
            })}
        </div>
    );
}
