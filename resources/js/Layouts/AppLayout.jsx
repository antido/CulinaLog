import ApplicationLogo from '@/Components/ApplicationLogo';
import Avatar from '@/Components/Avatar';
import Button from '@/Components/Button';
import Dropdown from '@/Components/Dropdown';
import FlashMessages from '@/Components/FlashMessages';
import NavLink from '@/Components/NavLink';
import RouteAnnouncer from '@/Components/RouteAnnouncer';
import { usePermissions } from '@/utils';
import { Link, usePage } from '@inertiajs/react';
import { useState } from 'react';

/**
 * The page frame used by every screen except the sign-in pages:
 * navigation bar, flash messages, the page content, and the footer.
 */
export default function AppLayout({ children, narrow = false }) {
    const { auth } = usePage().props;
    const user = auth.user;
    const { can } = usePermissions();
    const [menuOpen, setMenuOpen] = useState(false);

    // Links shown to everyone. `match` is the route-name pattern that marks the link as current.
    const mainLinks = [
        { label: 'Home', href: route('home'), match: 'home' },
        { label: 'Recipes', href: route('recipes.index'), match: 'recipes.*' },
        {
            label: 'Categories',
            href: route('categories.index'),
            match: 'categories.*',
        },
        { label: 'Forum', href: route('forum.index'), match: 'forum.*' },
        { label: 'Search', href: route('search'), match: 'search' },
    ];

    // Links in the signed-in user's account menu.
    // Admin and moderation links only appear for roles that may open them.
    const accountLinks = user
        ? [
              {
                  label:
                      auth.unread_notifications > 0
                          ? `Notifications (${auth.unread_notifications} new)`
                          : 'Notifications',
                  href: route('notifications.index'),
              },
              { label: 'My recipes', href: route('dashboard') },
              { label: 'My profile', href: route('users.show', user.username) },
              { label: 'Account settings', href: route('profile.edit') },
              can('dashboard.admin') && {
                  label: 'Admin',
                  href: route('admin.dashboard'),
              },
              can('dashboard.moderator') && {
                  label: 'Moderation',
                  href: route('moderation.dashboard'),
              },
          ].filter(Boolean)
        : [];

    return (
        <div className="flex min-h-screen flex-col">
            <a
                href="#main"
                className="sr-only focus:not-sr-only focus:absolute focus:left-4 focus:top-4 focus:z-50 focus:rounded-lg focus:bg-paper focus:px-4 focus:py-2 focus:font-semibold"
            >
                Skip to content
            </a>

            <header className="border-b border-line bg-paper">
                <div className="mx-auto flex h-16 max-w-6xl items-center gap-2 px-4 sm:px-6">
                    <Link
                        href={route('home')}
                        className="mr-2 flex shrink-0 items-center gap-2 rounded-lg"
                    >
                        <ApplicationLogo className="size-8" />
                        <span className="font-display text-xl font-bold tracking-tight">
                            CulinaLog
                        </span>
                    </Link>

                    <nav
                        aria-label="Main"
                        className="hidden items-center gap-1 md:flex"
                    >
                        {mainLinks.map((link) => (
                            <NavLink
                                key={link.label}
                                href={link.href}
                                active={route().current(link.match)}
                            >
                                {link.label}
                            </NavLink>
                        ))}
                    </nav>

                    <div className="ml-auto hidden items-center gap-2 md:flex">
                        {user ? (
                            <Dropdown
                                label="Account menu"
                                trigger={
                                    <>
                                        <span className="relative">
                                            <Avatar user={user} size="sm" />
                                            {auth.unread_notifications > 0 && (
                                                <span className="absolute -right-1 -top-1 size-3 rounded-full border-2 border-paper bg-saffron">
                                                    <span className="sr-only">
                                                        You have new
                                                        notifications
                                                    </span>
                                                </span>
                                            )}
                                        </span>
                                        <span className="max-w-32 truncate">
                                            {user.name}
                                        </span>
                                    </>
                                }
                            >
                                {accountLinks.map((link) => (
                                    <Dropdown.Link
                                        key={link.label}
                                        href={link.href}
                                    >
                                        {link.label}
                                    </Dropdown.Link>
                                ))}
                                <Dropdown.Divider />
                                <Dropdown.Link
                                    href={route('logout')}
                                    method="post"
                                    as="button"
                                >
                                    Log out
                                </Dropdown.Link>
                            </Dropdown>
                        ) : (
                            <>
                                <NavLink href={route('login')}>Log in</NavLink>
                                <Button href={route('register')}>
                                    Create account
                                </Button>
                            </>
                        )}
                    </div>

                    <button
                        type="button"
                        className="ml-auto inline-flex size-11 items-center justify-center rounded-lg text-ink hover:bg-aubergine-soft md:hidden"
                        aria-expanded={menuOpen}
                        aria-controls="mobile-menu"
                        onClick={() => setMenuOpen(!menuOpen)}
                    >
                        <span className="sr-only">
                            {menuOpen ? 'Close menu' : 'Open menu'}
                        </span>
                        <svg
                            className="size-6"
                            viewBox="0 0 24 24"
                            fill="none"
                            stroke="currentColor"
                            strokeWidth="2"
                            strokeLinecap="round"
                            aria-hidden="true"
                        >
                            {menuOpen ? (
                                <path d="M6 6l12 12M18 6L6 18" />
                            ) : (
                                <path d="M4 7h16M4 12h16M4 17h16" />
                            )}
                        </svg>
                    </button>
                </div>

                {menuOpen && (
                    <nav
                        id="mobile-menu"
                        aria-label="Mobile"
                        className="border-t border-line px-4 py-3 md:hidden"
                    >
                        <ul className="grid gap-1">
                            {mainLinks.map((link) => (
                                <li key={link.label}>
                                    <NavLink
                                        href={link.href}
                                        active={route().current(link.match)}
                                        className="w-full"
                                    >
                                        {link.label}
                                    </NavLink>
                                </li>
                            ))}

                            <li
                                className="my-2 border-t border-line"
                                role="separator"
                            />

                            {user ? (
                                <>
                                    {accountLinks.map((link) => (
                                        <li key={link.label}>
                                            <NavLink
                                                href={link.href}
                                                className="w-full"
                                            >
                                                {link.label}
                                            </NavLink>
                                        </li>
                                    ))}
                                    <li>
                                        <NavLink
                                            href={route('logout')}
                                            method="post"
                                            as="button"
                                            className="w-full"
                                        >
                                            Log out
                                        </NavLink>
                                    </li>
                                </>
                            ) : (
                                <>
                                    <li>
                                        <NavLink
                                            href={route('login')}
                                            className="w-full"
                                        >
                                            Log in
                                        </NavLink>
                                    </li>
                                    <li>
                                        <NavLink
                                            href={route('register')}
                                            className="w-full"
                                        >
                                            Create account
                                        </NavLink>
                                    </li>
                                </>
                            )}
                        </ul>
                    </nav>
                )}
            </header>

            <RouteAnnouncer />
            <FlashMessages />

            <main
                id="main"
                className={`mx-auto w-full flex-1 px-4 py-8 sm:px-6 sm:py-12 ${
                    narrow ? 'max-w-3xl' : 'max-w-6xl'
                }`}
            >
                {children}
            </main>

            <footer className="border-t border-line bg-paper">
                <div className="mx-auto flex max-w-6xl flex-col gap-3 px-4 py-6 text-sm text-plum sm:flex-row sm:items-center sm:justify-between sm:px-6">
                    <p>
                        CulinaLog, a place to keep and share the recipes you
                        cook.
                    </p>
                    <nav aria-label="Footer">
                        <ul className="flex flex-wrap gap-x-5 gap-y-1">
                            {mainLinks.slice(1).map((link) => (
                                <li key={link.label}>
                                    <Link
                                        href={link.href}
                                        className="inline-flex min-h-11 items-center underline-offset-4 hover:text-aubergine hover:underline"
                                    >
                                        {link.label}
                                    </Link>
                                </li>
                            ))}
                        </ul>
                    </nav>
                </div>
            </footer>
        </div>
    );
}
