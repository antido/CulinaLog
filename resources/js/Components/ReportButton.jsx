import Button from '@/Components/Button';
import InputError from '@/Components/InputError';
import InputLabel from '@/Components/InputLabel';
import Modal from '@/Components/Modal';
import SelectInput from '@/Components/SelectInput';
import Textarea from '@/Components/Textarea';
import { DialogTitle } from '@headlessui/react';
import { useForm } from '@inertiajs/react';
import { useState } from 'react';

// Keep in step with app/Enums/ReportReason.php.
const reasons = [
    { value: 'spam', label: 'Spam' },
    { value: 'harassment', label: 'Harassment' },
    { value: 'inappropriate', label: 'Inappropriate Content' },
    { value: 'copyright', label: 'Copyright Concern' },
    { value: 'misleading', label: 'Misleading Information' },
    { value: 'other', label: 'Other' },
];

/**
 * A "Report" button that opens a short form and sends the report
 * to the moderators.
 *
 * - `type` is one of: recipe, comment, forum_topic, forum_reply.
 * - `id` is the id of that item.
 * - `noun` names the item in the dialog, for example "comment".
 * - `className` styles the button that opens the dialog.
 */
export default function ReportButton({ type, id, noun, className }) {
    const [open, setOpen] = useState(false);

    const { data, setData, post, processing, errors, reset, clearErrors } =
        useForm({
            type,
            id,
            reason: 'spam',
            description: '',
        });

    const close = () => {
        setOpen(false);
        reset();
        clearErrors();
    };

    const submit = (e) => {
        e.preventDefault();

        post(route('reports.store'), {
            preserveScroll: true,
            onSuccess: close,
        });
    };

    return (
        <>
            <button
                type="button"
                className={
                    className ??
                    'inline-flex min-h-11 items-center rounded-lg px-2 text-sm font-semibold text-plum hover:bg-aubergine-soft hover:text-aubergine'
                }
                onClick={() => setOpen(true)}
            >
                Report
            </button>

            <Modal show={open} onClose={close} maxWidth="md">
                <form onSubmit={submit} className="space-y-5 p-6">
                    <div>
                        <DialogTitle className="font-display text-xl font-semibold">
                            Report this {noun}
                        </DialogTitle>
                        <p className="mt-1 text-sm text-plum">
                            A moderator will look at it. The author is not
                            told who reported it.
                        </p>
                    </div>

                    <div>
                        <InputLabel
                            htmlFor={`report-reason-${type}-${id}`}
                            value="What is wrong with it?"
                        />
                        <SelectInput
                            id={`report-reason-${type}-${id}`}
                            className="mt-1 block w-full"
                            value={data.reason}
                            onChange={(e) => setData('reason', e.target.value)}
                        >
                            {reasons.map((reason) => (
                                <option key={reason.value} value={reason.value}>
                                    {reason.label}
                                </option>
                            ))}
                        </SelectInput>
                        <InputError className="mt-2" message={errors.reason} />
                    </div>

                    <div>
                        <InputLabel
                            htmlFor={`report-description-${type}-${id}`}
                            value={
                                data.reason === 'other'
                                    ? 'Details'
                                    : 'Details (optional)'
                            }
                        />
                        <Textarea
                            id={`report-description-${type}-${id}`}
                            className="mt-1 block w-full"
                            rows={3}
                            maxLength={1000}
                            value={data.description}
                            onChange={(e) =>
                                setData('description', e.target.value)
                            }
                        />
                        <InputError
                            className="mt-2"
                            message={
                                errors.description ?? errors.type ?? errors.id
                            }
                        />
                    </div>

                    <div className="flex flex-col-reverse gap-3 sm:flex-row sm:justify-end">
                        <Button variant="secondary" onClick={close}>
                            Cancel
                        </Button>
                        <Button type="submit" disabled={processing}>
                            {processing ? 'Sending…' : 'Send report'}
                        </Button>
                    </div>
                </form>
            </Modal>
        </>
    );
}
