import { router } from '@inertiajs/react';
import { useEffect, useState } from 'react';

/**
 * Tells screen-reader users when a new page has loaded.
 *
 * Inertia swaps pages without a full reload, so a screen reader would
 * otherwise say nothing after a link is followed. This writes the new
 * page's title into a hidden "live region", which is read aloud.
 */
export default function RouteAnnouncer() {
    const [message, setMessage] = useState('');

    useEffect(() => {
        // The title is updated just after navigation, so wait a moment.
        const stop = router.on('navigate', () => {
            setTimeout(() => setMessage(document.title), 100);
        });

        return stop;
    }, []);

    return (
        <p className="sr-only" role="status" aria-live="polite" aria-atomic="true">
            {message}
        </p>
    );
}
