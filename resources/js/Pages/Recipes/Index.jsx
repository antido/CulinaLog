import Button from '@/Components/Button';
import EmptyState from '@/Components/EmptyState';
import PageHeader from '@/Components/PageHeader';
import Pagination from '@/Components/Pagination';
import RecipeGrid from '@/Components/RecipeGrid';
import AppLayout from '@/Layouts/AppLayout';
import { Head } from '@inertiajs/react';

export default function Index({ recipes }) {
    return (
        <AppLayout>
            <Head title="Recipes" />

            <PageHeader
                title="Recipes"
                intro="Everything the community has published, newest first."
                actions={
                    <Button href={route('recipes.create')}>Write a recipe</Button>
                }
            />

            {recipes.data.length === 0 ? (
                <EmptyState
                    title="No recipes yet"
                    message="Be the first to share one."
                    action={
                        <Button href={route('recipes.create')}>
                            Write a recipe
                        </Button>
                    }
                />
            ) : (
                <RecipeGrid recipes={recipes.data} />
            )}

            <Pagination paginator={recipes} className="mt-8" />
        </AppLayout>
    );
}
