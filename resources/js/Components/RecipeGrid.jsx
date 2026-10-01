import RecipeCard from '@/Components/RecipeCard';

/** A responsive grid of recipe cards: 1, 2 or 3 columns by screen width. */
export default function RecipeGrid({ recipes }) {
    return (
        <ul className="grid gap-5 sm:grid-cols-2 lg:grid-cols-3">
            {recipes.map((recipe) => (
                <li key={recipe.id}>
                    <RecipeCard recipe={recipe} />
                </li>
            ))}
        </ul>
    );
}
