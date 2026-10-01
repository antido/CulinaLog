import Badge from '@/Components/Badge';
import Button from '@/Components/Button';
import ConfirmDialog from '@/Components/ConfirmDialog';
import EmptyState from '@/Components/EmptyState';
import InputLabel from '@/Components/InputLabel';
import ManageNav from '@/Components/ManageNav';
import PageHeader from '@/Components/PageHeader';
import Pagination from '@/Components/Pagination';
import SelectInput from '@/Components/SelectInput';
import TextInput from '@/Components/TextInput';
import AppLayout from '@/Layouts/AppLayout';
import { capitalize } from '@/utils';
import { Head, router } from '@inertiajs/react';
import { useState } from 'react';

export default function Index({ users, roles, filters }) {
    const [search, setSearch] = useState(filters.search);
    const [userToDelete, setUserToDelete] = useState(null);
    const [processing, setProcessing] = useState(false);

    const submitSearch = (e) => {
        e.preventDefault();
        router.get(route('admin.users.index'), { search }, { preserveState: true });
    };

    const changeRole = (user, role) => {
        router.patch(
            route('admin.users.role', user.id),
            { role },
            { preserveScroll: true },
        );
    };

    const deleteUser = () => {
        router.delete(route('admin.users.destroy', userToDelete.id), {
            preserveScroll: true,
            onStart: () => setProcessing(true),
            onFinish: () => {
                setProcessing(false);
                setUserToDelete(null);
            },
        });
    };

    return (
        <AppLayout>
            <Head title="Users" />

            <ManageNav />
            <PageHeader
                title="Users"
                intro="Change a member's role or remove their account. You cannot change your own role."
            />

            <form
                onSubmit={submitSearch}
                role="search"
                className="mb-6 flex flex-col gap-3 sm:flex-row sm:items-end"
            >
                <div className="flex-1">
                    <InputLabel htmlFor="search" value="Search by name or email" />
                    <TextInput
                        id="search"
                        type="search"
                        value={search}
                        onChange={(e) => setSearch(e.target.value)}
                        className="mt-1 block w-full"
                    />
                </div>
                <Button type="submit" variant="secondary">
                    Search
                </Button>
            </form>

            {users.data.length === 0 ? (
                <EmptyState
                    title="No users match that search"
                    message="Try a different name or email address."
                />
            ) : (
                <ul className="divide-y divide-line rounded-2xl border border-line bg-paper">
                    {users.data.map((user) => (
                        <li
                            key={user.id}
                            className="flex flex-col gap-4 p-4 sm:p-5 md:flex-row md:items-center"
                        >
                            <div className="min-w-0 flex-1">
                                <p className="truncate font-semibold">
                                    {user.name}
                                </p>
                                <p className="truncate text-sm text-plum">
                                    {user.email}
                                </p>
                                <p className="mt-1 text-sm text-plum">
                                    Joined {user.joined}
                                </p>
                            </div>

                            {user.can_manage ? (
                                <div className="flex flex-wrap items-end gap-3">
                                    <div>
                                        <InputLabel
                                            htmlFor={`role-${user.id}`}
                                            value={`Role for ${user.name}`}
                                            className="sr-only"
                                        />
                                        <SelectInput
                                            id={`role-${user.id}`}
                                            value={user.role ?? ''}
                                            onChange={(e) =>
                                                changeRole(user, e.target.value)
                                            }
                                        >
                                            {!user.role && (
                                                <option value="">No role</option>
                                            )}
                                            {roles.map((role) => (
                                                <option key={role} value={role}>
                                                    {capitalize(role)}
                                                </option>
                                            ))}
                                        </SelectInput>
                                    </div>
                                    <Button
                                        variant="secondary"
                                        onClick={() => setUserToDelete(user)}
                                    >
                                        Delete
                                    </Button>
                                </div>
                            ) : (
                                <Badge tone="primary" className="self-start md:self-auto">
                                    {capitalize(user.role ?? 'No role')}
                                </Badge>
                            )}
                        </li>
                    ))}
                </ul>
            )}

            <Pagination paginator={users} className="mt-6" />

            <ConfirmDialog
                show={userToDelete !== null}
                title={`Delete ${userToDelete?.name}'s account?`}
                message="Their recipes, comments and forum posts are deleted with the account. This cannot be undone."
                confirmLabel="Delete account"
                processing={processing}
                onConfirm={deleteUser}
                onClose={() => setUserToDelete(null)}
            />
        </AppLayout>
    );
}
