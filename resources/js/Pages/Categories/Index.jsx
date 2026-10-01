import EmptyState from '@/Components/EmptyState';
import PageHeader from '@/Components/PageHeader';
import Thumbnail from '@/Components/Thumbnail';
import AppLayout from '@/Layouts/AppLayout';
import { Head, Link } from '@inertiajs/react';

export default function Index({ categories }) {
    return (
        <AppLayout>
            <Head title="Categories" />

            <PageHeader
                title="Categories"
                intro="Browse recipes by the kind of dish."
            />

            {categories.length === 0 ? (
                <EmptyState
                    title="No categories yet"
                    message="Categories appear here once an admin adds them."
                />
            ) : (
                <ul className="grid gap-5 sm:grid-cols-2 lg:grid-cols-3">
                    {categories.map((category) => (
                        <li key={category.id}>
                            <Link
                                href={route('categories.show', category.slug)}
                                className="group block overflow-hidden rounded-2xl border border-line bg-paper hover:border-aubergine"
                            >
                                <Thumbnail
                                    src={category.image_url}
                                    className="aspect-[16/9]"
                                />
                                <div className="p-5">
                                    <h2 className="text-xl font-semibold group-hover:text-aubergine">
                                        {category.name}
                                    </h2>
                                    {category.description && (
                                        <p className="mt-1 line-clamp-2 text-plum">
                                            {category.description}
                                        </p>
                                    )}
                                </div>
                            </Link>
                        </li>
                    ))}
                </ul>
            )}
        </AppLayout>
    );
}
