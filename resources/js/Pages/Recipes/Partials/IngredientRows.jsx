import Button from '@/Components/Button';
import InputError from '@/Components/InputError';
import InputLabel from '@/Components/InputLabel';
import TextInput from '@/Components/TextInput';
import { moveItem } from '@/utils';
import RowControls from './RowControls';

const emptyIngredient = { name: '', quantity: '', unit: '' };

/**
 * The editable list of ingredients in the recipe form.
 *
 * `items` is the array held by the form, `onChange` receives the new array,
 * and `errors` is the form's error object (keys look like "ingredients.0.name").
 */
export default function IngredientRows({ items, errors, onChange }) {
    const updateField = (index, field, value) => {
        onChange(
            items.map((item, position) =>
                position === index ? { ...item, [field]: value } : item,
            ),
        );
    };

    const remove = (index) => {
        onChange(items.filter((_, position) => position !== index));
    };

    return (
        <div>
            <InputError className="mb-4" message={errors.ingredients} />

            <ol className="space-y-4">
                {items.map((item, index) => (
                    <li key={index}>
                        <fieldset className="rounded-xl border border-line p-4">
                            <legend className="px-1 text-sm font-semibold">
                                Ingredient {index + 1}
                            </legend>

                            <div className="grid gap-4 sm:grid-cols-[1fr_7rem_8rem]">
                                <div>
                                    <InputLabel
                                        htmlFor={`ingredient-${index}-name`}
                                        value="Name"
                                    />
                                    <TextInput
                                        id={`ingredient-${index}-name`}
                                        className="mt-1 block w-full"
                                        value={item.name}
                                        maxLength={150}
                                        placeholder="Garlic, minced"
                                        onChange={(e) =>
                                            updateField(index, 'name', e.target.value)
                                        }
                                    />
                                </div>

                                <div>
                                    <InputLabel
                                        htmlFor={`ingredient-${index}-quantity`}
                                        value="Quantity"
                                    />
                                    <TextInput
                                        id={`ingredient-${index}-quantity`}
                                        type="number"
                                        inputMode="decimal"
                                        min={0}
                                        step="any"
                                        className="mt-1 block w-full"
                                        value={item.quantity}
                                        placeholder="4"
                                        onChange={(e) =>
                                            updateField(
                                                index,
                                                'quantity',
                                                e.target.value,
                                            )
                                        }
                                    />
                                </div>

                                <div>
                                    <InputLabel
                                        htmlFor={`ingredient-${index}-unit`}
                                        value="Unit"
                                    />
                                    <TextInput
                                        id={`ingredient-${index}-unit`}
                                        className="mt-1 block w-full"
                                        value={item.unit}
                                        maxLength={30}
                                        placeholder="cloves"
                                        onChange={(e) =>
                                            updateField(index, 'unit', e.target.value)
                                        }
                                    />
                                </div>
                            </div>

                            <InputError
                                className="mt-2"
                                message={
                                    errors[`ingredients.${index}.name`] ??
                                    errors[`ingredients.${index}.quantity`] ??
                                    errors[`ingredients.${index}.unit`]
                                }
                            />

                            <div className="mt-4">
                                <RowControls
                                    label={`ingredient ${index + 1}`}
                                    isFirst={index === 0}
                                    isLast={index === items.length - 1}
                                    canRemove={items.length > 1}
                                    onMoveUp={() =>
                                        onChange(moveItem(items, index, index - 1))
                                    }
                                    onMoveDown={() =>
                                        onChange(moveItem(items, index, index + 1))
                                    }
                                    onRemove={() => remove(index)}
                                />
                            </div>
                        </fieldset>
                    </li>
                ))}
            </ol>

            <Button
                variant="secondary"
                className="mt-4"
                onClick={() => onChange([...items, { ...emptyIngredient }])}
                disabled={items.length >= 50}
            >
                Add ingredient
            </Button>
        </div>
    );
}
