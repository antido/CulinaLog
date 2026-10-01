import Badge from '@/Components/Badge';
import Button from '@/Components/Button';
import ConfirmDialog from '@/Components/ConfirmDialog';
import EmptyState from '@/Components/EmptyState';
import ManageNav from '@/Components/ManageNav';
import PageHeader from '@/Components/PageHeader';
import Pagination from '@/Components/Pagination';
import Thumbnail from '@/Components/Thumbnail';
import AppLayout from '@/Layouts/AppLayout';
import { Head, router } from '@inertiajs/react';
import { useState } from 'react';

export default function Index({ categories }) {
    const [categoryToDelete, setCategoryToDelete] = useState(null);
    const [processing, setProcessing] = useState(false);

    const deleteCategory = () => {
        router.delete(route('admin.categories.destroy', categoryToDelete.slug), {
            onStart: () => setProcessing(true),
            onFinish: () => {
                setProcessing(false);
                setCategoryToDelete(null);
            },
        });
    };

    return (
        <AppLayout>
            <Head title="Categories" />

            <ManageNav />
            <PageHeader
                title="Recipe categories"
                actions={
                    <Button href={route('admin.categories.create')}>
                        New category
                    </Button>
                }
            />

            {categories.data.length === 0 ? (
                <EmptyState
                    title="No categories yet"
                    message="Recipes need a category. Add the first one."
                    action={
                        <Button href={route('admin.categories.create')}>
                            New category
                        </Button>
                    }
                />
            ) : (
                <ul className="divide-y divide-line rounded-2xl border border-line bg-paper">
                    {categories.data.map((category) => (
                        <li
                            key={category.id}
                            className="flex flex-col gap-4 p-4 sm:flex-row sm:items-center sm:p-5"
                        >
                            <div className="w-24 shrink-0 overflow-hidden rounded-lg">
                                <Thumbnail src={category.image_url} />
                            </div>

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
                                {category.description && (
                                    <p className="mt-1 line-clamp-2 text-sm text-plum">
                                        {category.description}
                                    </p>
                                )}
                            </div>

                            <div className="flex gap-3">
                                <Button
                                    href={route(
                                        'admin.categories.edit',
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

            <Pagination paginator={categories} className="mt-6" />

            <ConfirmDialog
                show={categoryToDelete !== null}
                title={`Delete "${categoryToDelete?.name}"?`}
                message="A category that still has recipes cannot be deleted. Disable it instead to hide it."
                confirmLabel="Delete category"
                processing={processing}
                onConfirm={deleteCategory}
                onClose={() => setCategoryToDelete(null)}
            />
        </AppLayout>
    );
}
