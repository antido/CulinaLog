import Button from '@/Components/Button';
import EmptyState from '@/Components/EmptyState';
import RecipeGrid from '@/Components/RecipeGrid';
import AppLayout from '@/Layouts/AppLayout';
import { Head, Link, usePage } from '@inertiajs/react';

export default function Home({ recipes, categories }) {
    const { auth } = usePage().props;

    return (
        <AppLayout>
            <Head title="Recipes from home cooks" />

            <section className="max-w-2xl pb-10 sm:py-10">
                <h1 className="text-4xl font-bold tracking-tight sm:text-6xl">
                    Write down what you cook. Share what works.
                </h1>
                <p className="mt-5 text-lg text-plum">
                    CulinaLog is a community recipe book. Keep your own
                    recipes in one place, and talk cooking with other home
                    cooks.
                </p>

                <div className="mt-8 flex flex-wrap gap-3">
                    {auth.user ? (
                        <Button href={route('recipes.create')}>
                            Write a recipe
                        </Button>
                    ) : (
                        <Button href={route('register')}>Create account</Button>
                    )}
                    <Button href={route('recipes.index')} variant="secondary">
                        Browse recipes
                    </Button>
                </div>
            </section>

            {categories.length > 0 && (
                <nav aria-label="Categories" className="mb-12">
                    <ul className="flex flex-wrap gap-2">
                        {categories.map((category) => (
                            <li key={category.slug}>
                                <Link
                                    href={route('categories.show', category.slug)}
                                    className="inline-flex min-h-11 items-center rounded-full border border-line bg-paper px-4 text-sm font-medium hover:border-aubergine hover:text-aubergine"
                                >
                                    {category.name}
                                </Link>
                            </li>
                        ))}
                    </ul>
                </nav>
            )}

            <section aria-labelledby="latest-heading">
                <div className="mb-6 flex items-end justify-between gap-4">
                    <h2 id="latest-heading" className="text-2xl font-bold">
                        Latest recipes
                    </h2>
                    {recipes.length > 0 && (
                        <Link href={route('recipes.index')} className="link">
                            See all recipes
                        </Link>
                    )}
                </div>

                {recipes.length === 0 ? (
                    <EmptyState
                        title="No recipes yet"
                        message="Be the first to share one."
                        action={
                            <Button href={route('recipes.create')}>
                                Write a recipe
                            </Button>
                        }
                    />
                ) : (
                    <RecipeGrid recipes={recipes} />
                )}
            </section>
        </AppLayout>
    );
}
