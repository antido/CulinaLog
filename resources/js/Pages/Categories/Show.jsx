import Badge from '@/Components/Badge';
import EmptyState from '@/Components/EmptyState';
import PageHeader from '@/Components/PageHeader';
import Pagination from '@/Components/Pagination';
import RecipeGrid from '@/Components/RecipeGrid';
import AppLayout from '@/Layouts/AppLayout';
import { Head, Link } from '@inertiajs/react';

export default function Show({ category, recipes }) {
    return (
        <AppLayout>
            <Head title={`${category.name} recipes`} />

            <p className="mb-3 text-sm">
                <Link href={route('categories.index')} className="link">
                    All categories
                </Link>
            </p>

            {!category.is_active && (
                <Badge tone="warning" className="mb-3">
                    Disabled: only admins can see this category
                </Badge>
            )}

            <PageHeader title={category.name} intro={category.description} />

            {recipes.data.length === 0 ? (
                <EmptyState
                    title={`No recipes in ${category.name} yet`}
                    message="Recipes appear here when members publish them in this category."
                />
            ) : (
                <RecipeGrid recipes={recipes.data} />
            )}

            <Pagination paginator={recipes} className="mt-8" />
        </AppLayout>
    );
}
