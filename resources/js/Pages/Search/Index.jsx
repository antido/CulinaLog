import Button from '@/Components/Button';
import EmptyState from '@/Components/EmptyState';
import InputLabel from '@/Components/InputLabel';
import PageHeader from '@/Components/PageHeader';
import Pagination from '@/Components/Pagination';
import RecipeGrid from '@/Components/RecipeGrid';
import SelectInput from '@/Components/SelectInput';
import TextInput from '@/Components/TextInput';
import TopicList from '@/Components/TopicList';
import AppLayout from '@/Layouts/AppLayout';
import { formatMinutes } from '@/utils';
import { Head, Link, router } from '@inertiajs/react';
import { useState } from 'react';

export default function Index({
    filters,
    results,
    categories,
    difficulties,
    timeLimits,
}) {
    const [form, setForm] = useState(filters);
    const [processing, setProcessing] = useState(false);

    const searchingRecipes = form.type === 'recipes';

    const update = (field, value) => setForm({ ...form, [field]: value });

    // Leave empty filters out of the address so links stay short.
    const search = (values) => {
        const query = Object.fromEntries(
            Object.entries(values).filter(([, value]) => value !== ''),
        );

        router.get(route('search'), query, {
            preserveState: true,
            onStart: () => setProcessing(true),
            onFinish: () => setProcessing(false),
        });
    };

    const submit = (e) => {
        e.preventDefault();
        search(form);
    };

    const switchType = (type) => {
        const next = { ...form, type };
        setForm(next);
        search(next);
    };

    return (
        <AppLayout>
            <Head title="Search" />

            <PageHeader
                title="Search"
                intro="Find recipes by title, ingredient, category or author, or look through forum topics."
            />

            <form
                onSubmit={submit}
                role="search"
                className="rounded-2xl border border-line bg-paper p-5 sm:p-6"
            >
                <div className="flex flex-col gap-3 sm:flex-row sm:items-end">
                    <div className="flex-1">
                        <InputLabel htmlFor="q" value="Search for" />
                        <TextInput
                            id="q"
                            type="search"
                            className="mt-1 block w-full"
                            value={form.q}
                            maxLength={100}
                            placeholder="garlic, soup, bread…"
                            onChange={(e) => update('q', e.target.value)}
                        />
                    </div>
                    <Button type="submit" disabled={processing}>
                        {processing ? 'Searching…' : 'Search'}
                    </Button>
                </div>

                <fieldset className="mt-5">
                    <legend className="text-sm font-medium">Look in</legend>
                    <div className="mt-2 flex flex-wrap gap-2">
                        {[
                            { value: 'recipes', label: 'Recipes' },
                            { value: 'topics', label: 'Forum topics' },
                        ].map((option) => (
                            <button
                                key={option.value}
                                type="button"
                                onClick={() => switchType(option.value)}
                                aria-pressed={form.type === option.value}
                                className={`inline-flex min-h-11 items-center rounded-full border px-4 text-sm font-semibold ${
                                    form.type === option.value
                                        ? 'border-aubergine bg-aubergine text-white'
                                        : 'border-line bg-paper hover:border-aubergine hover:text-aubergine'
                                }`}
                            >
                                {option.label}
                            </button>
                        ))}
                    </div>
                </fieldset>

                <div className="mt-5 grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
                    {searchingRecipes && (
                        <>
                            <div>
                                <InputLabel htmlFor="category" value="Category" />
                                <SelectInput
                                    id="category"
                                    className="mt-1 block w-full"
                                    value={form.category}
                                    onChange={(e) =>
                                        update('category', e.target.value)
                                    }
                                >
                                    <option value="">Any category</option>
                                    {categories.map((category) => (
                                        <option
                                            key={category.slug}
                                            value={category.slug}
                                        >
                                            {category.name}
                                        </option>
                                    ))}
                                </SelectInput>
                            </div>

                            <div>
                                <InputLabel
                                    htmlFor="difficulty"
                                    value="Difficulty"
                                />
                                <SelectInput
                                    id="difficulty"
                                    className="mt-1 block w-full"
                                    value={form.difficulty}
                                    onChange={(e) =>
                                        update('difficulty', e.target.value)
                                    }
                                >
                                    <option value="">Any difficulty</option>
                                    {difficulties.map((difficulty) => (
                                        <option
                                            key={difficulty.value}
                                            value={difficulty.value}
                                        >
                                            {difficulty.label}
                                        </option>
                                    ))}
                                </SelectInput>
                            </div>

                            <div>
                                <InputLabel htmlFor="max_time" value="Ready in" />
                                <SelectInput
                                    id="max_time"
                                    className="mt-1 block w-full"
                                    value={form.max_time}
                                    onChange={(e) =>
                                        update('max_time', e.target.value)
                                    }
                                >
                                    <option value="">Any time</option>
                                    {timeLimits.map((minutes) => (
                                        <option key={minutes} value={minutes}>
                                            {formatMinutes(minutes)} or less
                                        </option>
                                    ))}
                                </SelectInput>
                            </div>
                        </>
                    )}

                    <div>
                        <InputLabel htmlFor="sort" value="Sort by" />
                        <SelectInput
                            id="sort"
                            className="mt-1 block w-full"
                            value={form.sort}
                            onChange={(e) => update('sort', e.target.value)}
                        >
                            <option value="newest">Newest first</option>
                            <option value="oldest">Oldest first</option>
                            <option value="popular">
                                {searchingRecipes
                                    ? 'Most reactions'
                                    : 'Most replies'}
                            </option>
                        </SelectInput>
                    </div>
                </div>

                <div className="mt-5 flex flex-wrap gap-3">
                    <Button type="submit" variant="secondary" disabled={processing}>
                        Apply filters
                    </Button>
                    <Link
                        href={route('search')}
                        className="link inline-flex min-h-11 items-center"
                    >
                        Clear all
                    </Link>
                </div>
            </form>

            <section aria-labelledby="results-heading" className="mt-10">
                <h2
                    id="results-heading"
                    className="mb-5 text-2xl font-bold"
                    aria-live="polite"
                >
                    {results.total}{' '}
                    {filters.type === 'topics'
                        ? results.total === 1
                            ? 'forum topic'
                            : 'forum topics'
                        : results.total === 1
                          ? 'recipe'
                          : 'recipes'}{' '}
                    found
                </h2>

                {results.data.length === 0 ? (
                    <EmptyState
                        title="Nothing matches that search"
                        message="Try fewer words, check the spelling, or remove a filter."
                    />
                ) : filters.type === 'topics' ? (
                    <TopicList topics={results.data} />
                ) : (
                    <RecipeGrid recipes={results.data} />
                )}

                <Pagination paginator={results} className="mt-8" />
            </section>
        </AppLayout>
    );
}
