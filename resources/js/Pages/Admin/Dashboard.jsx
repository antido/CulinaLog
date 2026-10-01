import ManageNav from '@/Components/ManageNav';
import PageHeader from '@/Components/PageHeader';
import RecentList from '@/Components/RecentList';
import StatCard from '@/Components/StatCard';
import AppLayout from '@/Layouts/AppLayout';
import { Head } from '@inertiajs/react';

export default function Dashboard({
    stats,
    recentRecipes,
    recentComments,
    recentUsers,
    recentTopics,
}) {
    return (
        <AppLayout>
            <Head title="Admin overview" />

            <ManageNav />
            <PageHeader title="Admin overview" />

            <dl className="grid grid-cols-2 gap-4 lg:grid-cols-4">
                {stats.map((stat) => (
                    <StatCard key={stat.label} {...stat} />
                ))}
            </dl>

            <div className="mt-8 grid gap-5 lg:grid-cols-2">
                <RecentList
                    heading="Recent recipes"
                    items={recentRecipes}
                    emptyMessage="No recipes yet."
                />
                <RecentList
                    heading="Recent comments"
                    items={recentComments}
                    emptyMessage="No comments yet."
                />
                <RecentList
                    heading="Recent users"
                    items={recentUsers}
                    emptyMessage="No users yet."
                />
                <RecentList
                    heading="Recent forum topics"
                    items={recentTopics}
                    emptyMessage="No forum topics yet."
                />
            </div>
        </AppLayout>
    );
}
