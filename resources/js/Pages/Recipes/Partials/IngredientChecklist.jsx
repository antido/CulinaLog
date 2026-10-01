import { useState } from 'react';

/**
 * The ingredient list on a recipe page. Each ingredient is a checkbox the
 * cook can tick while gathering things. The ticks live only in this page:
 * nothing is saved, and they reset when the page is reloaded.
 */
export default function IngredientChecklist({ ingredients }) {
    const [checked, setChecked] = useState([]);

    const toggle = (id) => {
        setChecked((current) =>
            current.includes(id)
                ? current.filter((checkedId) => checkedId !== id)
                : [...current, id],
        );
    };

    return (
        <ul className="mt-4 divide-y divide-line rounded-2xl border border-line bg-paper">
            {ingredients.map((ingredient) => {
                const isChecked = checked.includes(ingredient.id);
                const amount = [ingredient.quantity, ingredient.unit]
                    .filter(Boolean)
                    .join(' ');

                return (
                    <li key={ingredient.id}>
                        <label className="flex min-h-12 cursor-pointer items-start gap-3 px-4 py-3">
                            <input
                                type="checkbox"
                                checked={isChecked}
                                onChange={() => toggle(ingredient.id)}
                                className="size-6 shrink-0 rounded border-line text-aubergine"
                            />
                            <span
                                className={`min-w-0 break-words ${
                                    isChecked ? 'text-plum line-through' : ''
                                }`}
                            >
                                {amount && (
                                    <span className="font-semibold">
                                        {amount}{' '}
                                    </span>
                                )}
                                {ingredient.name}
                            </span>
                        </label>
                    </li>
                );
            })}
        </ul>
    );
}
