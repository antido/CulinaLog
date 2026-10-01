import PageHeader from '@/Components/PageHeader';
import AppLayout from '@/Layouts/AppLayout';
import { Head } from '@inertiajs/react';
import RecipeForm from './Partials/RecipeForm';

export default function Create({ categories, difficulties, statuses }) {
    return (
        <AppLayout narrow>
            <Head title="Write a recipe" />

            <PageHeader title="Write a recipe" />

            <RecipeForm
                categories={categories}
                difficulties={difficulties}
                statuses={statuses}
            />
        </AppLayout>
    );
}
