import ManageNav from '@/Components/ManageNav';
import PageHeader from '@/Components/PageHeader';
import RecentList from '@/Components/RecentList';
import StatCard from '@/Components/StatCard';
import AppLayout from '@/Layouts/AppLayout';
import { Head } from '@inertiajs/react';

export default function Dashboard({ stats, recentReports, activity }) {
    return (
        <AppLayout>
            <Head title="Moderation overview" />

            <ManageNav />
            <PageHeader
                title="Moderation overview"
                intro="What is waiting for a moderator, and what moderators did recently."
            />

            <dl className="grid grid-cols-2 gap-4 lg:grid-cols-3">
                {stats.map((stat) => (
                    <StatCard key={stat.label} {...stat} />
                ))}
            </dl>

            <div className="mt-8 grid gap-5 lg:grid-cols-2">
                <RecentList
                    heading="Recent reports"
                    items={recentReports}
                    emptyMessage="No reports yet."
                />
                <RecentList
                    heading="Moderation activity"
                    items={activity}
                    emptyMessage="No moderator has acted yet."
                />
            </div>
        </AppLayout>
    );
}
