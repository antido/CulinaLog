import Button from '@/Components/Button';
import Modal from '@/Components/Modal';
import { DialogTitle } from '@headlessui/react';

/**
 * Asks the user to confirm a destructive action before it runs.
 *
 * <ConfirmDialog
 *     show={confirming}
 *     title="Delete this recipe?"
 *     message="This cannot be undone."
 *     confirmLabel="Delete recipe"
 *     onConfirm={deleteRecipe}
 *     onClose={() => setConfirming(false)}
 * />
 */
export default function ConfirmDialog({
    show,
    title,
    message,
    confirmLabel = 'Confirm',
    processing = false,
    onConfirm,
    onClose,
}) {
    return (
        <Modal show={show} onClose={onClose} maxWidth="md">
            <div className="p-6">
                <DialogTitle className="font-display text-xl font-semibold">
                    {title}
                </DialogTitle>

                {message && <p className="mt-2 text-plum">{message}</p>}

                <div className="mt-6 flex flex-col-reverse gap-3 sm:flex-row sm:justify-end">
                    <Button variant="secondary" onClick={onClose}>
                        Cancel
                    </Button>
                    <Button
                        variant="danger"
                        onClick={onConfirm}
                        disabled={processing}
                    >
                        {confirmLabel}
                    </Button>
                </div>
            </div>
        </Modal>
    );
}
