import Button from '@/Components/Button';
import Card from '@/Components/Card';
import ConfirmDialog from '@/Components/ConfirmDialog';
import InputError from '@/Components/InputError';
import InputLabel from '@/Components/InputLabel';
import ManageNav from '@/Components/ManageNav';
import PageHeader from '@/Components/PageHeader';
import TextInput from '@/Components/TextInput';
import AppLayout from '@/Layouts/AppLayout';
import { Head, router, useForm } from '@inertiajs/react';
import { useState } from 'react';
import RoleCard from './Partials/RoleCard';

export default function Index({ roles, permissions }) {
    const [roleToDelete, setRoleToDelete] = useState(null);
    const [processing, setProcessing] = useState(false);

    const newRole = useForm({ name: '', permissions: [] });

    const createRole = (e) => {
        e.preventDefault();
        newRole.post(route('admin.roles.store'), {
            preserveScroll: true,
            onSuccess: () => newRole.reset(),
        });
    };

    const deleteRole = () => {
        router.delete(route('admin.roles.destroy', roleToDelete.id), {
            preserveScroll: true,
            onStart: () => setProcessing(true),
            onFinish: () => {
                setProcessing(false);
                setRoleToDelete(null);
            },
        });
    };

    return (
        <AppLayout>
            <Head title="Roles" />

            <ManageNav />
            <PageHeader
                title="Roles"
                intro="A role is a named set of permissions. Every user has one role; change it on the Users page."
            />

            <div className="space-y-6">
                {roles.map((role) => (
                    // The key includes the permissions so the card resets after a save.
                    <RoleCard
                        key={`${role.id}-${role.permissions.join(',')}`}
                        role={role}
                        permissions={permissions}
                        onDelete={setRoleToDelete}
                    />
                ))}

                <Card as="section">
                    <h2 className="text-xl font-semibold">Add a role</h2>
                    <p className="mt-1 text-sm text-plum">
                        The new role starts with no permissions. Tick them on
                        its card after creating it.
                    </p>

                    <form
                        onSubmit={createRole}
                        className="mt-4 flex flex-col gap-3 sm:flex-row sm:items-end"
                    >
                        <div className="flex-1">
                            <InputLabel htmlFor="role-name" value="Role name" />
                            <TextInput
                                id="role-name"
                                className="mt-1 block w-full"
                                value={newRole.data.name}
                                maxLength={30}
                                placeholder="recipe_editor"
                                onChange={(e) =>
                                    newRole.setData(
                                        'name',
                                        e.target.value.toLowerCase(),
                                    )
                                }
                                aria-describedby="role-name-hint"
                                required
                            />
                        </div>
                        <Button type="submit" disabled={newRole.processing}>
                            Add role
                        </Button>
                    </form>
                    <p id="role-name-hint" className="mt-2 text-sm text-plum">
                        Lowercase letters and underscores, 3 to 30 characters.
                    </p>
                    <InputError className="mt-2" message={newRole.errors.name} />
                </Card>
            </div>

            <ConfirmDialog
                show={roleToDelete !== null}
                title={`Delete the role "${roleToDelete?.name}"?`}
                message="A role that still has users cannot be deleted."
                confirmLabel="Delete role"
                processing={processing}
                onConfirm={deleteRole}
                onClose={() => setRoleToDelete(null)}
            />
        </AppLayout>
    );
}
