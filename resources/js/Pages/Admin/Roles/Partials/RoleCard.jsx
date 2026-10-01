import Badge from '@/Components/Badge';
import Button from '@/Components/Button';
import Card from '@/Components/Card';
import Checkbox from '@/Components/Checkbox';
import InputError from '@/Components/InputError';
import { capitalize } from '@/utils';
import { useForm } from '@inertiajs/react';

/**
 * One role with a checkbox for each permission.
 * The admin role is shown but cannot be changed.
 */
export default function RoleCard({ role, permissions, onDelete }) {
    const { data, setData, put, processing, errors, isDirty } = useForm({
        permissions: role.permissions,
    });

    const toggle = (name, checked) => {
        setData(
            'permissions',
            checked
                ? [...data.permissions, name]
                : data.permissions.filter((permission) => permission !== name),
        );
    };

    const submit = (e) => {
        e.preventDefault();
        put(route('admin.roles.update', role.id), { preserveScroll: true });
    };

    return (
        <Card as="section">
            <div className="flex flex-wrap items-center gap-3">
                <h2 className="text-xl font-semibold">{capitalize(role.name)}</h2>
                {role.is_built_in && <Badge>Built in</Badge>}
                <span className="text-sm text-plum">
                    {role.users_count}{' '}
                    {role.users_count === 1 ? 'user' : 'users'}
                </span>
            </div>

            {role.is_locked && (
                <p className="mt-2 text-sm text-plum">
                    The admin role always has every permission, so admins
                    cannot be locked out by mistake.
                </p>
            )}

            <form onSubmit={submit} className="mt-4">
                <fieldset disabled={role.is_locked}>
                    <legend className="sr-only">
                        Permissions for {role.name}
                    </legend>
                    <ul className="grid gap-x-6 sm:grid-cols-2">
                        {permissions.map((permission) => (
                            <li key={permission.name}>
                                <label className="flex min-h-11 items-start gap-3 py-2">
                                    <Checkbox
                                        className="mt-0.5 shrink-0"
                                        checked={data.permissions.includes(
                                            permission.name,
                                        )}
                                        onChange={(e) =>
                                            toggle(
                                                permission.name,
                                                e.target.checked,
                                            )
                                        }
                                    />
                                    <span className="min-w-0">
                                        <span className="block break-words text-sm font-medium">
                                            {permission.name}
                                        </span>
                                        <span className="block text-sm text-plum">
                                            {permission.description}
                                        </span>
                                    </span>
                                </label>
                            </li>
                        ))}
                    </ul>
                </fieldset>

                <InputError className="mt-2" message={errors.permissions} />

                {!role.is_locked && (
                    <div className="mt-4 flex flex-wrap gap-3">
                        <Button type="submit" disabled={processing || !isDirty}>
                            {processing ? 'Saving…' : 'Save permissions'}
                        </Button>
                        {!role.is_built_in && (
                            <Button
                                variant="secondary"
                                onClick={() => onDelete(role)}
                            >
                                Delete role
                            </Button>
                        )}
                    </div>
                )}
            </form>
        </Card>
    );
}
