import Card from '@/Components/Card';
import ManageNav from '@/Components/ManageNav';
import PageHeader from '@/Components/PageHeader';
import AppLayout from '@/Layouts/AppLayout';
import { Head } from '@inertiajs/react';
import CategoryForm from './Partials/CategoryForm';

export default function Create() {
    return (
        <AppLayout>
            <Head title="New category" />

            <ManageNav />
            <PageHeader title="New category" />

            <Card className="max-w-2xl">
                <CategoryForm />
            </Card>
        </AppLayout>
    );
}
