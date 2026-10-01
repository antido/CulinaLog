import Badge from '@/Components/Badge';
import ManageNav from '@/Components/ManageNav';
import PageHeader from '@/Components/PageHeader';
import AppLayout from '@/Layouts/AppLayout';
import { capitalize } from '@/utils';
import { Head, Link } from '@inertiajs/react';

export default function Index({ permissions }) {
    return (
        <AppLayout>
            <Head title="Permissions" />

            <ManageNav />
            <PageHeader
                title="Permissions"
                intro="Each permission allows one kind of action. The application checks them by name, so this list is fixed."
            />

            <p className="mb-6">
                To change which roles have a permission, go to{' '}
                <Link href={route('admin.roles.index')} className="link">
                    Roles
                </Link>
                .
            </p>

            <ul className="divide-y divide-line rounded-2xl border border-line bg-paper">
                {permissions.map((permission) => (
                    <li
                        key={permission.name}
                        className="flex flex-col gap-2 p-4 sm:flex-row sm:items-center sm:gap-6 sm:p-5"
                    >
                        <div className="min-w-0 flex-1">
                            <p className="break-words font-semibold">
                                {permission.name}
                            </p>
                            <p className="text-sm text-plum">
                                {permission.description}
                            </p>
                        </div>
                        <ul
                            aria-label={`Roles with ${permission.name}`}
                            className="flex flex-wrap gap-2"
                        >
                            {permission.roles.length === 0 ? (
                                <li className="text-sm text-plum">No role</li>
                            ) : (
                                permission.roles.map((role) => (
                                    <li key={role}>
                                        <Badge tone="primary">
                                            {capitalize(role)}
                                        </Badge>
                                    </li>
                                ))
                            )}
                        </ul>
                    </li>
                ))}
            </ul>
        </AppLayout>
    );
}
