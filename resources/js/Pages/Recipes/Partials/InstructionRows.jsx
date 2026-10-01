import Button from '@/Components/Button';
import InputError from '@/Components/InputError';
import InputLabel from '@/Components/InputLabel';
import Textarea from '@/Components/Textarea';
import { moveItem } from '@/utils';
import RowControls from './RowControls';

/**
 * The editable list of cooking steps in the recipe form.
 * Works the same way as IngredientRows.
 */
export default function InstructionRows({ items, errors, onChange }) {
    const updateText = (index, value) => {
        onChange(
            items.map((item, position) =>
                position === index ? { instruction: value } : item,
            ),
        );
    };

    const remove = (index) => {
        onChange(items.filter((_, position) => position !== index));
    };

    return (
        <div>
            <InputError className="mb-4" message={errors.instructions} />

            <ol className="space-y-4">
                {items.map((item, index) => (
                    <li
                        key={index}
                        className="rounded-xl border border-line p-4"
                    >
                        <InputLabel
                            htmlFor={`instruction-${index}`}
                            value={`Step ${index + 1}`}
                            className="font-semibold"
                        />
                        <Textarea
                            id={`instruction-${index}`}
                            className="mt-1 block w-full"
                            rows={3}
                            value={item.instruction}
                            maxLength={2000}
                            onChange={(e) => updateText(index, e.target.value)}
                        />

                        <InputError
                            className="mt-2"
                            message={errors[`instructions.${index}.instruction`]}
                        />

                        <div className="mt-4">
                            <RowControls
                                label={`step ${index + 1}`}
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
                    </li>
                ))}
            </ol>

            <Button
                variant="secondary"
                className="mt-4"
                onClick={() => onChange([...items, { instruction: '' }])}
                disabled={items.length >= 50}
            >
                Add step
            </Button>
        </div>
    );
}
