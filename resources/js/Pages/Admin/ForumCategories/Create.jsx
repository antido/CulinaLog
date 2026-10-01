import Card from '@/Components/Card';
import ManageNav from '@/Components/ManageNav';
import PageHeader from '@/Components/PageHeader';
import AppLayout from '@/Layouts/AppLayout';
import { Head } from '@inertiajs/react';
import ForumCategoryForm from './Partials/ForumCategoryForm';

export default function Create() {
    return (
        <AppLayout>
            <Head title="New forum category" />

            <ManageNav />
            <PageHeader title="New forum category" />

            <Card className="max-w-2xl">
                <ForumCategoryForm />
            </Card>
        </AppLayout>
    );
}
