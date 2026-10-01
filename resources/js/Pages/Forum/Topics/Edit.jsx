import PageHeader from '@/Components/PageHeader';
import AppLayout from '@/Layouts/AppLayout';
import { Head } from '@inertiajs/react';
import TopicForm from './Partials/TopicForm';

export default function Edit({ topic, categories }) {
    return (
        <AppLayout narrow>
            <Head title={`Edit ${topic.title}`} />

            <PageHeader title="Edit topic" />

            <TopicForm topic={topic} categories={categories} />
        </AppLayout>
    );
}
