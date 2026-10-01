import Badge from '@/Components/Badge';
import Button from '@/Components/Button';
import ConfirmDialog from '@/Components/ConfirmDialog';
import EmptyState from '@/Components/EmptyState';
import ManageNav from '@/Components/ManageNav';
import PageHeader from '@/Components/PageHeader';
import AppLayout from '@/Layouts/AppLayout';
import { Head, router } from '@inertiajs/react';
import { useState } from 'react';

export default function Index({ categories }) {
    const [categoryToDelete, setCategoryToDelete] = useState(null);
    const [processing, setProcessing] = useState(false);

    const deleteCategory = () => {
        router.delete(
            route('admin.forum-categories.destroy', categoryToDelete.slug),
            {
                onStart: () => setProcessing(true),
                onFinish: () => {
                    setProcessing(false);
                    setCategoryToDelete(null);
                },
            },
        );
    };

    const newButton = (
        <Button href={route('admin.forum-categories.create')}>
            New forum category
        </Button>
    );

    return (
        <AppLayout>
            <Head title="Forum categories" />

            <ManageNav />
            <PageHeader title="Forum categories" actions={newButton} />

            {categories.length === 0 ? (
                <EmptyState
                    title="No forum categories yet"
                    message="Topics need a category. Add the first one."
                    action={newButton}
                />
            ) : (
                <ul className="divide-y divide-line rounded-2xl border border-line bg-paper">
                    {categories.map((category) => (
                        <li
                            key={category.id}
                            className="flex flex-col gap-4 p-4 sm:flex-row sm:items-center sm:p-5"
                        >
                            <div className="min-w-0 flex-1">
                                <div className="flex flex-wrap items-center gap-2">
                                    <p className="font-semibold">
                                        {category.name}
                                    </p>
                                    <Badge
                                        tone={
                                            category.is_active
                                                ? 'success'
                                                : 'neutral'
                                        }
                                    >
                                        {category.is_active
                                            ? 'Active'
                                            : 'Disabled'}
                                    </Badge>
                                </div>
                                <p className="mt-1 text-sm text-plum">
                                    Position {category.sort_order},{' '}
                                    {category.topics_count}{' '}
                                    {category.topics_count === 1
                                        ? 'topic'
                                        : 'topics'}
                                </p>
                            </div>

                            <div className="flex gap-3">
                                <Button
                                    href={route(
                                        'admin.forum-categories.edit',
                                        category.slug,
                                    )}
                                    variant="secondary"
                                    aria-label={`Edit ${category.name}`}
                                >
                                    Edit
                                </Button>
                                <Button
                                    variant="secondary"
                                    onClick={() => setCategoryToDelete(category)}
                                    aria-label={`Delete ${category.name}`}
                                >
                                    Delete
                                </Button>
                            </div>
                        </li>
                    ))}
                </ul>
            )}

            <ConfirmDialog
                show={categoryToDelete !== null}
                title={`Delete "${categoryToDelete?.name}"?`}
                message="A category that still has topics cannot be deleted. Disable it instead to hide it."
                confirmLabel="Delete category"
                processing={processing}
                onConfirm={deleteCategory}
                onClose={() => setCategoryToDelete(null)}
            />
        </AppLayout>
    );
}
