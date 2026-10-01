import Button from '@/Components/Button';
import Card from '@/Components/Card';
import InputError from '@/Components/InputError';
import InputLabel from '@/Components/InputLabel';
import PrimaryButton from '@/Components/PrimaryButton';
import SelectInput from '@/Components/SelectInput';
import Textarea from '@/Components/Textarea';
import TextInput from '@/Components/TextInput';
import { useForm } from '@inertiajs/react';

/**
 * The form used to start and to edit a forum topic.
 * Pass `topic` when editing; leave it out when creating.
 */
export default function TopicForm({ topic = null, categories, selectedCategory }) {
    const editing = topic !== null;

    const preselected = categories.find(
        (category) => category.slug === selectedCategory,
    );

    const { data, setData, post, put, processing, errors } = useForm({
        title: topic?.title ?? '',
        category_id: topic?.category_id ?? preselected?.id ?? '',
        body: topic?.body ?? '',
    });

    const submit = (e) => {
        e.preventDefault();

        if (editing) {
            put(route('forum.topics.update', topic.slug));
        } else {
            post(route('forum.topics.store'));
        }
    };

    return (
        <form onSubmit={submit}>
            <Card className="space-y-6">
                <div>
                    <InputLabel htmlFor="title" value="Title" />
                    <TextInput
                        id="title"
                        className="mt-1 block w-full"
                        value={data.title}
                        maxLength={150}
                        onChange={(e) => setData('title', e.target.value)}
                        aria-describedby="title-hint"
                        required
                    />
                    <p id="title-hint" className="mt-2 text-sm text-plum">
                        A question or a clear statement works best.
                    </p>
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
                    <InputLabel htmlFor="body" value="Message" />
                    <Textarea
                        id="body"
                        className="mt-1 block w-full"
                        rows={8}
                        value={data.body}
                        maxLength={10000}
                        onChange={(e) => setData('body', e.target.value)}
                        required
                    />
                    <InputError className="mt-2" message={errors.body} />
                </div>
            </Card>

            <div className="mt-6 flex flex-col-reverse gap-3 sm:flex-row">
                <PrimaryButton disabled={processing}>
                    {processing
                        ? 'Saving…'
                        : editing
                          ? 'Save topic'
                          : 'Post topic'}
                </PrimaryButton>
                <Button
                    href={
                        editing
                            ? route('forum.topics.show', topic.slug)
                            : route('forum.index')
                    }
                    variant="secondary"
                >
                    Cancel
                </Button>
            </div>
        </form>
    );
}
