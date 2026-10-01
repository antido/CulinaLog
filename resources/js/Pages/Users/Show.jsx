import Avatar from '@/Components/Avatar';
import Button from '@/Components/Button';
import EmptyState from '@/Components/EmptyState';
import Pagination from '@/Components/Pagination';
import RecipeGrid from '@/Components/RecipeGrid';
import StatCard from '@/Components/StatCard';
import AppLayout from '@/Layouts/AppLayout';
import { Head, usePage } from '@inertiajs/react';

export default function Show({ profile, stats, recipes }) {
    const { auth } = usePage().props;
    const isOwnProfile = auth.user?.id === profile.id;

    return (
        <AppLayout>
            <Head title={`${profile.name} (@${profile.username})`} />

            <header className="flex flex-col gap-5 sm:flex-row sm:items-center">
                <Avatar user={profile} size="lg" />

                <div className="min-w-0 flex-1">
                    <h1 className="break-words text-3xl font-bold tracking-tight sm:text-4xl">
                        {profile.name}
                    </h1>
                    <p className="mt-1 text-plum">
                        @{profile.username}, joined {profile.joined}
                    </p>
                </div>

                {isOwnProfile && (
                    <Button href={route('profile.edit')} variant="secondary">
                        Edit profile
                    </Button>
                )}
            </header>

            {profile.bio && (
                <p className="user-text mt-6 max-w-2xl text-lg">{profile.bio}</p>
            )}

            <dl className="mt-8 grid grid-cols-2 gap-4 lg:grid-cols-4">
                {stats.map((stat) => (
                    <StatCard key={stat.label} {...stat} />
                ))}
            </dl>

            <section aria-labelledby="recipes-heading" className="mt-12">
                <h2 id="recipes-heading" className="mb-6 text-2xl font-bold">
                    Recipes
                </h2>

                {recipes.data.length === 0 ? (
                    <EmptyState
                        title="No published recipes yet"
                        message={
                            isOwnProfile
                                ? 'Recipes you publish appear here.'
                                : `${profile.name} has not published a recipe yet.`
                        }
                    />
                ) : (
                    <RecipeGrid recipes={recipes.data} />
                )}

                <Pagination paginator={recipes} className="mt-8" />
            </section>
        </AppLayout>
    );
}
