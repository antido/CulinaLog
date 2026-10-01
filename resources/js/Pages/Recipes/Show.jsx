import Avatar from '@/Components/Avatar';
import Button from '@/Components/Button';
import ConfirmDialog from '@/Components/ConfirmDialog';
import RecipeStatusBadge from '@/Components/RecipeStatusBadge';
import Thumbnail from '@/Components/Thumbnail';
import AppLayout from '@/Layouts/AppLayout';
import { formatMinutes, useRequest } from '@/utils';
import { Head, Link, router } from '@inertiajs/react';
import { useState } from 'react';
import DiscussionThread from '@/Components/Discussion/DiscussionThread';
import PostActions from '@/Components/Discussion/PostActions';
import ReportButton from '@/Components/ReportButton';
import IngredientChecklist from './Partials/IngredientChecklist';
import ReactionBar from './Partials/ReactionBar';

export default function Show({
    recipe,
    can,
    reactions,
    comments,
    commentCount,
}) {
    const [confirmingDelete, setConfirmingDelete] = useState(false);
    const [processing, setProcessing] = useState(false);

    const isPublic = recipe.status === 'published' && !recipe.is_hidden;

    const deleteRecipe = () => {
        router.delete(route('recipes.destroy', recipe.slug), {
            onStart: () => setProcessing(true),
            onFinish: () => setProcessing(false),
        });
    };

    const { busy, send } = useRequest();

    const setHidden = (hidden) => {
        send(
            'patch',
            route(
                hidden ? 'moderation.recipes.hide' : 'moderation.recipes.restore',
                recipe.slug,
            ),
        );
    };

    const facts = [
        { label: 'Preparation', value: formatMinutes(recipe.prep_time) },
        { label: 'Cooking', value: formatMinutes(recipe.cook_time) },
        { label: 'Servings', value: recipe.servings },
        { label: 'Difficulty', value: recipe.difficulty },
    ];

    return (
        <AppLayout>
            {/* Matches the title the server prints; see App\Services\SeoService. */}
            <Head title={`${recipe.title} by ${recipe.author.name}`} />

            <article>
                {!isPublic && (
                    <div className="mb-6 rounded-xl border border-saffron/50 bg-saffron-soft px-4 py-3">
                        <RecipeStatusBadge recipe={recipe} />
                        <p className="mt-2 text-sm">
                            This recipe is not public. Only its author and
                            moderators can see this page.
                        </p>
                    </div>
                )}

                <header className="grid gap-8 lg:grid-cols-2 lg:items-center">
                    <div className="min-w-0">
                        <p className="text-sm font-medium">
                            <Link
                                href={route('categories.show', recipe.category.slug)}
                                className="link"
                            >
                                {recipe.category.name}
                            </Link>
                        </p>

                        <h1 className="mt-2 break-words text-4xl font-bold tracking-tight sm:text-5xl">
                            {recipe.title}
                        </h1>

                        <Link
                            href={route('users.show', recipe.author.username)}
                            className="mt-5 inline-flex min-h-11 items-center gap-3 rounded-lg"
                        >
                            <Avatar user={recipe.author} />
                            <span>
                                <span className="block font-medium">
                                    {recipe.author.name}
                                </span>
                                {recipe.published && (
                                    <span className="block text-sm text-plum">
                                        Published {recipe.published}
                                    </span>
                                )}
                            </span>
                        </Link>

                        <dl className="mt-6 grid grid-cols-2 gap-x-6 gap-y-4 border-y border-line py-5 sm:grid-cols-4">
                            {facts.map((fact) => (
                                <div key={fact.label}>
                                    <dt className="text-sm text-plum">
                                        {fact.label}
                                    </dt>
                                    <dd className="mt-0.5 font-semibold">
                                        {fact.value}
                                    </dd>
                                </div>
                            ))}
                        </dl>

                        {(can.update || can.delete || can.moderate) && (
                            <div className="mt-6 flex flex-wrap gap-3">
                                {can.update && (
                                    <Button
                                        href={route('recipes.edit', recipe.slug)}
                                        variant="secondary"
                                    >
                                        Edit
                                    </Button>
                                )}
                                {can.delete && (
                                    <Button
                                        variant="secondary"
                                        onClick={() => setConfirmingDelete(true)}
                                    >
                                        Delete
                                    </Button>
                                )}
                                {can.moderate && (
                                    <Button
                                        variant="secondary"
                                        disabled={busy}
                                        onClick={() => setHidden(!recipe.is_hidden)}
                                    >
                                        {recipe.is_hidden
                                            ? 'Restore (moderator)'
                                            : 'Hide (moderator)'}
                                    </Button>
                                )}
                            </div>
                        )}
                    </div>

                    <div className="overflow-hidden rounded-2xl border border-line">
                        <Thumbnail
                            src={recipe.image_url}
                            alt={`Photo of ${recipe.title}`}
                            eager
                        />
                    </div>
                </header>

                <p className="user-text mt-10 max-w-3xl text-lg">
                    {recipe.description}
                </p>

                <div className="mt-12 grid gap-10 lg:grid-cols-[20rem_1fr] lg:gap-14">
                    <section aria-labelledby="ingredients-heading">
                        <h2
                            id="ingredients-heading"
                            className="text-2xl font-bold"
                        >
                            Ingredients
                        </h2>
                        <p className="mt-1 text-sm text-plum">
                            Tick each one off as you gather it.
                        </p>
                        <IngredientChecklist ingredients={recipe.ingredients} />
                    </section>

                    <section aria-labelledby="instructions-heading">
                        <h2
                            id="instructions-heading"
                            className="text-2xl font-bold"
                        >
                            Instructions
                        </h2>
                        <ol className="mt-5 space-y-6">
                            {recipe.instructions.map((step) => (
                                <li key={step.id} className="flex gap-4">
                                    <span
                                        aria-hidden="true"
                                        className="flex size-9 shrink-0 items-center justify-center rounded-full bg-aubergine font-display font-bold text-white"
                                    >
                                        {step.step_number}
                                    </span>
                                    <p className="user-text min-w-0 flex-1 pt-1 text-lg">
                                        <span className="sr-only">
                                            Step {step.step_number}:{' '}
                                        </span>
                                        {step.instruction}
                                    </p>
                                </li>
                            ))}
                        </ol>
                    </section>
                </div>
            </article>

            {isPublic && (
                <>
                    <section
                        aria-labelledby="reactions-heading"
                        className="mt-14 border-t border-line pt-8"
                    >
                        <h2
                            id="reactions-heading"
                            className="mb-4 text-lg font-semibold"
                        >
                            What did you think of this recipe?
                        </h2>
                        <div className="flex flex-wrap items-center justify-between gap-3">
                            <ReactionBar
                                recipeSlug={recipe.slug}
                                counts={reactions.counts}
                                mine={reactions.mine}
                                canReact={can.react}
                            />
                            <ReportButton
                                type="recipe"
                                id={recipe.id}
                                noun="recipe"
                            />
                        </div>
                    </section>

                    <DiscussionThread
                        heading={`Comments (${commentCount})`}
                        noun="comment"
                        items={comments}
                        storeUrl={route('comments.store', recipe.slug)}
                        deleteRoute="comments.destroy"
                        canPost={can.comment}
                        closedMessage="Comments are closed for this recipe."
                        guestNote="You are commenting as a guest."
                        allowGuestName
                        emptyTitle="No comments yet"
                        emptyMessage="Cooked it? Say how it went."
                        extra={(comment) => (
                            <PostActions
                                item={comment}
                                noun="comment"
                                reportType="comment"
                                hideRoute="moderation.comments.update"
                                permission="comments.moderate"
                            />
                        )}
                    />
                </>
            )}

            <ConfirmDialog
                show={confirmingDelete}
                title={`Delete "${recipe.title}"?`}
                message="The recipe and everything attached to it are removed. This cannot be undone."
                confirmLabel="Delete recipe"
                processing={processing}
                onConfirm={deleteRecipe}
                onClose={() => setConfirmingDelete(false)}
            />
        </AppLayout>
    );
}
