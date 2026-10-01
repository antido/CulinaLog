import PageHeader from '@/Components/PageHeader';
import AppLayout from '@/Layouts/AppLayout';
import { Head } from '@inertiajs/react';
import RecipeForm from './Partials/RecipeForm';

export default function Edit({ recipe, categories, difficulties, statuses }) {
    return (
        <AppLayout narrow>
            <Head title={`Edit ${recipe.title}`} />

            <PageHeader title="Edit recipe" />

            <RecipeForm
                recipe={recipe}
                categories={categories}
                difficulties={difficulties}
                statuses={statuses}
            />
        </AppLayout>
    );
}
