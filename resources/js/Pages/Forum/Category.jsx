import Button from '@/Components/Button';
import EmptyState from '@/Components/EmptyState';
import PageHeader from '@/Components/PageHeader';
import Pagination from '@/Components/Pagination';
import TopicList from '@/Components/TopicList';
import AppLayout from '@/Layouts/AppLayout';
import { Head, Link } from '@inertiajs/react';

export default function Category({ category, topics }) {
    const newTopicUrl = route('forum.topics.create', { category: category.slug });

    return (
        <AppLayout>
            <Head title={`${category.name} forum`} />

            <p className="mb-3 text-sm">
                <Link href={route('forum.index')} className="link">
                    Forum
                </Link>
            </p>

            <PageHeader
                title={category.name}
                intro={category.description}
                actions={<Button href={newTopicUrl}>Start a topic</Button>}
            />

            {topics.data.length === 0 ? (
                <EmptyState
                    title={`No topics in ${category.name} yet`}
                    message="Start the first conversation here."
                    action={<Button href={newTopicUrl}>Start a topic</Button>}
                />
            ) : (
                <TopicList topics={topics.data} showCategory={false} />
            )}

            <Pagination paginator={topics} className="mt-6" />
        </AppLayout>
    );
}
