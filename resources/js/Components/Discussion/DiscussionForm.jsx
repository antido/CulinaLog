import Button from '@/Components/Button';
import InputError from '@/Components/InputError';
import InputLabel from '@/Components/InputLabel';
import PrimaryButton from '@/Components/PrimaryButton';
import Textarea from '@/Components/Textarea';
import TextInput from '@/Components/TextInput';
import { capitalize } from '@/utils';
import { useForm, usePage } from '@inertiajs/react';

/**
 * The form for a new post in a discussion, or for an answer to one
 * when `parentId` is given. Used for recipe comments and forum replies.
 *
 * - `storeUrl` is where the form is sent.
 * - `noun` is what a post is called here: "comment" or "reply".
 * - `allowGuestName` adds an optional name field for guests.
 */
export default function DiscussionForm({
    storeUrl,
    noun,
    allowGuestName = false,
    parentId = null,
    onDone,
}) {
    const { auth } = usePage().props;
    const isGuest = auth.user === null;

    // Give every form on the page its own field ids.
    const idPrefix = parentId ? `answer-${parentId}` : 'post';

    const { data, setData, post, processing, errors, reset } = useForm({
        body: '',
        guest_name: '',
        parent_id: parentId,
        website: '',
    });

    const submit = (e) => {
        e.preventDefault();

        post(storeUrl, {
            preserveScroll: true,
            onSuccess: () => {
                reset('body');
                onDone?.();
            },
        });
    };

    return (
        <form onSubmit={submit} className="space-y-4">
            {isGuest && allowGuestName && (
                <div>
                    <InputLabel
                        htmlFor={`${idPrefix}-name`}
                        value="Name (optional)"
                    />
                    <TextInput
                        id={`${idPrefix}-name`}
                        className="mt-1 block w-full sm:w-72"
                        value={data.guest_name}
                        maxLength={40}
                        placeholder="Anonymous"
                        autoComplete="nickname"
                        onChange={(e) => setData('guest_name', e.target.value)}
                    />
                    <InputError className="mt-2" message={errors.guest_name} />
                </div>
            )}

            <div>
                <InputLabel
                    htmlFor={`${idPrefix}-body`}
                    value={parentId ? 'Your answer' : `Your ${noun}`}
                />
                <Textarea
                    id={`${idPrefix}-body`}
                    className="mt-1 block w-full"
                    rows={3}
                    value={data.body}
                    onChange={(e) => setData('body', e.target.value)}
                    required
                />
                <InputError
                    className="mt-2"
                    message={errors.body ?? errors.parent_id}
                />
            </div>

            {/*
              Spam trap: hidden from people and from screen readers, so only
              automated scripts fill it in. The server discards those posts.
            */}
            <div className="hidden" aria-hidden="true">
                <label htmlFor={`${idPrefix}-website`}>Website</label>
                <input
                    id={`${idPrefix}-website`}
                    type="text"
                    name="website"
                    tabIndex={-1}
                    autoComplete="off"
                    value={data.website}
                    onChange={(e) => setData('website', e.target.value)}
                />
            </div>

            <div className="flex flex-wrap gap-3">
                <PrimaryButton disabled={processing}>
                    {processing
                        ? 'Posting…'
                        : parentId
                          ? 'Post answer'
                          : `Post ${noun}`}
                </PrimaryButton>
                {parentId && (
                    <Button variant="secondary" onClick={onDone}>
                        Cancel
                    </Button>
                )}
            </div>

            <p className="sr-only" role="status">
                {processing ? `${capitalize(noun)} is being posted` : ''}
            </p>
        </form>
    );
}
