import Button from '@/Components/Button';
import Checkbox from '@/Components/Checkbox';
import ImageInput from '@/Components/ImageInput';
import InputError from '@/Components/InputError';
import InputLabel from '@/Components/InputLabel';
import PrimaryButton from '@/Components/PrimaryButton';
import Textarea from '@/Components/Textarea';
import TextInput from '@/Components/TextInput';
import { useForm } from '@inertiajs/react';

/**
 * The form used to create and to edit a category.
 * Pass `category` when editing; leave it out when creating.
 */
export default function CategoryForm({ category = null }) {
    const editing = category !== null;

    const { data, setData, post, processing, errors } = useForm({
        // File uploads must be sent as POST; `_method` tells Laravel to treat it as PUT.
        ...(editing ? { _method: 'put' } : {}),
        name: category?.name ?? '',
        description: category?.description ?? '',
        is_active: category?.is_active ?? true,
        image: null,
        remove_image: false,
    });

    const submit = (e) => {
        e.preventDefault();

        post(
            editing
                ? route('admin.categories.update', category.slug)
                : route('admin.categories.store'),
        );
    };

    return (
        <form onSubmit={submit} className="space-y-6">
            <div>
                <InputLabel htmlFor="name" value="Name" />
                <TextInput
                    id="name"
                    className="mt-1 block w-full"
                    value={data.name}
                    maxLength={80}
                    onChange={(e) => setData('name', e.target.value)}
                    required
                />
                <InputError className="mt-2" message={errors.name} />
            </div>

            <div>
                <InputLabel htmlFor="description" value="Description (optional)" />
                <Textarea
                    id="description"
                    className="mt-1 block w-full"
                    value={data.description}
                    maxLength={1000}
                    onChange={(e) => setData('description', e.target.value)}
                />
                <InputError className="mt-2" message={errors.description} />
            </div>

            <ImageInput
                id="image"
                label="Image (optional)"
                currentUrl={category?.image_url}
                error={errors.image}
                onChange={(file) => setData('image', file)}
                onRemoveChange={
                    editing ? (remove) => setData('remove_image', remove) : undefined
                }
            />

            <div>
                <label className="flex min-h-11 items-center gap-3">
                    <Checkbox
                        checked={data.is_active}
                        onChange={(e) => setData('is_active', e.target.checked)}
                    />
                    <span>
                        <span className="font-medium">Active</span>
                        <span className="block text-sm text-plum">
                            Disabled categories are hidden from visitors and
                            cannot be chosen for new recipes.
                        </span>
                    </span>
                </label>
                <InputError className="mt-2" message={errors.is_active} />
            </div>

            <div className="flex flex-col-reverse gap-3 sm:flex-row">
                <PrimaryButton disabled={processing}>
                    {processing
                        ? 'Saving…'
                        : editing
                          ? 'Save category'
                          : 'Create category'}
                </PrimaryButton>
                <Button
                    href={route('admin.categories.index')}
                    variant="secondary"
                >
                    Cancel
                </Button>
            </div>
        </form>
    );
}
