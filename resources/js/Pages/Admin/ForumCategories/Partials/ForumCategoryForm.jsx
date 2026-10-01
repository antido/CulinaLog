import Button from '@/Components/Button';
import Checkbox from '@/Components/Checkbox';
import InputError from '@/Components/InputError';
import InputLabel from '@/Components/InputLabel';
import PrimaryButton from '@/Components/PrimaryButton';
import Textarea from '@/Components/Textarea';
import TextInput from '@/Components/TextInput';
import { useForm } from '@inertiajs/react';

/**
 * The form used to create and to edit a forum category.
 * Pass `category` when editing; leave it out when creating.
 */
export default function ForumCategoryForm({ category = null }) {
    const editing = category !== null;

    const { data, setData, post, put, processing, errors } = useForm({
        name: category?.name ?? '',
        description: category?.description ?? '',
        is_active: category?.is_active ?? true,
        sort_order: category?.sort_order ?? 0,
    });

    const submit = (e) => {
        e.preventDefault();

        if (editing) {
            put(route('admin.forum-categories.update', category.slug));
        } else {
            post(route('admin.forum-categories.store'));
        }
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

            <div>
                <InputLabel htmlFor="sort_order" value="Position in the list" />
                <TextInput
                    id="sort_order"
                    type="number"
                    inputMode="numeric"
                    min={0}
                    max={1000}
                    className="mt-1 block w-32"
                    value={data.sort_order}
                    onChange={(e) => setData('sort_order', e.target.value)}
                    aria-describedby="sort-hint"
                    required
                />
                <p id="sort-hint" className="mt-2 text-sm text-plum">
                    Lower numbers come first.
                </p>
                <InputError className="mt-2" message={errors.sort_order} />
            </div>

            <div>
                <label className="flex min-h-11 items-center gap-3">
                    <Checkbox
                        checked={data.is_active}
                        onChange={(e) => setData('is_active', e.target.checked)}
                    />
                    <span>
                        <span className="font-medium">Active</span>
                        <span className="block text-sm text-plum">
                            Disabled categories are hidden and cannot receive
                            new topics.
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
                    href={route('admin.forum-categories.index')}
                    variant="secondary"
                >
                    Cancel
                </Button>
            </div>
        </form>
    );
}
