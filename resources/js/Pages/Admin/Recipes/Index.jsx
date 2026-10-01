import Button from '@/Components/Button';
import EmptyState from '@/Components/EmptyState';
import InputLabel from '@/Components/InputLabel';
import ManageNav from '@/Components/ManageNav';
import PageHeader from '@/Components/PageHeader';
import Pagination from '@/Components/Pagination';
import RecipeStatusBadge from '@/Components/RecipeStatusBadge';
import SelectInput from '@/Components/SelectInput';
import TextInput from '@/Components/TextInput';
import AppLayout from '@/Layouts/AppLayout';
import { Head, Link, router } from '@inertiajs/react';
import { useState } from 'react';

export default function Index({ recipes, filters, statuses }) {
    const [search, setSearch] = useState(filters.search);
    const [status, setStatus] = useState(filters.status);

    const applyFilters = (e) => {
        e.preventDefault();
        router.get(
            route('admin.recipes.index'),
            { search, status },
            { preserveState: true },
        );
    };

    return (
        <AppLayout>
            <Head title="All recipes" />

            <ManageNav />
            <PageHeader
                title="All recipes"
                intro="Every recipe on the site, including drafts, archived and hidden ones."
            />

            <form
                onSubmit={applyFilters}
                role="search"
                className="mb-6 grid gap-3 sm:grid-cols-[1fr_12rem_auto] sm:items-end"
            >
                <div>
                    <InputLabel htmlFor="search" value="Search by title" />
                    <TextInput
                        id="search"
                        type="search"
                        value={search}
                        onChange={(e) => setSearch(e.target.value)}
                        className="mt-1 block w-full"
                    />
                </div>
                <div>
                    <InputLabel htmlFor="status" value="Status" />
                    <SelectInput
                        id="status"
                        value={status}
                        onChange={(e) => setStatus(e.target.value)}
                        className="mt-1 block w-full"
                    >
                        <option value="">Any status</option>
                        {statuses.map((option) => (
                            <option key={option.value} value={option.value}>
                                {option.label}
                            </option>
                        ))}
                    </SelectInput>
                </div>
                <Button type="submit" variant="secondary">
                    Filter
                </Button>
            </form>

            {recipes.data.length === 0 ? (
                <EmptyState
                    title="No recipes match"
                    message="Try a different title or status."
                />
            ) : (
                <ul className="divide-y divide-line rounded-2xl border border-line bg-paper">
                    {recipes.data.map((recipe) => (
                        <li
                            key={recipe.id}
                            className="flex flex-col gap-3 p-4 sm:flex-row sm:items-center sm:p-5"
                        >
                            <div className="min-w-0 flex-1">
                                <Link
                                    href={route('recipes.show', recipe.slug)}
                                    className="break-words font-semibold hover:text-aubergine"
                                >
                                    {recipe.title}
                                </Link>
                                <p className="mt-1 text-sm text-plum">
                                    By {recipe.author.name} in{' '}
                                    {recipe.category.name}
                                </p>
                                <div className="mt-2">
                                    <RecipeStatusBadge recipe={recipe} />
                                </div>
                            </div>
                            <Button
                                small
                                variant="secondary"
                                href={route('recipes.edit', recipe.slug)}
                                aria-label={`Edit ${recipe.title}`}
                            >
                                Edit
                            </Button>
                        </li>
                    ))}
                </ul>
            )}

            <Pagination paginator={recipes} className="mt-6" />
        </AppLayout>
    );
}
