import Button from '@/Components/Button';
import Card from '@/Components/Card';
import ImageInput from '@/Components/ImageInput';
import InputError from '@/Components/InputError';
import InputLabel from '@/Components/InputLabel';
import PrimaryButton from '@/Components/PrimaryButton';
import SelectInput from '@/Components/SelectInput';
import Textarea from '@/Components/Textarea';
import TextInput from '@/Components/TextInput';
import { useForm } from '@inertiajs/react';
import IngredientRows from './IngredientRows';
import InstructionRows from './InstructionRows';

/**
 * The form used to create and to edit a recipe.
 * Pass `recipe` when editing; leave it out when creating.
 */
export default function RecipeForm({
    recipe = null,
    categories,
    difficulties,
    statuses,
}) {
    const editing = recipe !== null;

    const { data, setData, post, processing, errors } = useForm({
        // File uploads must be sent as POST; `_method` tells Laravel to treat it as PUT.
        ...(editing ? { _method: 'put' } : {}),
        title: recipe?.title ?? '',
        category_id: recipe?.category_id ?? '',
        description: recipe?.description ?? '',
        prep_time: recipe?.prep_time ?? '',
        cook_time: recipe?.cook_time ?? '',
        servings: recipe?.servings ?? '',
        difficulty: recipe?.difficulty ?? 'easy',
        status: recipe?.status ?? 'draft',
        ingredients: recipe?.ingredients ?? [
            { name: '', quantity: '', unit: '' },
        ],
        instructions: recipe?.instructions ?? [{ instruction: '' }],
        image: null,
        remove_image: false,
    });

    const submit = (e) => {
        e.preventDefault();

        post(
            editing
                ? route('recipes.update', recipe.slug)
                : route('recipes.store'),
        );
    };

    const hasErrors = Object.keys(errors).length > 0;

    return (
        <form onSubmit={submit} className="space-y-6" noValidate>
            {hasErrors && (
                <p
                    role="alert"
                    className="rounded-xl border border-chili/20 bg-chili-soft px-4 py-3 text-sm font-medium text-chili"
                >
                    The recipe was not saved. Fix the fields marked below and
                    save again.
                </p>
            )}

            <Card className="space-y-6">
                <h2 className="text-xl font-semibold">About the recipe</h2>

                <div>
                    <InputLabel htmlFor="title" value="Title" />
                    <TextInput
                        id="title"
                        className="mt-1 block w-full"
                        value={data.title}
                        maxLength={150}
                        onChange={(e) => setData('title', e.target.value)}
                        required
                    />
                    <InputError className="mt-2" message={errors.title} />
                </div>

                <div>
                    <InputLabel htmlFor="category_id" value="Category" />
                    <SelectInput
                        id="category_id"
                        className="mt-1 block w-full"
                        value={data.category_id}
                        onChange={(e) => setData('category_id', e.target.value)}
                        required
                    >
                        <option value="">Choose a category</option>
                        {categories.map((category) => (
                            <option key={category.id} value={category.id}>
                                {category.name}
                            </option>
                        ))}
                    </SelectInput>
                    <InputError className="mt-2" message={errors.category_id} />
                </div>

                <div>
                    <InputLabel htmlFor="description" value="Description" />
                    <Textarea
                        id="description"
                        className="mt-1 block w-full"
                        rows={5}
                        value={data.description}
                        maxLength={5000}
                        onChange={(e) => setData('description', e.target.value)}
                        aria-describedby="description-hint"
                        required
                    />
                    <p id="description-hint" className="mt-2 text-sm text-plum">
                        What is the dish, and what makes your version worth
                        cooking?
                    </p>
                    <InputError className="mt-2" message={errors.description} />
                </div>

                <ImageInput
                    id="image"
                    label="Photo (optional)"
                    currentUrl={recipe?.image_url}
                    error={errors.image}
                    onChange={(file) => setData('image', file)}
                    onRemoveChange={
                        editing
                            ? (remove) => setData('remove_image', remove)
                            : undefined
                    }
                />
            </Card>

            <Card className="space-y-6">
                <h2 className="text-xl font-semibold">Time and servings</h2>

                <div className="grid gap-6 sm:grid-cols-2">
                    <div>
                        <InputLabel
                            htmlFor="prep_time"
                            value="Preparation time (minutes)"
                        />
                        <TextInput
                            id="prep_time"
                            type="number"
                            inputMode="numeric"
                            min={0}
                            max={1440}
                            className="mt-1 block w-full"
                            value={data.prep_time}
                            onChange={(e) => setData('prep_time', e.target.value)}
                            required
                        />
                        <InputError className="mt-2" message={errors.prep_time} />
                    </div>

                    <div>
                        <InputLabel
                            htmlFor="cook_time"
                            value="Cooking time (minutes)"
                        />
                        <TextInput
                            id="cook_time"
                            type="number"
                            inputMode="numeric"
                            min={0}
                            max={1440}
                            className="mt-1 block w-full"
                            value={data.cook_time}
                            onChange={(e) => setData('cook_time', e.target.value)}
                            required
                        />
                        <InputError className="mt-2" message={errors.cook_time} />
                    </div>

                    <div>
                        <InputLabel htmlFor="servings" value="Servings" />
                        <TextInput
                            id="servings"
                            type="number"
                            inputMode="numeric"
                            min={1}
                            max={100}
                            className="mt-1 block w-full"
                            value={data.servings}
                            onChange={(e) => setData('servings', e.target.value)}
                            required
                        />
                        <InputError className="mt-2" message={errors.servings} />
                    </div>

                    <div>
                        <InputLabel htmlFor="difficulty" value="Difficulty" />
                        <SelectInput
                            id="difficulty"
                            className="mt-1 block w-full"
                            value={data.difficulty}
                            onChange={(e) => setData('difficulty', e.target.value)}
                        >
                            {difficulties.map((difficulty) => (
                                <option
                                    key={difficulty.value}
                                    value={difficulty.value}
                                >
                                    {difficulty.label}
                                </option>
                            ))}
                        </SelectInput>
                        <InputError className="mt-2" message={errors.difficulty} />
                    </div>
                </div>
            </Card>

            <Card>
                <h2 className="text-xl font-semibold">Ingredients</h2>
                <p className="mb-5 mt-1 text-sm text-plum">
                    List them in the order they are used. Leave the quantity
                    empty for things like "salt, to taste".
                </p>
                <IngredientRows
                    items={data.ingredients}
                    errors={errors}
                    onChange={(items) => setData('ingredients', items)}
                />
            </Card>

            <Card>
                <h2 className="text-xl font-semibold">Instructions</h2>
                <p className="mb-5 mt-1 text-sm text-plum">
                    One action per step is easiest to follow while cooking.
                </p>
                <InstructionRows
                    items={data.instructions}
                    errors={errors}
                    onChange={(items) => setData('instructions', items)}
                />
            </Card>

            <Card className="space-y-6">
                <h2 className="text-xl font-semibold">Who can see it</h2>

                <div>
                    <InputLabel htmlFor="status" value="Status" />
                    <SelectInput
                        id="status"
                        className="mt-1 block w-full sm:w-64"
                        value={data.status}
                        onChange={(e) => setData('status', e.target.value)}
                        aria-describedby="status-hint"
                    >
                        {statuses.map((status) => (
                            <option key={status.value} value={status.value}>
                                {status.label}
                            </option>
                        ))}
                    </SelectInput>
                    <p id="status-hint" className="mt-2 text-sm text-plum">
                        Published recipes are visible to everyone. Drafts and
                        archived recipes are visible only to you.
                    </p>
                    <InputError className="mt-2" message={errors.status} />
                </div>
            </Card>

            <div className="flex flex-col-reverse gap-3 sm:flex-row">
                <PrimaryButton disabled={processing}>
                    {processing ? 'Saving…' : 'Save recipe'}
                </PrimaryButton>
                <Button
                    href={
                        editing
                            ? route('recipes.show', recipe.slug)
                            : route('dashboard')
                    }
                    variant="secondary"
                >
                    Cancel
                </Button>
            </div>
        </form>
    );
}
