import { usePage } from '@inertiajs/react';
import { useEffect, useState } from 'react';

/**
 * Shows the success or error message the server flashed to the session,
 * for example after saving a recipe. Screen readers announce it.
 */
export default function FlashMessages() {
    const { flash } = usePage().props;
    const [visible, setVisible] = useState(true);

    // Show the message again whenever a new one arrives.
    useEffect(() => {
        setVisible(true);
    }, [flash]);

    const message = flash?.success || flash?.error;

    if (!message || !visible) {
        return <div role="status" aria-live="polite" />;
    }

    const isError = Boolean(flash.error);

    return (
        <div role="status" aria-live="polite" className="mx-auto max-w-6xl px-4 pt-4 sm:px-6">
            <div
                className={`flex items-start justify-between gap-4 rounded-xl border px-4 py-3 text-sm font-medium ${
                    isError
                        ? 'border-chili/20 bg-chili-soft text-chili'
                        : 'border-basil/20 bg-basil-soft text-basil'
                }`}
            >
                <p>{message}</p>
                <button
                    type="button"
                    onClick={() => setVisible(false)}
                    className="-m-2 rounded-lg p-2 underline underline-offset-2"
                >
                    Dismiss
                </button>
            </div>
        </div>
    );
}
