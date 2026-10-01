import Button from '@/Components/Button';
import EmptyState from '@/Components/EmptyState';
import InputLabel from '@/Components/InputLabel';
import PageHeader from '@/Components/PageHeader';
import Pagination from '@/Components/Pagination';
import TextInput from '@/Components/TextInput';
import TopicList from '@/Components/TopicList';
import AppLayout from '@/Layouts/AppLayout';
import { Head, Link, router } from '@inertiajs/react';
import { useState } from 'react';

export default function Index({ categories, topics, filters }) {
    const [search, setSearch] = useState(filters.q);
    const searching = filters.q !== '';

    const submitSearch = (e) => {
        e.preventDefault();
        router.get(route('forum.index'), search ? { q: search } : {});
    };

    return (
        <AppLayout>
            <Head title="Forum" />

            <PageHeader
                title="Forum"
                intro="Ask a question, compare notes, or settle an argument about rice."
                actions={
                    <Button href={route('forum.topics.create')}>
                        Start a topic
                    </Button>
                }
            />

            <form
                onSubmit={submitSearch}
                role="search"
                className="mb-10 flex flex-col gap-3 sm:flex-row sm:items-end"
            >
                <div className="flex-1">
                    <InputLabel htmlFor="forum-search" value="Search the forum" />
                    <TextInput
                        id="forum-search"
                        type="search"
                        value={search}
                        onChange={(e) => setSearch(e.target.value)}
                        className="mt-1 block w-full"
                    />
                </div>
                <Button type="submit" variant="secondary">
                    Search
                </Button>
            </form>

            {!searching && categories.length > 0 && (
                <section aria-labelledby="forum-categories" className="mb-12">
                    <h2 id="forum-categories" className="mb-4 text-2xl font-bold">
                        Categories
                    </h2>
                    <ul className="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                        {categories.map((category) => (
                            <li key={category.id}>
                                <Link
                                    href={route(
                                        'forum.categories.show',
                                        category.slug,
                                    )}
                                    className="block h-full rounded-2xl border border-line bg-paper p-5 hover:border-aubergine"
                                >
                                    <span className="block text-lg font-semibold">
                                        {category.name}
                                    </span>
                                    {category.description && (
                                        <span className="mt-1 block text-sm text-plum">
                                            {category.description}
                                        </span>
                                    )}
                                    <span className="mt-3 block text-sm font-medium text-aubergine">
                                        {category.topics_count}{' '}
                                        {category.topics_count === 1
                                            ? 'topic'
                                            : 'topics'}
                                    </span>
                                </Link>
                            </li>
                        ))}
                    </ul>
                </section>
            )}

            <section aria-labelledby="forum-topics">
                <h2 id="forum-topics" className="mb-4 text-2xl font-bold">
                    {searching
                        ? `Topics matching "${filters.q}"`
                        : 'Active topics'}
                </h2>

                {searching && (
                    <p className="mb-4">
                        <Link href={route('forum.index')} className="link">
                            Clear search
                        </Link>
                    </p>
                )}

                {topics.data.length === 0 ? (
                    <EmptyState
                        title={
                            searching
                                ? 'No topics match that search'
                                : 'No topics yet'
                        }
                        message={
                            searching
                                ? 'Try fewer or different words.'
                                : 'Start the first conversation.'
                        }
                        action={
                            <Button href={route('forum.topics.create')}>
                                Start a topic
                            </Button>
                        }
                    />
                ) : (
                    <TopicList topics={topics.data} />
                )}

                <Pagination paginator={topics} className="mt-6" />
            </section>
        </AppLayout>
    );
}
