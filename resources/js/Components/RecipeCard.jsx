import Thumbnail from '@/Components/Thumbnail';
import { formatMinutes } from '@/utils';
import { Link } from '@inertiajs/react';

/**
 * One recipe in a grid. The whole card is a single link to the recipe.
 */
export default function RecipeCard({ recipe }) {
    return (
        <article className="group relative flex h-full flex-col overflow-hidden rounded-2xl border border-line bg-paper hover:border-aubergine">
            <Thumbnail src={recipe.image_url} />

            <div className="flex flex-1 flex-col p-5">
                <p className="text-sm font-medium text-aubergine">
                    {recipe.category.name}
                </p>

                <h3 className="mt-1 break-words text-xl font-semibold leading-snug">
                    {/* The ::after layer stretches this link over the whole card. */}
                    <Link
                        href={route('recipes.show', recipe.slug)}
                        className="after:absolute after:inset-0 after:rounded-2xl group-hover:text-aubergine"
                    >
                        {recipe.title}
                    </Link>
                </h3>

                <p className="mt-2 line-clamp-2 text-sm text-plum">
                    {recipe.excerpt}
                </p>

                <ul className="mt-auto flex flex-wrap gap-x-4 gap-y-1 pt-4 text-sm text-plum">
                    <li>{formatMinutes(recipe.total_time)}</li>
                    <li>Serves {recipe.servings}</li>
                    <li>{recipe.difficulty}</li>
                </ul>

                <p className="mt-3 truncate border-t border-line pt-3 text-sm text-plum">
                    By {recipe.author.name}
                </p>
            </div>
        </article>
    );
}
