import Button from '@/Components/Button';
import EmptyState from '@/Components/EmptyState';
import PageHeader from '@/Components/PageHeader';
import Pagination from '@/Components/Pagination';
import RecipeStatusBadge from '@/Components/RecipeStatusBadge';
import Thumbnail from '@/Components/Thumbnail';
import AppLayout from '@/Layouts/AppLayout';
import { useRequest } from '@/utils';
import { Head, Link, usePage } from '@inertiajs/react';

export default function Dashboard({ recipes }) {
    const { auth } = usePage().props;
    const { busy, send } = useRequest();

    const setStatus = (recipe, status) => {
        send('patch', route('recipes.status', recipe.slug), { status });
    };

    return (
        <AppLayout>
            <Head title="My recipes" />

            <PageHeader
                title="My recipes"
                intro={`Signed in as ${auth.user.name}. Drafts and archived recipes are visible only to you.`}
                actions={
                    <Button href={route('recipes.create')}>Write a recipe</Button>
                }
            />

            {recipes.data.length === 0 ? (
                <EmptyState
                    title="You have not written a recipe yet"
                    message="Start with a dish you know by heart. You can save it as a draft and publish later."
                    action={
                        <Button href={route('recipes.create')}>
                            Write a recipe
                        </Button>
                    }
                />
            ) : (
                <ul className="divide-y divide-line rounded-2xl border border-line bg-paper">
                    {recipes.data.map((recipe) => (
                        <li
                            key={recipe.id}
                            className="flex flex-col gap-4 p-4 sm:flex-row sm:items-center sm:p-5"
                        >
                            <div className="w-full shrink-0 overflow-hidden rounded-lg sm:w-28">
                                <Thumbnail src={recipe.image_url} />
                            </div>

                            <div className="min-w-0 flex-1">
                                <Link
                                    href={route('recipes.show', recipe.slug)}
                                    className="break-words font-semibold hover:text-aubergine"
                                >
                                    {recipe.title}
                                </Link>
                                <p className="mt-1 text-sm text-plum">
                                    {recipe.category.name}
                                </p>
                                <div className="mt-2">
                                    <RecipeStatusBadge recipe={recipe} />
                                </div>
                            </div>

                            <div className="flex flex-wrap gap-2">
                                {recipe.status !== 'published' && (
                                    <Button
                                        disabled={busy}
                                        onClick={() =>
                                            setStatus(recipe, 'published')
                                        }
                                        aria-label={`Publish ${recipe.title}`}
                                    >
                                        Publish
                                    </Button>
                                )}
                                {recipe.status === 'published' && (
                                    <Button
                                        variant="secondary"
                                        disabled={busy}
                                        onClick={() =>
                                            setStatus(recipe, 'archived')
                                        }
                                        aria-label={`Archive ${recipe.title}`}
                                    >
                                        Archive
                                    </Button>
                                )}
                                <Button
                                    variant="secondary"
                                    href={route('recipes.edit', recipe.slug)}
                                    aria-label={`Edit ${recipe.title}`}
                                >
                                    Edit
                                </Button>
                            </div>
                        </li>
                    ))}
                </ul>
            )}

            <Pagination paginator={recipes} className="mt-6" />
        </AppLayout>
    );
}
