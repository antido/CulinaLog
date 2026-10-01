import Card from '@/Components/Card';
import ManageNav from '@/Components/ManageNav';
import PageHeader from '@/Components/PageHeader';
import AppLayout from '@/Layouts/AppLayout';
import { Head } from '@inertiajs/react';
import ForumCategoryForm from './Partials/ForumCategoryForm';

export default function Edit({ category }) {
    return (
        <AppLayout>
            <Head title={`Edit ${category.name}`} />

            <ManageNav />
            <PageHeader title={`Edit ${category.name}`} />

            <Card className="max-w-2xl">
                <ForumCategoryForm category={category} />
            </Card>
        </AppLayout>
    );
}
