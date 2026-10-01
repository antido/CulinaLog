import EmptyState from '@/Components/EmptyState';
import PageHeader from '@/Components/PageHeader';
import AppLayout from '@/Layouts/AppLayout';
import { Head } from '@inertiajs/react';
import TopicForm from './Partials/TopicForm';

export default function Create({ categories, selectedCategory }) {
    return (
        <AppLayout narrow>
            <Head title="Start a topic" />

            <PageHeader title="Start a topic" />

            {categories.length === 0 ? (
                <EmptyState
                    title="The forum has no categories yet"
                    message="An admin needs to add a forum category before topics can be posted."
                />
            ) : (
                <TopicForm
                    categories={categories}
                    selectedCategory={selectedCategory}
                />
            )}
        </AppLayout>
    );
}
